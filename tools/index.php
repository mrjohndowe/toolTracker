<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$category = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$location = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT) ?: null;

$sql = '
    SELECT
        t.id, t.internal_id, t.barcode, t.serial_number, t.name,
        t.manufacturer, t.model, t.status, t.tool_condition, t.active,
        c.name AS category_name,
        l.name AS location_name,
        (SELECT filename FROM tool_photos p WHERE p.tool_id = t.id ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS photo
    FROM tools t
    LEFT JOIN tool_categories c ON c.id = t.category_id
    LEFT JOIN tool_locations l ON l.id = t.location_id
    WHERE 1=1
';
$params = [];

if ($q !== '') {
    $sql .= ' AND (
        t.internal_id LIKE ? OR t.barcode LIKE ? OR t.serial_number LIKE ?
        OR t.name LIKE ? OR t.manufacturer LIKE ? OR t.model LIKE ?
    )';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($status !== '' && in_array($status, tool_statuses(), true)) {
    $sql .= ' AND t.status = ?';
    $params[] = $status;
}

if ($category !== null) {
    $sql .= ' AND t.category_id = ?';
    $params[] = $category;
}

if ($location !== null) {
    $sql .= ' AND t.location_id = ?';
    $params[] = $location;
}

$sql .= ' ORDER BY t.active DESC, t.name ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tools = $stmt->fetchAll();

$pageTitle = 'Tools';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Tool Inventory</h1>
    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/tools/categories.php">Categories</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/tools/locations.php">Locations</a>
        <a class="btn" href="<?= BASE_URL ?>/tools/add.php">Add Tool</a>
    </div>
</div>

<div class="card">
    <form method="get">
        <div class="grid">
            <div class="form-group">
                <label>Search</label>
                <input name="q" value="<?= e($q) ?>" placeholder="ID, barcode, serial, name...">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (tool_statuses() as $item): ?>
                        <option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>>
                            <?= e($item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">All categories</option>
                    <?php foreach (tool_categories() as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $category === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= e((string)$item['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Location</label>
                <select name="location_id">
                    <option value="">All locations</option>
                    <?php foreach (tool_locations() as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= $location === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= e((string)$item['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="actions">
            <button class="btn">Filter</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/tools/index.php">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Tool</th>
            <th>Internal ID</th>
            <th>Serial</th>
            <th>Category</th>
            <th>Location</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$tools): ?>
            <tr><td colspan="7">No tools found.</td></tr>
        <?php endif; ?>

        <?php foreach ($tools as $tool): ?>
            <tr>
                <td>
                    <strong><?= e((string)$tool['name']) ?></strong><br>
                    <span class="muted"><?= e(trim((string)$tool['manufacturer'] . ' ' . (string)$tool['model'])) ?></span>
                </td>
                <td><?= e((string)$tool['internal_id']) ?></td>
                <td><?= e((string)($tool['serial_number'] ?? '')) ?></td>
                <td><?= e((string)($tool['category_name'] ?? '')) ?></td>
                <td><?= e((string)($tool['location_name'] ?? '')) ?></td>
                <td>
                    <span class="badge"><?= e((string)$tool['status']) ?></span>
                    <?php if ((int)$tool['active'] !== 1): ?>
                        <span class="badge">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a class="btn secondary" href="<?= BASE_URL ?>/tools/view.php?id=<?= (int)$tool['id'] ?>">View</a>
                    <a class="btn secondary" href="<?= BASE_URL ?>/tools/edit.php?id=<?= (int)$tool['id'] ?>">Edit</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
