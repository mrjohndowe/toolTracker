<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('/transfers/index.php');
}

$transfer = find_transfer($id);

if (!$transfer) {
    flash('danger', 'Transfer not found.');
    redirect('/transfers/index.php');
}

$items = transfer_items($id);
$destinationBins = storage_bins_for_location((int)$transfer['to_location_id']);

$pageTitle = (string)$transfer['transfer_number'];
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1><?= e((string)$transfer['transfer_number']) ?></h1>
    <a class="btn secondary" href="<?= BASE_URL ?>/transfers/index.php">Back</a>
</div>

<div class="grid">
    <div class="card">
        <h2>Transfer Details</h2>
        <p><strong>From:</strong> <?= e((string)$transfer['from_location_name']) ?></p>
        <p><strong>To:</strong> <?= e((string)$transfer['to_location_name']) ?></p>
        <p><strong>Status:</strong> <?= e((string)$transfer['status']) ?></p>
        <p><strong>Requested by:</strong> <?= e((string)($transfer['requested_by_name'] ?? '')) ?></p>
        <p><strong>Approved by:</strong> <?= e((string)($transfer['approved_by_name'] ?? '')) ?></p>
        <p><strong>Received by:</strong> <?= e((string)($transfer['received_by_name'] ?? '')) ?></p>
    </div>

    <div class="card">
        <h2>Reason and Notes</h2>
        <p><?= nl2br(e((string)($transfer['reason'] ?? ''))) ?></p>
        <p><?= nl2br(e((string)($transfer['notes'] ?? ''))) ?></p>
    </div>
</div>

<div class="card">
    <h2>Items</h2>
    <table class="table">
        <thead><tr><th>Tool</th><th>From Bin</th><th>To Bin</th><th>Condition Out</th><th>Condition In</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e((string)$item['tool_name']) ?><br><span class="muted"><?= e((string)$item['internal_id']) ?></span></td>
                <td><?= e((string)($item['from_bin_name'] ?? 'Unassigned')) ?></td>
                <td><?= e((string)($item['to_bin_name'] ?? 'Unassigned')) ?></td>
                <td><?= e((string)($item['condition_out'] ?? '')) ?></td>
                <td><?= e((string)($item['condition_in'] ?? '')) ?></td>
                <td><?= e((string)$item['item_status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Actions</h2>

    <?php if ($transfer['status'] === 'Pending Approval'): ?>
        <form method="post" action="<?= BASE_URL ?>/transfers/action.php" class="actions">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="transfer_id" value="<?= (int)$transfer['id'] ?>">
            <button class="btn" name="action" value="approve">Approve</button>
            <button class="btn secondary" name="action" value="reject">Reject</button>
        </form>
    <?php elseif ($transfer['status'] === 'Approved'): ?>
        <form method="post" action="<?= BASE_URL ?>/transfers/action.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="transfer_id" value="<?= (int)$transfer['id'] ?>">
            <button class="btn" name="action" value="ship">Mark In Transit</button>
        </form>
    <?php elseif ($transfer['status'] === 'In Transit'): ?>
        <form method="post" action="<?= BASE_URL ?>/transfers/action.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="transfer_id" value="<?= (int)$transfer['id'] ?>">

            <div class="form-group">
                <label>Destination Bin</label>
                <select name="to_storage_bin_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($destinationBins as $bin): ?>
                        <option value="<?= (int)$bin['id'] ?>"><?= e((string)$bin['name']) ?> — <?= e((string)$bin['code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Condition Received</label>
                <select name="condition_in">
                    <option>Excellent</option>
                    <option selected>Good</option>
                    <option>Fair</option>
                    <option>Poor</option>
                </select>
            </div>

            <button class="btn" name="action" value="receive">Receive Transfer</button>
        </form>
    <?php else: ?>
        <p>No actions are available for this transfer.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
