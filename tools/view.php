<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid tool ID.');
}

$tool = find_tool($id);
if ($tool === null) {
    http_response_code(404);
    exit('Tool not found.');
}

$photos = db()->prepare('SELECT * FROM tool_photos WHERE tool_id = ? ORDER BY is_primary DESC, id DESC');
$photos->execute([$id]);
$photos = $photos->fetchAll();

$history = db()->prepare(
    'SELECT h.*, u.username
     FROM tool_status_history h
     LEFT JOIN users u ON u.id = h.changed_by
     WHERE h.tool_id = ?
     ORDER BY h.id DESC'
);
$history->execute([$id]);
$history = $history->fetchAll();

$pageTitle = $tool['name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <div>
        <h1><?= e((string)$tool['name']) ?></h1>
        <div class="muted"><?= e((string)$tool['internal_id']) ?></div>
    </div>

    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/tools/label.php?id=<?= $id ?>" target="_blank">Print Label</a>
        <a class="btn" href="<?= BASE_URL ?>/tools/edit.php?id=<?= $id ?>">Edit Tool</a>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2>Tool Information</h2>
        <p><strong>Barcode:</strong> <?= e((string)$tool['barcode']) ?></p>
        <p><strong>Serial:</strong> <?= e((string)($tool['serial_number'] ?? '')) ?></p>
        <p><strong>Manufacturer:</strong> <?= e((string)($tool['manufacturer'] ?? '')) ?></p>
        <p><strong>Model:</strong> <?= e((string)($tool['model'] ?? '')) ?></p>
        <p><strong>Category:</strong> <?= e((string)($tool['category_name'] ?? '')) ?></p>
        <p><strong>Location:</strong> <?= e((string)($tool['location_name'] ?? '')) ?></p>
    </div>

    <div class="card">
        <h2>Status</h2>
        <p><strong>Status:</strong> <span class="badge"><?= e((string)$tool['status']) ?></span></p>
        <p><strong>Condition:</strong> <?= e((string)$tool['tool_condition']) ?></p>
        <p><strong>Purchase Date:</strong> <?= e((string)($tool['purchase_date'] ?? '')) ?></p>
        <p><strong>Replacement Value:</strong> $<?= number_format((float)$tool['replacement_value'], 2) ?></p>
        <p><strong>Active:</strong> <?= (int)$tool['active'] === 1 ? 'Yes' : 'No' ?></p>
    </div>
</div>

<div class="card">
    <h2>Notes</h2>
    <p><?= nl2br(e((string)($tool['notes'] ?? 'No notes.'))) ?></p>
</div>

<div class="card">
    <div class="actions" style="justify-content:space-between">
        <h2>Photos</h2>
        <a class="btn" href="<?= BASE_URL ?>/tools/upload_photo.php?id=<?= $id ?>">Upload Photo</a>
    </div>

    <?php if (!$photos): ?>
        <p>No photos uploaded.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($photos as $photo): ?>
                <div>
                    <img src="<?= BASE_URL ?>/uploads/tools/<?= e((string)$photo['filename']) ?>"
                         alt="Tool photo"
                         style="width:100%;max-height:240px;object-fit:cover;border-radius:8px">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Status History</h2>
    <table class="table">
        <thead>
        <tr><th>Date</th><th>Status</th><th>Condition</th><th>User</th><th>Notes</th></tr>
        </thead>
        <tbody>
        <?php foreach ($history as $row): ?>
            <tr>
                <td><?= e((string)$row['created_at']) ?></td>
                <td><?= e((string)$row['old_status']) ?> → <?= e((string)$row['new_status']) ?></td>
                <td><?= e((string)$row['old_condition']) ?> → <?= e((string)$row['new_condition']) ?></td>
                <td><?= e((string)($row['username'] ?? 'System')) ?></td>
                <td><?= e((string)($row['notes'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
