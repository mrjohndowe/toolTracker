<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('Administrator');

$users = db()->query(
    'SELECT
        u.id,
        u.username,
        u.first_name,
        u.last_name,
        u.email,
        u.active,
        u.last_login,
        r.name AS role_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     ORDER BY u.last_name, u.first_name'
)->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>User Management</h1>
    <a class="btn" href="<?= BASE_URL ?>/admin/user_form.php">Add User</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td><?= e((string)$row['first_name'] . ' ' . (string)$row['last_name']) ?></td>
                <td><?= e((string)$row['username']) ?></td>
                <td><?= e((string)$row['role_name']) ?></td>
                <td>
                    <span class="badge">
                        <?= (int)$row['active'] === 1 ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td><?= e((string)($row['last_login'] ?? 'Never')) ?></td>
                <td>
                    <a class="btn secondary" href="<?= BASE_URL ?>/admin/user_form.php?id=<?= (int)$row['id'] ?>">
                        Edit
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
