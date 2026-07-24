<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $toolId = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
    $typeId = filter_input(INPUT_POST, 'maintenance_type_id', FILTER_VALIDATE_INT) ?: null;
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $priority = (string)($_POST['priority'] ?? 'Normal');
    $assignedTo = trim((string)($_POST['assigned_to'] ?? ''));
    $vendorName = trim((string)($_POST['vendor_name'] ?? ''));
    $dueDate = trim((string)($_POST['due_date'] ?? ''));

    if (!$toolId) $errors[] = 'Tool is required.';
    if ($title === '') $errors[] = 'Title is required.';
    if (!in_array($priority, maintenance_priorities(), true)) {
        $errors[] = 'Invalid priority.';
    }

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();
            $user = current_user();
            $number = generate_work_order_number();

            $stmt = $pdo->prepare(
                'INSERT INTO work_orders
                 (work_order_number, tool_id, maintenance_type_id, title,
                  description, priority, assigned_to, vendor_name, due_date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $number,
                $toolId,
                $typeId,
                $title,
                $description !== '' ? $description : null,
                $priority,
                $assignedTo !== '' ? $assignedTo : null,
                $vendorName !== '' ? $vendorName : null,
                $dueDate !== '' ? $dueDate : null,
                $user['id'] ?? null,
            ]);

            $workOrderId = (int)$pdo->lastInsertId();

            $toolStmt = $pdo->prepare('SELECT status, tool_condition FROM tools WHERE id = ?');
            $toolStmt->execute([$toolId]);
            $tool = $toolStmt->fetch();

            if (is_array($tool) && $tool['status'] !== 'Repair') {
                $pdo->prepare('UPDATE tools SET status = "Repair" WHERE id = ?')->execute([$toolId]);

                $pdo->prepare(
                    'INSERT INTO tool_status_history
                     (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
                     VALUES (?, ?, "Repair", ?, ?, ?, ?)'
                )->execute([
                    $toolId,
                    $tool['status'],
                    $tool['tool_condition'],
                    $tool['tool_condition'],
                    'Work order opened: ' . $number,
                    $user['id'] ?? null,
                ]);
            }

            record_work_order_history($workOrderId, null, 'Open', 'Work order created');
            $pdo->commit();

            audit_log('work_order_created', null, $number);
            flash('success', 'Work order created.');
            redirect('/maintenance/work_order_view.php?id=' . $workOrderId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'New Work Order';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>New Work Order</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Tool</label>
                <select name="tool_id" required>
                    <option value="">Select tool</option>
                    <?php foreach (maintenance_tools_list() as $tool): ?>
                        <option value="<?= (int)$tool['id'] ?>">
                            <?= e((string)$tool['name']) ?> — <?= e((string)$tool['internal_id']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Maintenance Type</label>
                <select name="maintenance_type_id">
                    <option value="">No type</option>
                    <?php foreach (maintenance_types_list() as $type): ?>
                        <option value="<?= (int)$type['id'] ?>">
                            <?= e((string)$type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <?php foreach (maintenance_priorities() as $priority): ?>
                        <option value="<?= e($priority) ?>" <?= $priority === 'Normal' ? 'selected' : '' ?>>
                            <?= e($priority) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date">
            </div>

            <div class="form-group">
                <label>Assigned To</label>
                <input name="assigned_to">
            </div>

            <div class="form-group">
                <label>Vendor</label>
                <input name="vendor_name">
            </div>
        </div>

        <div class="form-group">
            <label>Title</label>
            <input name="title" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="6" style="width:100%;padding:11px"></textarea>
        </div>

        <div class="actions">
            <button class="btn">Create Work Order</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/index.php">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
