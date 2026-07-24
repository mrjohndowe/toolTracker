<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$workOrder = $id ? find_work_order($id) : null;

if ($workOrder === null) {
    http_response_code(404);
    exit('Work order not found.');
}

$attachments = db()->prepare(
    'SELECT * FROM work_order_attachments
     WHERE work_order_id = ?
     ORDER BY id DESC'
);
$attachments->execute([$id]);
$attachments = $attachments->fetchAll();

$history = db()->prepare(
    'SELECT h.*, u.username
     FROM work_order_status_history h
     LEFT JOIN users u ON u.id = h.changed_by
     WHERE h.work_order_id = ?
     ORDER BY h.id DESC'
);
$history->execute([$id]);
$history = $history->fetchAll();

$pageTitle = $workOrder['work_order_number'];
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <div>
        <h1><?= e((string)$workOrder['work_order_number']) ?></h1>
        <div class="muted"><?= e((string)$workOrder['title']) ?></div>
    </div>

    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/upload_attachment.php?id=<?= $id ?>">Add Attachment</a>
        <a class="btn" href="<?= BASE_URL ?>/maintenance/work_order_update.php?id=<?= $id ?>">Update Work Order</a>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2>Tool</h2>
        <p><strong><?= e((string)$workOrder['tool_name']) ?></strong></p>
        <p><?= e((string)$workOrder['internal_id']) ?></p>
        <p>Tool status: <?= e((string)$workOrder['tool_status']) ?></p>
    </div>

    <div class="card">
        <h2>Work Order</h2>
        <p><strong>Type:</strong> <?= e((string)($workOrder['maintenance_type_name'] ?? '')) ?></p>
        <p><strong>Priority:</strong> <?= e((string)$workOrder['priority']) ?></p>
        <p><strong>Status:</strong> <span class="badge"><?= e((string)$workOrder['status']) ?></span></p>
        <p><strong>Due:</strong> <?= e((string)($workOrder['due_date'] ?? '')) ?></p>
    </div>
</div>

<div class="card">
    <h2>Description</h2>
    <p><?= nl2br(e((string)($workOrder['description'] ?? 'No description.'))) ?></p>
</div>

<div class="grid">
    <div class="card">
        <h2>Assignment</h2>
        <p><strong>Assigned To:</strong> <?= e((string)($workOrder['assigned_to'] ?? '')) ?></p>
        <p><strong>Vendor:</strong> <?= e((string)($workOrder['vendor_name'] ?? '')) ?></p>
        <p><strong>Opened:</strong> <?= e((string)$workOrder['opened_date']) ?></p>
        <p><strong>Completed:</strong> <?= e((string)($workOrder['completed_date'] ?? '')) ?></p>
    </div>

    <div class="card">
        <h2>Costs</h2>
        <p><strong>Labor:</strong> $<?= number_format((float)$workOrder['labor_cost'], 2) ?></p>
        <p><strong>Parts:</strong> $<?= number_format((float)$workOrder['parts_cost'], 2) ?></p>
        <p><strong>Other:</strong> $<?= number_format((float)$workOrder['other_cost'], 2) ?></p>
        <p><strong>Total:</strong> $<?= number_format(maintenance_total_cost($workOrder), 2) ?></p>
    </div>
</div>

<div class="card">
    <h2>Completion Notes</h2>
    <p><?= nl2br(e((string)($workOrder['completion_notes'] ?? ''))) ?></p>
</div>

<div class="card">
    <h2>Attachments</h2>

    <?php if (!$attachments): ?>
        <p>No attachments.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($attachments as $attachment): ?>
                <li>
                    <a href="<?= BASE_URL ?>/uploads/maintenance/<?= e((string)$attachment['filename']) ?>" target="_blank">
                        <?= e((string)($attachment['original_name'] ?? $attachment['filename'])) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Status History</h2>

    <table class="table">
        <thead><tr><th>Date</th><th>Status</th><th>User</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($history as $row): ?>
            <tr>
                <td><?= e((string)$row['created_at']) ?></td>
                <td><?= e((string)($row['old_status'] ?? '')) ?> → <?= e((string)$row['new_status']) ?></td>
                <td><?= e((string)($row['username'] ?? 'System')) ?></td>
                <td><?= e((string)($row['notes'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
