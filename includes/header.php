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
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.webmanifest">
    <meta name="theme-color" content="#1f2937">
</head>
<body>
<script>
    window.TOOLTRACK_BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/pwa-register.js"></script>
<header class="topbar">
    <div class="brand"><?= e(APP_NAME) ?></div>

    <?php if ($user !== null): ?>
        <nav>
            <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>/mobile/index.php">Mobile</a>
            <a href="<?= BASE_URL ?>/tools/">Tools</a>

            <a href="<?= BASE_URL ?>/employees/index.php">Employees</a>
            <a href="<?= BASE_URL ?>/checkout/index.php">Checkout</a>
            <a href="<?= BASE_URL ?>/checkout/history.php">History</a>
            <a href="<?= BASE_URL ?>/maintenance/index.php">Maintenance</a>
            
            
           
            
            
            
            <?php if (($user['role_name'] ?? '') === 'Administrator'): ?>
                <a href="<?= BASE_URL ?>/admin/users.php">Users</a>     
                <a href="<?= BASE_URL ?>/maintenance/history.php">Maintenance History</a>
                <a href="<?= BASE_URL ?>/reports/tool_history.php">Tool History</a>
                <a href="<?= BASE_URL ?>/reports/index.php">Reports</a>
                <a href="<?= BASE_URL ?>/inspections/history.php">Inspection History</a>
                <a href="<?= BASE_URL ?>/admin/inspection_questions.php">Inspection Questions</a>
            <?php endif; ?>

            <?php if (($user['role_name'] ?? '') === 'Super Administrator'): ?>
                <a href="<?= BASE_URL ?>/locations/index.php">Locations</a>
                <a href="<?= BASE_URL ?>/notifications/index.php">Notifications</a>
                <a href="<?= BASE_URL ?>/reports/location_inventory.php">Location Inventory</a>
                <a href="<?= BASE_URL ?>/reports/transfer_history.php">Transfer History</a>
                <a href="<?= BASE_URL ?>/reports/custody_history.php">Chain of Custody</a>
                <a href="<?= BASE_URL ?>/transfers/index.php">Transfers</a>
                
                <a href="<?= BASE_URL ?>/admin/api_tokens.php">API Tokens</a>
                <a href="<?= BASE_URL ?>/admin/api_logs.php">API Logs</a>
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
