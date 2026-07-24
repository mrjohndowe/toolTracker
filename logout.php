<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$user = current_user();

if ($user !== null) {
    audit_log('logout', isset($user['id']) ? (int)$user['id'] : null);
}

session_unset();
session_destroy();
session_start();

flash('success', 'You have been logged out.');
redirect('/login.php');
