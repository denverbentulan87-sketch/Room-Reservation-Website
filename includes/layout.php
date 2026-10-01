<?php
declare(strict_types=1);

function render_flashes(): void
{
    foreach (pull_flashes() as $f) {
        $type = in_array($f['type'], ['success', 'error', 'warn', 'info'], true) ? $f['type'] : 'info';
        $role = $type === 'error' ? 'alert' : 'status';
        echo '<div class="flash flash-' . e($type) . '" role="' . $role . '"><span>' . e($f['msg']) . '</span><button type="button" class="flash-x" aria-label="Dismiss message" data-dismiss>&times;</button></div>';
    }
}

function render_errors(array $errors): void
{
    if (!$errors) {
        return;
    }
    echo '<div class="flash flash-error" role="alert"><div><strong>Please fix the following:</strong><ul class="err-list">';
    foreach ($errors as $err) {
        echo '<li>' . e($err) . '</li>';
    }
    echo '</ul></div></div>';
}

/* ==================================================================
 *  PUBLIC WEBSITE
 * ================================================================== */
function public_header(string $title, string $active = '', string $description = ''): void
{
    $site = setting('site_name', 'Pajuleras Boarding House');
    $desc = $description !== '' ? $description : setting('tagline');
    $nav = [
        'home'    => ['Home', 'index.php'],
        'rooms'   => ['Rooms', 'rooms.php'],
        'policies' => ['Rates & rules', 'policies.php'],
        'inquire' => ['Send an inquiry', 'inquire.php'],
        'track'   => ['Check my inquiry', 'track.php'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title === $site ? $site : $title . ' | ' . $site) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
</head>
<body class="public">
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap header-row">
    <a class="brand" href="<?= e(url('')) ?>">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-text"><strong><?= e($site) ?></strong><small><?= e(setting('address')) ?></small></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>Menu</button>
    <nav id="site-nav" class="site-nav" aria-label="Main">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="weave" aria-hidden="true"></div>
</header>
<main id="main">
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="wrap flash-wrap"><?php render_flashes(); ?></div>
<?php endif;
}

function public_footer(): void
{
    $site = setting('site_name', 'Pajuleras Boarding House');
    ?>
</main>
<footer class="site-footer">
  <div class="weave weave-dark" aria-hidden="true"></div>
  <div class="wrap footer-grid">
    <div>
      <h2 class="foot-title"><?= e($site) ?></h2>
      <p><?= e(setting('address')) ?></p>
      <p>Landlord: <?= e(setting('landlord_name')) ?></p>
    </div>
    <div>
      <h2 class="foot-title">Contact the landlord</h2>
      <?php if (setting('phone') !== ''): ?><p>Phone / text: <strong><?= e(setting('phone')) ?></strong></p><?php endif; ?>
      <?php if (setting('email') !== ''): ?><p>Email: <?= e(setting('email')) ?></p><?php endif; ?>
      <?php if (setting('contact_hours') !== ''): ?><p><?= e(setting('contact_hours')) ?></p><?php endif; ?>
    </div>
    <div>
      <h2 class="foot-title">Looking for a room?</h2>
      <p><a href="<?= e(url('rooms.php')) ?>">See available rooms</a></p>
      <p><a href="<?= e(url('inquire.php')) ?>">Send an inquiry</a></p>
      <p><a href="<?= e(url('track.php')) ?>">Check the reply to your inquiry</a></p>
    </div>
  </div>
  <div class="wrap footer-base">
    <span>&copy; <?= date('Y') ?> <?= e($site) ?></span>
    <span>Information Systems Department, Inabanga College of Arts and Sciences</span>
    <a href="<?= e(url('admin/login.php')) ?>" class="admin-link">Landlord sign in</a>
  </div>
</footer>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

/* ==================================================================
 *  ADMIN PANEL
 * ================================================================== */
function admin_header(string $title, string $active = '', array $opts = []): void
{
    $site = setting('site_name', 'Pajuleras Boarding House');
    $admin = $GLOBALS['ADMIN'] ?? ['full_name' => 'Landlord', 'username' => 'admin'];
    $unread = 0;
    try {
        $unread = (int)fetch_val("SELECT COUNT(*) FROM inquiries WHERE admin_unread = 1 AND status <> 'closed'");
    } catch (Throwable $e) {
    }
    $nav = [
        'dashboard'    => ['Dashboard', 'admin/index.php'],
        'inquiries'    => ['Inquiries', 'admin/inquiries.php'],
        'reservations' => ['Reservations', 'admin/reservations.php'],
        'tenants'      => ['Tenants', 'admin/tenants.php'],
        'rooms'        => ['Rooms', 'admin/rooms.php'],
        'payments'     => ['Payments', 'admin/payments.php'],
        'reports'      => ['Reports', 'admin/reports.php'],
        'settings'     => ['Settings', 'admin/settings.php'],
        'activity'     => ['Activity log', 'admin/activity.php'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> | Admin | <?= e($site) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin">
<a class="skip" href="#main">Skip to content</a>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="side-brand" href="<?= e(url('admin/')) ?>">
      <span class="brand-mark" aria-hidden="true"></span>
      <span><strong><?= e($site) ?></strong><small>Management system</small></span>
    </a>
    <nav class="side-nav" aria-label="Admin">
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>>
          <?= e($label) ?>
          <?php if ($key === 'inquiries' && $unread > 0): ?><span class="count"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">View public website</a>
    </div>
  </aside>
  <div class="content">
    <header class="topbar">
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="sidebar" data-side-toggle>Menu</button>
      <div class="topbar-title"><?= e($title) ?></div>
      <div class="topbar-user">
        <span><?= e($admin['full_name']) ?></span>
        <form method="post" action="<?= e(url('admin/logout.php')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-ghost" type="submit">Sign out</button>
        </form>
      </div>
    </header>
    <main id="main" class="page">
      <?php render_flashes(); ?>
<?php
}

function admin_footer(): void
{
    ?>
    </main>
  </div>
</div>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** Page heading with optional action buttons (HTML passed in $actions is trusted). */
function page_head(string $title, string $sub = '', string $actions = ''): void
{
    echo '<div class="page-head"><div><h1>' . e($title) . '</h1>' . ($sub !== '' ? '<p class="sub">' . e($sub) . '</p>' : '') . '</div>';
    if ($actions !== '') {
        echo '<div class="page-actions">' . $actions . '</div>';
    }
    echo '</div>';
}
