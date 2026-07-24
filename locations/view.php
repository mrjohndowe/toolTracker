<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('/locations/index.php');
}

$stmt = db()->prepare('SELECT * FROM locations WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$location = $stmt->fetch();

if (!is_array($location)) {
    flash('danger', 'Location not found.');
    redirect('/locations/index.php');
}

$toolStmt = db()->prepare(
    'SELECT
        t.id, t.internal_id, t.name, t.status, t.tool_condition,
        sb.name AS bin_name
     FROM tools t
     LEFT JOIN storage_bins sb ON sb.id = t.storage_bin_id
     WHERE t.location_id = ? AND t.active = 1
     ORDER BY t.name'
);
$toolStmt->execute([$id]);
$tools = $toolStmt->fetchAll();

$binStmt = db()->prepare(
    'SELECT sb.*, COUNT(t.id) AS tool_count
     FROM storage_bins sb
     LEFT JOIN tools t ON t.storage_bin_id = sb.id AND t.active = 1
     WHERE sb.location_id = ?
     GROUP BY sb.id
     ORDER BY sb.name'
);
$binStmt->execute([$id]);
$bins = $binStmt->fetchAll();

$pageTitle = (string)$location['name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1><?= e((string)$location['name']) ?></h1>
    <a class="btn" href="<?= BASE_URL ?>/transfers/create.php?from_location_id=<?= (int)$location['id'] ?>">New Transfer</a>
</div>

<div class="grid">
    <div class="card">
        <h2>Location Details</h2>
        <p><strong>Code:</strong> <?= e((string)$location['code']) ?></p>
        <p><strong>Address:</strong><br>
            <?= e((string)($location['address_line1'] ?? '')) ?><br>
            <?= e((string)($location['address_line2'] ?? '')) ?><br>
            <?= e(trim(
                (string)($location['city'] ?? '') . ', ' .
                (string)($location['state'] ?? '') . ' ' .
                (string)($location['postal_code'] ?? '')
            )) ?>
        </p>
        <p><strong>Phone:</strong> <?= e((string)($location['phone'] ?? '')) ?></p>
        <p><strong>Email:</strong> <?= e((string)($location['email'] ?? '')) ?></p>
    </div>

    <div class="card">
        <h2>Storage Bins</h2>
        <table class="table">
            <thead><tr><th>Bin</th><th>Code</th><th>Tools</th></tr></thead>
            <tbody>
            <?php foreach ($bins as $bin): ?>
                <tr>
                    <td><?= e((string)$bin['name']) ?></td>
                    <td><?= e((string)$bin['code']) ?></td>
                    <td><?= (int)$bin['tool_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Inventory</h2>
    <table class="table">
        <thead><tr><th>Tool</th><th>Internal ID</th><th>Bin</th><th>Status</th><th>Condition</th></tr></thead>
        <tbody>
        <?php foreach ($tools as $tool): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/tools/view.php?id=<?= (int)$tool['id'] ?>"><?= e((string)$tool['name']) ?></a></td>
                <td><?= e((string)$tool['internal_id']) ?></td>
                <td><?= e((string)($tool['bin_name'] ?? 'Unassigned')) ?></td>
                <td><?= e((string)$tool['status']) ?></td>
                <td><?= e((string)$tool['tool_condition']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
