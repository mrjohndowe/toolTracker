<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$rows = db()->query(
    'SELECT
        l.*,
        COUNT(DISTINCT t.id) AS tool_count,
        COUNT(DISTINCT sb.id) AS bin_count,
        COUNT(DISTINCT lm.id) AS manager_count
     FROM locations l
     LEFT JOIN tools t ON t.location_id = l.id AND t.active = 1
     LEFT JOIN storage_bins sb ON sb.location_id = l.id AND sb.active = 1
     LEFT JOIN location_managers lm ON lm.location_id = l.id
     GROUP BY l.id
     ORDER BY l.name'
)->fetchAll();

$pageTitle = 'Locations';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Locations</h1>
    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/locations/bins.php">Storage Bins</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/locations/managers.php">Managers</a>
        <a class="btn" href="<?= BASE_URL ?>/locations/add.php">New Location</a>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Location</th><th>Code</th><th>Tools</th><th>Bins</th><th>Managers</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['name']) ?></td>
                <td><?= e((string)$row['code']) ?></td>
                <td><?= (int)$row['tool_count'] ?></td>
                <td><?= (int)$row['bin_count'] ?></td>
                <td><?= (int)$row['manager_count'] ?></td>
                <td><?= (int)$row['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                <td><a class="btn secondary" href="<?= BASE_URL ?>/locations/view.php?id=<?= (int)$row['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
