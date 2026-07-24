<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$stats = [
    'users' => (int)db()->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn(),
    'admins' => (int)db()->query(
        "SELECT COUNT(*)
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE r.name = 'Administrator' AND u.active = 1"
    )->fetchColumn(),
    'logins_today' => (int)db()->query(
        "SELECT COUNT(*)
         FROM activity_logs
         WHERE action = 'login' AND DATE(created_at) = CURDATE()"
    )->fetchColumn(),
    'activity_today' => (int)db()->query(
        'SELECT COUNT(*)
         FROM activity_logs
         WHERE DATE(created_at) = CURDATE()'
    )->fetchColumn(),
];

$activity = db()->query(
    'SELECT a.action, a.details, a.created_at, u.username
     FROM activity_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.id DESC
     LIMIT 10'
)->fetchAll();

$user = current_user();
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<h1>Dashboard</h1>
<p>Welcome, <?= e((string)$user['first_name'] . ' ' . (string)$user['last_name']) ?>.</p>

<div class="grid">
    <div class="card">
        <div class="muted">Active Users</div>
        <div class="stat"><?= $stats['users'] ?></div>
    </div>

    <div class="card">
        <div class="muted">Administrators</div>
        <div class="stat"><?= $stats['admins'] ?></div>
    </div>

    <div class="card">
        <div class="muted">Logins Today</div>
        <div class="stat"><?= $stats['logins_today'] ?></div>
    </div>

    <div class="card">
        <div class="muted">Activity Today</div>
        <div class="stat"><?= $stats['activity_today'] ?></div>
    </div>
</div>

<div class="card">
    <h2>Foundation Installed</h2>
    <p>
        This release includes authentication, roles, user administration,
        session security, CSRF protection, login throttling, and audit logging.
    </p>
</div>

<div class="card">
    <h2>Recent Activity</h2>

    <table class="table">
        <thead>
            <tr>
                <th>User</th>
                <th>Action</th>
                <th>Details</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($activity as $row): ?>
            <tr>
                <td><?= e((string)($row['username'] ?? 'System')) ?></td>
                <td><?= e((string)$row['action']) ?></td>
                <td><?= e((string)($row['details'] ?? '')) ?></td>
                <td><?= e((string)$row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
