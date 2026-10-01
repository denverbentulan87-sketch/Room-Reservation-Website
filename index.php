<?php
require __DIR__ . '/includes/bootstrap.php';

$rooms = rooms_with_status(true);
$activeRooms = array_filter($rooms, fn($r) => $r['status'] === 'active');
$openBeds = array_sum(array_column($activeRooms, 'open_beds'));
$roomsWithOpen = count(array_filter($activeRooms, fn($r) => $r['open_beds'] > 0));
$shown = array_slice($rooms, 0, 6);

public_header(setting('site_name', 'Pajuleras Boarding House'), 'home');
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <h1>A place to stay in Poblacion, Inabanga</h1>
      <p class="lead"><?= e(setting('about_text')) ?></p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url('rooms.php')) ?>">See available rooms</a>
        <a class="btn btn-lg" href="<?= e(url('inquire.php')) ?>">Send an inquiry</a>
      </div>
      <p class="hero-note">No account needed. Send a message and the landlord will reply. You can check the reply anytime with your inquiry code.</p>
    </div>
    <figure class="hero-photo">
      <img src="<?= e(url('assets/img/house-outside.jpg')) ?>" alt="Front of the boarding house: a woven-bamboo wall, a wooden door, and green plants under a tin roof" width="1400" height="883" fetchpriority="high">
      <figcaption class="hero-chip">
        <?php if ($openBeds > 0): ?>
          <strong><?= plural($openBeds, 'bed') ?> open</strong> in <?= plural($roomsWithOpen, 'room') ?> right now
        <?php else: ?>
          <strong>No beds are open</strong> right now. Send an inquiry to ask when one frees up.
        <?php endif; ?>
      </figcaption>
    </figure>
  </div>
</section>

<section class="section" id="rooms">
  <div class="wrap">
    <div class="section-head">
      <h2>Room board</h2>
      <p>This board is kept up to date from the landlord's records, so what you see is what is free today.</p>
      <?= pips_legend() ?>
    </div>
    <?php if ($shown): ?>
      <div class="room-grid">
        <?php foreach ($shown as $r) { echo room_card($r); } ?>
      </div>
      <?php if (count($rooms) > count($shown)): ?>
        <p class="more"><a class="btn" href="<?= e(url('rooms.php')) ?>">See all <?= count($rooms) ?> rooms</a></p>
      <?php endif; ?>
    <?php else: ?>
      <p class="empty">Rooms will be listed here soon. In the meantime, <a href="<?= e(url('inquire.php')) ?>">send us an inquiry</a>.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap steps-grid">
    <div>
      <h2>How to reserve a room</h2>
      <p class="muted">Reserving is simple, and the landlord confirms each step with you.</p>
      <p><a class="btn btn-primary" href="<?= e(url('inquire.php')) ?>">Start with an inquiry</a></p>
    </div>
    <ol class="steps">
      <?php foreach (reservation_steps() as [$t, $d]): ?>
        <li><strong><?= e($t) ?></strong><span><?= e($d) ?></span></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section">
  <div class="wrap gallery">
    <figure>
      <img src="<?= e(url('assets/img/room-inside.jpg')) ?>" alt="Inside a shared room: wooden bunk beds against plywood walls" loading="lazy" width="1400" height="867">
      <figcaption>Inside a shared room, with bunk-style beds.</figcaption>
    </figure>
    <div class="gallery-text">
      <h2>Before you move in</h2>
      <p>You will be asked for a few things so the landlord can record your reservation properly:</p>
      <ul class="tick">
        <?php foreach (array_slice(lines(setting('requirements')), 0, 5) as $l): ?><li><?= e($l) ?></li><?php endforeach; ?>
      </ul>
      <p><a href="<?= e(url('policies.php')) ?>">Read the rates, payment schedule, and house rules</a></p>
    </div>
  </div>
</section>

<section class="section cta-band">
  <div class="wrap cta-row">
    <div>
      <h2>Have a question first?</h2>
      <p>Ask about rates, room availability, or requirements. There is no obligation.</p>
    </div>
    <div class="cta-actions">
      <a class="btn btn-primary btn-lg" href="<?= e(url('inquire.php')) ?>">Send an inquiry</a>
      <?php if (setting('phone') !== ''): ?><span class="cta-phone">or call/text <strong><?= e(setting('phone')) ?></strong></span><?php endif; ?>
    </div>
  </div>
</section>
<?php public_footer();
