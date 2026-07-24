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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = (string)($_POST['status'] ?? $workOrder['status']);
    $assignedTo = trim((string)($_POST['assigned_to'] ?? ''));
    $vendorName = trim((string)($_POST['vendor_name'] ?? ''));
    $dueDate = trim((string)($_POST['due_date'] ?? ''));
    $laborCost = (float)($_POST['labor_cost'] ?? 0);
    $partsCost = (float)($_POST['parts_cost'] ?? 0);
    $otherCost = (float)($_POST['other_cost'] ?? 0);
    $completionNotes = trim((string)($_POST['completion_notes'] ?? ''));

    if (!in_array($status, maintenance_statuses(), true)) {
        $errors[] = 'Invalid work order status.';
    }

    if ($laborCost < 0 || $partsCost < 0 || $otherCost < 0) {
        $errors[] = 'Costs cannot be negative.';
    }

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();
            $user = current_user();

            $completedDate = $status === 'Completed' ? date('Y-m-d H:i:s') : null;
            $completedBy = $status === 'Completed' ? ($user['id'] ?? null) : null;

            $pdo->prepare(
                'UPDATE work_orders
                 SET status = ?, assigned_to = ?, vendor_name = ?, due_date = ?,
                     labor_cost = ?, parts_cost = ?, other_cost = ?,
                     completion_notes = ?, completed_date = ?, completed_by = ?
                 WHERE id = ?'
            )->execute([
                $status,
                $assignedTo !== '' ? $assignedTo : null,
                $vendorName !== '' ? $vendorName : null,
                $dueDate !== '' ? $dueDate : null,
                $laborCost,
                $partsCost,
                $otherCost,
                $completionNotes !== '' ? $completionNotes : null,
                $completedDate,
                $completedBy,
                $id,
            ]);

            if ($status !== $workOrder['status']) {
                record_work_order_history(
                    $id,
                    (string)$workOrder['status'],
                    $status,
                    'Work order updated'
                );
            }

            if ($status === 'Completed') {
                $pdo->prepare(
                    'UPDATE tools SET status = "Available" WHERE id = ?'
                )->execute([(int)$workOrder['tool_id']]);

                $pdo->prepare(
                    'INSERT INTO tool_status_history
                     (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
                     VALUES (?, ?, "Available", ?, ?, ?, ?)'
                )->execute([
                    (int)$workOrder['tool_id'],
                    (string)$workOrder['tool_status'],
                    'Good',
                    'Good',
                    'Work order completed: ' . $workOrder['work_order_number'],
                    $user['id'] ?? null,
                ]);

                if ($workOrder['schedule_id']) {
                    $scheduleStmt = $pdo->prepare(
                        'SELECT interval_days FROM maintenance_schedules WHERE id = ?'
                    );
                    $scheduleStmt->execute([(int)$workOrder['schedule_id']]);
                    $interval = (int)$scheduleStmt->fetchColumn();

                    $nextDate = $interval > 0
                        ? date('Y-m-d', strtotime('+' . $interval . ' days'))
                        : null;

                    $pdo->prepare(
                        'UPDATE maintenance_schedules
                         SET last_service_date = CURDATE(), next_service_date = ?
                         WHERE id = ?'
                    )->execute([$nextDate, (int)$workOrder['schedule_id']]);
                }
            }

            $pdo->commit();

            audit_log('work_order_updated', null, (string)$workOrder['work_order_number']);
            flash('success', 'Work order updated.');
            redirect('/maintenance/work_order_view.php?id=' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Update Work Order';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Update <?= e((string)$workOrder['work_order_number']) ?></h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (maintenance_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= $workOrder['status'] === $status ? 'selected' : '' ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?= e((string)($workOrder['due_date'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Assigned To</label>
                <input name="assigned_to" value="<?= e((string)($workOrder['assigned_to'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Vendor</label>
                <input name="vendor_name" value="<?= e((string)($workOrder['vendor_name'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Labor Cost</label>
                <input type="number" min="0" step="0.01" name="labor_cost"
                       value="<?= e(number_format((float)$workOrder['labor_cost'], 2, '.', '')) ?>">
            </div>

            <div class="form-group">
                <label>Parts Cost</label>
                <input type="number" min="0" step="0.01" name="parts_cost"
                       value="<?= e(number_format((float)$workOrder['parts_cost'], 2, '.', '')) ?>">
            </div>

            <div class="form-group">
                <label>Other Cost</label>
                <input type="number" min="0" step="0.01" name="other_cost"
                       value="<?= e(number_format((float)$workOrder['other_cost'], 2, '.', '')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Completion Notes</label>
            <textarea name="completion_notes" rows="6" style="width:100%;padding:11px"><?= e((string)($workOrder['completion_notes'] ?? '')) ?></textarea>
        </div>

        <div class="actions">
            <button class="btn">Save Changes</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/work_order_view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
