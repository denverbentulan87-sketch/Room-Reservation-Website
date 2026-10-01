<?php
require __DIR__ . '/includes/bootstrap.php';

const TRACK_MINUTES = 30;
const MAX_MESSAGES_PER_INQUIRY = 40;

function normalize_ref(string $s): string
{
    $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $s) ?? '');
    return strlen($s) === 8 ? substr($s, 0, 4) . '-' . substr($s, 4) : $s;
}

function tracked_inquiry(): ?array
{
    $id = (int)($_SESSION['track_id'] ?? 0);
    $ts = (int)($_SESSION['track_time'] ?? 0);
    if (!$id || (time() - $ts) > TRACK_MINUTES * 60) {
        unset($_SESSION['track_id'], $_SESSION['track_time']);
        return null;
    }
    return fetch_one("SELECT i.*, r.room_no FROM inquiries i LEFT JOIN rooms r ON r.id = i.room_id WHERE i.id = ?", [$id]);
}

$errors = [];
$REF = '';

if (is_post()) {
    csrf_verify();
    $action = post_str('action', 20);

    if ($action === 'lookup') {
        $REF = post_str('ref', 30);
        $contact = post_str('contact', 150);
        if (throttle_count('track', 15) >= (int)cfg('max_track_failures', 8)) {
            $errors[] = 'Too many unsuccessful tries. Please wait 15 minutes, or call the landlord.';
        } else {
            $inq = fetch_one("SELECT * FROM inquiries WHERE ref_code = ?", [normalize_ref($REF)]);
            $ok = false;
            if ($inq && $contact !== '') {
                $ok = (normalize_phone($contact) !== '' && normalize_phone($contact) === $inq['contact_no'])
                   || ($inq['email'] && mb_strtolower($contact) === mb_strtolower((string)$inq['email']));
            }
            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['track_id'] = (int)$inq['id'];
                $_SESSION['track_time'] = time();
                throttle_clear('track');
                redirect('track.php');
            }
            throttle_hit('track');
            $errors[] = 'We could not find an inquiry with that code and contact number. Check them and try again.';
        }
    } elseif ($action === 'reply') {
        $inq = tracked_inquiry();
        if (!$inq) {
            flash('warn', 'Your session timed out. Please enter your code again.');
            redirect('track.php');
        }
        $body = post_str('body', 2000);
        $count = (int)fetch_val("SELECT COUNT(*) FROM inquiry_messages WHERE inquiry_id = ?", [$inq['id']]);
        $recent = (int)fetch_val("SELECT COUNT(*) FROM inquiry_messages WHERE inquiry_id = ? AND sender = 'customer' AND created_at > (NOW() - INTERVAL 1 HOUR)", [$inq['id']]);
        if ($inq['status'] === 'closed') {
            $errors[] = 'This inquiry is closed. Please send a new inquiry.';
        } elseif (mb_strlen($body) < 2) {
            $errors[] = 'Please write your message.';
        } elseif ($count >= MAX_MESSAGES_PER_INQUIRY || $recent >= 8) {
            $errors[] = 'You have sent many messages already. Please wait for the landlord to reply, or call the landlord.';
        } else {
            in_transaction(function () use ($inq, $body) {
                q("INSERT INTO inquiry_messages (inquiry_id, sender, body) VALUES (?, 'customer', ?)", [$inq['id'], $body]);
                q("UPDATE inquiries SET admin_unread = 1, status = CASE WHEN status = 'replied' THEN 'new' ELSE status END WHERE id = ?", [$inq['id']]);
            });
            $_SESSION['track_time'] = time();
            flash('success', 'Your message was sent to the landlord.');
            redirect('track.php');
        }
    } elseif ($action === 'leave') {
        unset($_SESSION['track_id'], $_SESSION['track_time']);
        redirect('track.php');
    }
}

$inq = tracked_inquiry();
public_header('Check my inquiry', 'track', 'Read the landlord\'s reply to your inquiry.');

if (!$inq): ?>
<section class="page-title">
  <div class="wrap">
    <h1>Check my inquiry</h1>
    <p>Enter the code you received when you sent your inquiry, plus the contact number (or email) you used.</p>
  </div>
</section>
<section class="section tight">
  <div class="wrap narrow">
    <form class="card form" method="post" action="<?= e(url('track.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="lookup">
      <?php render_errors($errors); ?>
      <label>Inquiry code
        <input type="text" name="ref" value="<?= e($REF) ?>" maxlength="30" required placeholder="K7M2-9QXA" autocomplete="off" autocapitalize="characters" spellcheck="false">
      </label>
      <label>Contact number or email you used
        <input type="text" name="contact" maxlength="150" required autocomplete="off">
      </label>
      <button class="btn btn-primary btn-lg" type="submit">Show my inquiry</button>
      <p class="muted small">Lost your code? Call the landlord<?= setting('phone') !== '' ? ' at ' . e(setting('phone')) : '' ?> or send a new inquiry.</p>
    </form>
  </div>
</section>
<?php else:
    $messages = fetch_all("SELECT * FROM inquiry_messages WHERE inquiry_id = ? ORDER BY created_at, id", [$inq['id']]);
    ?>
<section class="page-title">
  <div class="wrap">
    <h1>Your inquiry</h1>
    <p><?= e($inq['subject']) ?> &middot; code <strong class="code-inline"><?= e($inq['ref_code']) ?></strong></p>
  </div>
</section>
<section class="section tight">
  <div class="wrap narrow">
    <div class="thread-head">
      <span>Status:
        <?php
        $label = ['new' => 'Waiting for the landlord\'s reply', 'replied' => 'The landlord has replied', 'converted' => 'A reservation was recorded for you', 'closed' => 'Closed'][$inq['status']] ?? $inq['status'];
        echo '<strong>' . e($label) . '</strong>';
        ?>
      </span>
      <form method="post" action="<?= e(url('track.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="leave"><button class="btn btn-sm btn-ghost" type="submit">Close this view</button></form>
    </div>
    <?php render_errors($errors); ?>

    <div class="thread" aria-label="Messages">
      <?php foreach ($messages as $m): ?>
        <div class="msg msg-<?= e($m['sender']) ?>">
          <div class="msg-meta"><strong><?= $m['sender'] === 'admin' ? 'Landlord' : 'You' ?></strong> <span><?= e(fmt_datetime($m['created_at'])) ?></span></div>
          <div class="msg-body"><?= nl2p($m['body']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($inq['status'] !== 'closed'): ?>
    <form class="card form" method="post" action="<?= e(url('track.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="reply">
      <label>Send a follow-up message
        <textarea name="body" rows="4" maxlength="2000" required></textarea>
      </label>
      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
    <?php else: ?>
      <p class="empty-box">This inquiry is closed. <a href="<?= e(url('inquire.php')) ?>">Send a new inquiry</a> if you still need a room.</p>
    <?php endif; ?>
  </div>
</section>
<?php endif;
public_footer();
