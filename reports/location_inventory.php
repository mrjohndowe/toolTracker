<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

$locationId = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT) ?: null;
$locations = locations_list();

$rows = [];

if ($locationId) {
    $stmt = db()->prepare(
        'SELECT
            t.id, t.internal_id, t.barcode, t.name,
            t.status, t.tool_condition,
            sb.name AS bin_name, sb.code AS bin_code,
            c.name AS category_name
         FROM tools t
         LEFT JOIN storage_bins sb ON sb.id = t.storage_bin_id
         LEFT JOIN tool_categories c ON c.id = t.category_id
         WHERE t.location_id = ? AND t.active = 1
         ORDER BY sb.name, t.name'
    );
    $stmt->execute([$locationId]);
    $rows = $stmt->fetchAll();
}

$pageTitle = 'Location Inventory';
require __DIR__ . '/../includes/header.php';
?>
<h1>Location Inventory</h1>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Location</label>
            <select name="location_id" required>
                <option value="">Select location</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?= (int)$location['id'] ?>" <?= $locationId === (int)$location['id'] ? 'selected' : '' ?>>
                        <?= e((string)$location['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn">Run Report</button>
    </form>
</div>

<?php if ($locationId): ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Tool</th><th>Internal ID</th><th>Category</th><th>Bin</th><th>Status</th><th>Condition</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/tools/view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['name']) ?></a></td>
                <td><?= e((string)$row['internal_id']) ?></td>
                <td><?= e((string)($row['category_name'] ?? '')) ?></td>
                <td><?= e((string)($row['bin_name'] ?? 'Unassigned')) ?></td>
                <td><?= e((string)$row['status']) ?></td>
                <td><?= e((string)$row['tool_condition']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
