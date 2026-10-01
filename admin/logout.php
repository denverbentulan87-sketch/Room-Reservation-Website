<?php
require __DIR__ . '/../includes/bootstrap.php';
if (is_post()) {
    csrf_verify();
    if (is_admin()) {
        log_activity('logout', 'admin', 1, 'Signed out');
    }
    admin_forget_session();
}
redirect('admin/login.php');
