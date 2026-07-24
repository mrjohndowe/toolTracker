<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;

    if ($locationId && $userId) {
        if ($isPrimary) {
            db()->prepare(
                'UPDATE location_managers SET is_primary = 0 WHERE location_id = ?'
            )->execute([$locationId]);
        }

        db()->prepare(
            'INSERT INTO location_managers
             (location_id, user_id, is_primary)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)'
        )->execute([$locationId, $userId, $isPrimary]);

        flash('success', 'Location manager assigned.');
    }

    redirect('/locations/managers.php');
}

$locations = locations_list();

$users = db()->query(
    'SELECT id, username, role_id
     FROM users
     WHERE active = 1
     ORDER BY username'
)->fetchAll();

$rows = db()->query(
    'SELECT
        lm.id, lm.is_primary,
        l.name AS location_name,
        u.username, u.role_id
     FROM location_managers lm
     INNER JOIN locations l ON l.id = lm.location_id
     INNER JOIN users u ON u.id = lm.user_id
     ORDER BY l.name, lm.is_primary DESC, u.username'
)->fetchAll();

$pageTitle = 'Location Managers';
require __DIR__ . '/../includes/header.php';
?>
<h1>Location Managers</h1>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Location</label>
                <select name="location_id" required>
                    <option value="">Select location</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?= (int)$location['id'] ?>"><?= e((string)$location['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>User</label>
                <select name="user_id" required>
                    <option value="">Select user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int)$user['id'] ?>"><?= e((string)$user['username']) ?> — <?= e((string)$user['role_id']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <label>
            <input type="checkbox" name="is_primary" style="width:auto">
            Primary manager
        </label>

        <br><br>
        <button class="btn">Assign Manager</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>Location</th><th>User</th><th>Role</th><th>Primary</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['location_name']) ?></td>
                <td><?= e((string)$row['username']) ?></td>
                <td><?= e((string)$row['role_id']) ?></td>
                <td><?= (int)$row['is_primary'] === 1 ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
