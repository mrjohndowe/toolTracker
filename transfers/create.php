<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

$locations = locations_list();
$fromLocationId = filter_input(INPUT_GET, 'from_location_id', FILTER_VALIDATE_INT) ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fromLocationId = filter_input(INPUT_POST, 'from_location_id', FILTER_VALIDATE_INT);
    $toLocationId = filter_input(INPUT_POST, 'to_location_id', FILTER_VALIDATE_INT);
    $reason = trim((string)($_POST['reason'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $toolIds = $_POST['tool_ids'] ?? [];

    if (
        !$fromLocationId ||
        !$toLocationId ||
        $fromLocationId === $toLocationId ||
        !is_array($toolIds) ||
        !$toolIds
    ) {
        flash('danger', 'Select two different locations and at least one tool.');
    } else {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $user = current_user();
            $number = generate_transfer_number();

            $pdo->prepare(
                'INSERT INTO transfer_requests
                 (transfer_number, from_location_id, to_location_id,
                  requested_by, status, reason, notes)
                 VALUES (?, ?, ?, ?, "Pending Approval", ?, ?)'
            )->execute([
                $number,
                $fromLocationId,
                $toLocationId,
                $user['id'] ?? null,
                $reason !== '' ? $reason : null,
                $notes !== '' ? $notes : null,
            ]);

            $transferId = (int)$pdo->lastInsertId();

            $toolStmt = $pdo->prepare(
                'SELECT id, location_id, storage_bin_id, tool_condition, status
                 FROM tools
                 WHERE id = ? AND location_id = ? AND active = 1
                 FOR UPDATE'
            );

            $insertItem = $pdo->prepare(
                'INSERT INTO transfer_items
                 (transfer_id, tool_id, from_storage_bin_id, condition_out)
                 VALUES (?, ?, ?, ?)'
            );

            foreach (array_unique(array_map('intval', $toolIds)) as $toolId) {
                $toolStmt->execute([$toolId, $fromLocationId]);
                $tool = $toolStmt->fetch();

                if (!is_array($tool)) {
                    throw new RuntimeException("Tool {$toolId} is not at the selected source location.");
                }

                if ($tool['status'] !== 'Available') {
                    throw new RuntimeException("Tool {$toolId} is not available for transfer.");
                }

                $insertItem->execute([
                    $transferId,
                    $toolId,
                    $tool['storage_bin_id'],
                    $tool['tool_condition'],
                ]);

                record_custody(
                    $toolId,
                    'Transfer Requested',
                    $fromLocationId,
                    $toLocationId,
                    $tool['storage_bin_id'] ? (int)$tool['storage_bin_id'] : null,
                    null,
                    $transferId,
                    $user['id'] ?? null,
                    'Transfer request ' . $number
                );
            }

            $pdo->commit();

            flash('success', 'Transfer request created.');
            redirect('/transfers/view.php?id=' . $transferId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('danger', $e->getMessage());
        }
    }
}

$tools = [];

if ($fromLocationId) {
    $stmt = db()->prepare(
        'SELECT id, internal_id, name, status, tool_condition
         FROM tools
         WHERE location_id = ? AND active = 1 AND status = "Available"
         ORDER BY name'
    );
    $stmt->execute([$fromLocationId]);
    $tools = $stmt->fetchAll();
}

$pageTitle = 'New Transfer';
require __DIR__ . '/../includes/header.php';
?>
<h1>New Transfer</h1>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Source Location</label>
            <select name="from_location_id" onchange="this.form.submit()">
                <option value="">Select source location</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?= (int)$location['id'] ?>" <?= $fromLocationId === (int)$location['id'] ? 'selected' : '' ?>>
                        <?= e((string)$location['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($fromLocationId): ?>
<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="from_location_id" value="<?= (int)$fromLocationId ?>">

        <div class="form-group">
            <label>Destination Location</label>
            <select name="to_location_id" required>
                <option value="">Select destination</option>
                <?php foreach ($locations as $location): ?>
                    <?php if ((int)$location['id'] === $fromLocationId) continue; ?>
                    <option value="<?= (int)$location['id'] ?>"><?= e((string)$location['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Reason</label>
            <input name="reason">
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="4" style="width:100%;padding:11px"></textarea>
        </div>

        <h2>Select Tools</h2>
        <?php foreach ($tools as $tool): ?>
            <label style="display:block;padding:8px 0;border-bottom:1px solid #ddd">
                <input type="checkbox" name="tool_ids[]" value="<?= (int)$tool['id'] ?>" style="width:auto">
                <?= e((string)$tool['name']) ?> —
                <?= e((string)$tool['internal_id']) ?> —
                <?= e((string)$tool['tool_condition']) ?>
            </label>
        <?php endforeach; ?>

        <br>
        <button class="btn">Submit Transfer Request</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
