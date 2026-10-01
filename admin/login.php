<?php
require __DIR__ . '/../includes/bootstrap.php';

if (is_admin()) {
    redirect('admin/');
}
$error = '';
$notice = $_SESSION['login_notice'] ?? '';
unset($_SESSION['login_notice']);
$username = '';

if (is_post()) {
    csrf_verify();
    $username = post_str('username', 50);
    [$ok, $msg] = admin_login($username, (string)($_POST['password'] ?? ''));
    if ($ok) {
        redirect('admin/');
    }
    $error = $msg;
}
$site = setting('site_name', 'Pajuleras Boarding House');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Landlord sign in | <?= e($site) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin login-page">
<main class="login-card">
  <div class="weave" aria-hidden="true"></div>
  <div class="login-body">
    <a class="brand" href="<?= e(url('')) ?>"><span class="brand-mark" aria-hidden="true"></span><span class="brand-text"><strong><?= e($site) ?></strong><small>Landlord sign in</small></span></a>
    <?php if ($notice): ?><div class="flash flash-info" role="status"><span><?= e($notice) ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="flash flash-error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?>
    <form method="post" action="<?= e(url('admin/login.php')) ?>" class="form" autocomplete="off">
      <?= csrf_field() ?>
      <label>Username
        <input type="text" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username" maxlength="50">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="btn btn-primary btn-lg" type="submit">Sign in</button>
    </form>
    <p class="muted small"><a href="<?= e(url('')) ?>">Back to the public website</a></p>
  </div>
</main>
</body>
</html>
