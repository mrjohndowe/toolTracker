<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
$user = current_user();
$roleName = (string)($user['role_name'] ?? '');
$isAdministrator = in_array($roleName, ['Administrator', 'Super Administrator'], true);
$isSuperAdministrator = $roleName === 'Super Administrator';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/app.css">
    <link rel="manifest" href="<?= rtrim(BASE_URL, '/') ?>/manifest.webmanifest">
    <meta name="theme-color" content="#1f2937">
</head>
<body>
<script>
    window.TOOLTRACK_BASE_URL = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
</script>
<script src="<?= rtrim(BASE_URL, '/') ?>/assets/js/pwa-register.js" defer></script>

<header class="topbar">
    <div class="topbar-row">
        <a class="brand" href="<?= rtrim(BASE_URL, '/') ?>/dashboard.php"><?= e(APP_NAME) ?></a>

        <?php if ($user !== null): ?>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-navigation">
                <span class="nav-toggle-label">Menu</span>
                <span class="nav-toggle-icon" aria-hidden="true">☰</span>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($user !== null): ?>
        <nav id="main-navigation" class="main-nav" aria-label="Main navigation">
            <a href="<?= rtrim(BASE_URL, '/') ?>/dashboard.php">Dashboard</a>
            <a href="<?= rtrim(BASE_URL, '/') ?>/mobile/index.php">Mobile</a>

            <div class="nav-dropdown">
                <button class="nav-dropdown-toggle" type="button" aria-expanded="false">
                    Inventory <span aria-hidden="true">▾</span>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="<?= rtrim(BASE_URL, '/') ?>/tools/">Tools</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/employees/index.php">Employees</a>
                    <?php if ($isSuperAdministrator): ?>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/locations/index.php">Locations</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/location_inventory.php">Location Inventory</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nav-dropdown">
                <button class="nav-dropdown-toggle" type="button" aria-expanded="false">
                    Checkouts <span aria-hidden="true">▾</span>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="<?= rtrim(BASE_URL, '/') ?>/checkout/index.php">Open Checkouts</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/checkout/new.php">New Checkout</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/checkout/return.php">Check In</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/checkout/history.php">Checkout History</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/checkout/scan_history.php">Scan History</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/inspections/history.php">Inspection History</a>
                </div>
            </div>

            <div class="nav-dropdown">
                <button class="nav-dropdown-toggle" type="button" aria-expanded="false">
                    Maintenance <span aria-hidden="true">▾</span>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="<?= rtrim(BASE_URL, '/') ?>/maintenance/index.php">Work Orders</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/maintenance/schedules.php">Schedules</a>
                    <a href="<?= rtrim(BASE_URL, '/') ?>/maintenance/calibration.php">Calibration</a>
                    <?php if ($isAdministrator): ?>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/maintenance/history.php">Maintenance History</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/maintenance/types.php">Maintenance Types</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isAdministrator): ?>
                <div class="nav-dropdown">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false">
                        Reports <span aria-hidden="true">▾</span>
                    </button>
                    <div class="nav-dropdown-menu">
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/index.php">Reports Dashboard</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/tool_history.php">Tool History</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/employee_history.php">Employee History</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/tool_utilization.php">Tool Utilization</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/maintenance_costs.php">Maintenance Costs</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/reports/overdue.php">Overdue Tools</a>
                        <?php if ($isSuperAdministrator): ?>
                            <a href="<?= rtrim(BASE_URL, '/') ?>/reports/transfer_history.php">Transfer History</a>
                            <a href="<?= rtrim(BASE_URL, '/') ?>/reports/custody_history.php">Chain of Custody</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="nav-dropdown nav-dropdown-right">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false">
                        Administration <span aria-hidden="true">▾</span>
                    </button>
                    <div class="nav-dropdown-menu">
                        <a href="<?= rtrim(BASE_URL, '/') ?>/admin/users.php">Users</a>
                        <a href="<?= rtrim(BASE_URL, '/') ?>/admin/inspection_questions.php">Inspection Questions</a>
                        <?php if ($isSuperAdministrator): ?>
                            <a href="<?= rtrim(BASE_URL, '/') ?>/notifications/index.php">Notifications</a>
                            <a href="<?= rtrim(BASE_URL, '/') ?>/transfers/index.php">Transfers</a>
                            <a href="<?= rtrim(BASE_URL, '/') ?>/admin/api_tokens.php">API Tokens</a>
                            <a href="<?= rtrim(BASE_URL, '/') ?>/admin/api_logs.php">API Logs</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <a class="logout-link" href="<?= rtrim(BASE_URL, '/') ?>/logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>

<script>
(function () {
    const nav = document.getElementById('main-navigation');
    const navToggle = document.querySelector('.nav-toggle');
    const dropdowns = Array.from(document.querySelectorAll('.nav-dropdown'));

    function closeDropdown(dropdown) {
        dropdown.classList.remove('open');
        const button = dropdown.querySelector('.nav-dropdown-toggle');
        if (button) button.setAttribute('aria-expanded', 'false');
    }

    function closeAll(except) {
        dropdowns.forEach(function (dropdown) {
            if (dropdown !== except) closeDropdown(dropdown);
        });
    }

    if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    dropdowns.forEach(function (dropdown) {
        const button = dropdown.querySelector('.nav-dropdown-toggle');
        if (!button) return;

        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = !dropdown.classList.contains('open');
            closeAll(dropdown);
            dropdown.classList.toggle('open', willOpen);
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        dropdown.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDropdown(dropdown);
                button.focus();
            }
        });
    });

    document.addEventListener('click', function () {
        closeAll(null);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900 && nav && navToggle) {
            nav.classList.remove('open');
            navToggle.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>

<main class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert <?= e((string)($flash['type'] ?? 'success')) ?>">
            <?= e((string)($flash['message'] ?? '')) ?>
        </div>
    <?php endforeach; ?>
