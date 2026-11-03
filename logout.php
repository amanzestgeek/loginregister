<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Logout only via POST + CSRF so a link/image can't sign users out.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(current_user() ? 'dashboard.php' : 'login.php');
}

$token = $_POST['_csrf'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    redirect('dashboard.php');
}

logout_user();
flash('success', 'You have been signed out.');
redirect('login.php');
