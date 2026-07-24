<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$stmt = db()->prepare(
    'SELECT ct.*, e.first_name, e.last_name
     FROM checkout_transactions ct
     INNER JOIN employees e ON e.id = ct.employee_id
     WHERE ct.id = ?'
);
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!is_array($transaction)) {
    http_response_code(404);
    exit('Transaction not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $returnCondition = (string)($_POST['return_condition'] ?? 'Good');
    $returnStatus = (string)($_POST['return_status'] ?? 'Returned');
    $notes = trim((string)($_POST['inspection_notes'] ?? ''));

    $allowedStatuses = ['Returned', 'Inspection', 'Repair', 'Lost'];
    $allowedConditions = ['Excellent', 'Good', 'Fair', 'Poor'];

    if (!$itemId || !in_array($returnStatus, $allowedStatuses, true) || !in_array($returnCondition, $allowedConditions, true)) {
        flash('danger', 'Invalid return information.');
        redirect('/checkout/return.php?id=' . $id);
    }

    $itemStmt = db()->prepare(
        'SELECT ci.*, t.name, t.barcode, t.status AS tool_status, t.tool_condition
         FROM checkout_items ci
         INNER JOIN tools t ON t.id = ci.tool_id
         WHERE ci.id = ? AND ci.transaction_id = ? AND ci.return_status = "Pending"'
    );
    $itemStmt->execute([$itemId, $id]);
    $item = $itemStmt->fetch();

    if (!is_array($item)) {
        flash('danger', 'Checkout item was not found or was already returned.');
        redirect('/checkout/return.php?id=' . $id);
    }

    $toolStatus = match ($returnStatus) {
        'Returned' => 'Available',
        'Inspection' => 'Inspection',
        'Repair' => 'Repair',
        'Lost' => 'Retired',
        default => 'Inspection',
    };

    $pdo = db();

    try {
        $pdo->beginTransaction();
        $user = current_user();

        $pdo->prepare(
            'UPDATE checkout_items
             SET returned_at = NOW(), return_condition = ?, return_status = ?,
                 inspection_notes = ?, returned_by = ?
             WHERE id = ?'
        )->execute([
            $returnCondition,
            $returnStatus,
            $notes !== '' ? $notes : null,
            $user['id'] ?? null,
            $itemId,
        ]);

        $pdo->prepare(
            'UPDATE tools
             SET status = ?, tool_condition = ?
             WHERE id = ?'
        )->execute([
            $toolStatus,
            $returnCondition,
            (int)$item['tool_id'],
        ]);

        $pdo->prepare(
            'INSERT INTO tool_status_history
             (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int)$item['tool_id'],
            (string)$item['tool_status'],
            $toolStatus,
            (string)$item['tool_condition'],
            $returnCondition,
            $notes !== '' ? $notes : 'Returned on ' . $transaction['transaction_number'],
            $user['id'] ?? null,
        ]);

        $pdo->commit();

        record_scan(
            'Tool Return',
            (string)$item['barcode'],
            true,
            'Return processed',
            (int)$transaction['employee_id'],
            (int)$item['tool_id'],
            (int)$transaction['id']
        );

        update_transaction_status((int)$transaction['id']);
        audit_log('tool_returned', null, (string)$item['name']);

        flash('success', $item['name'] . ' returned.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', $e->getMessage());
    }

    redirect('/checkout/return.php?id=' . $id);
}

$items = db()->prepare(
    'SELECT ci.*, t.name, t.internal_id, t.barcode, t.serial_number
     FROM checkout_items ci
     INNER JOIN tools t ON t.id = ci.tool_id
     WHERE ci.transaction_id = ?
     ORDER BY ci.return_status = "Pending" DESC, t.name'
);
$items->execute([$id]);
$items = $items->fetchAll();

$pageTitle = 'Return Tools';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <div>
        <h1>Return Tools</h1>
        <div class="muted">
            <?= e((string)$transaction['transaction_number']) ?> —
            <?= e((string)$transaction['first_name'] . ' ' . (string)$transaction['last_name']) ?>
        </div>
    </div>

    <a class="btn secondary" href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$transaction['id'] ?>">View Transaction</a>
</div>

<?php foreach ($items as $item): ?>
    <div class="card">
        <h2><?= e((string)$item['name']) ?></h2>
        <p>
            <strong>ID:</strong> <?= e((string)$item['internal_id']) ?> |
            <strong>Checkout Condition:</strong> <?= e((string)$item['checkout_condition']) ?>
        </p>

        <?php if ($item['return_status'] !== 'Pending'): ?>
            <div class="alert success">
                Returned as <?= e((string)$item['return_status']) ?>
                with condition <?= e((string)$item['return_condition']) ?>.
            </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">

                <div class="grid">
                    <div class="form-group">
                        <label>Return Condition</label>
                        <select name="return_condition">
                            <option>Excellent</option>
                            <option selected>Good</option>
                            <option>Fair</option>
                            <option>Poor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Return Result</label>
                        <select name="return_status">
                            <option value="Returned">Return to Available</option>
                            <option value="Inspection">Send to Inspection</option>
                            <option value="Repair">Send to Repair</option>
                            <option value="Lost">Mark Lost</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Inspection Notes</label>
                    <textarea name="inspection_notes" rows="4" style="width:100%;padding:11px"></textarea>
                </div>

                <button class="btn">Process Return</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
