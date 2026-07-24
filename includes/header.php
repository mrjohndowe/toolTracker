<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<header class="topbar">
    <div class="brand"><?= e(APP_NAME) ?></div>

    <?php if ($user !== null): ?>
        <nav>
            <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>

            <a href="<?= BASE_URL ?>/tools/">Tools</a>

            <a href="<?= BASE_URL ?>/employees/index.php">Employees</a>
            <a href="<?= BASE_URL ?>/checkout/index.php">Checkout</a>
            <a href="<?= BASE_URL ?>/checkout/history.php">History</a>
            <a href="<?= BASE_URL ?>/maintenance/index.php">Maintenance</a>
            <a href="<?= BASE_URL ?>/maintenance/history.php">Maintenance History</a>

            <?php if (($user['role_name'] ?? '') === 'Administrator'): ?>
                <a href="<?= BASE_URL ?>/admin/users.php">Users</a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>

<main class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert <?= e((string)($flash['type'] ?? 'success')) ?>">
            <?= e((string)($flash['message'] ?? '')) ?>
        </div>
    <?php endforeach; ?>
