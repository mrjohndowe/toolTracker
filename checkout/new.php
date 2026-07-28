<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../inspections/_common.php';
require_login();

if (isset($_GET['reset'])) {
    clear_checkout_cart();
    redirect('/checkout/new.php');
}

$employee = current_checkout_employee();
$cart = checkout_cart();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'scan_employee') {
        $badge = trim((string)($_POST['badge_code'] ?? ''));
        $found = find_employee_by_badge($badge);

        if ($found === null) {
            record_scan('Employee Badge', $badge, false, 'Employee not found or inactive');
            flash('danger', 'Employee badge not found or employee is inactive.');
        } else {
            $_SESSION['checkout_employee'] = $found;
            record_scan('Employee Badge', $badge, true, 'Employee selected', (int)$found['id']);
            flash('success', 'Employee selected.');
        }

        redirect('/checkout/new.php');
    }

    if ($action === 'scan_tool') {
        if ($employee === null) {
            flash('danger', 'Scan an employee badge first.');
            redirect('/checkout/new.php');
        }

        $value = trim((string)($_POST['tool_code'] ?? ''));
        $tool = find_tool_by_scan($value);

        if ($tool === null) {
            record_scan('Tool Checkout', $value, false, 'Tool not found');
            flash('danger', 'Tool not found.');
        } elseif ($tool['status'] !== 'Available') {
            record_scan('Tool Checkout', $value, false, 'Tool is not available', (int)$employee['id'], (int)$tool['id']);
            flash('danger', 'Tool is not available. Current status: ' . $tool['status']);
        } else {
            $cart[(int)$tool['id']] = $tool;
            save_checkout_cart($cart);
            record_scan('Tool Checkout', $value, true, 'Added to checkout cart', (int)$employee['id'], (int)$tool['id']);
            flash('success', $tool['name'] . ' added.');
        }

        redirect('/checkout/new.php');
    }

    if ($action === 'remove_tool') {
        $toolId = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
        if ($toolId && isset($cart[$toolId])) {
            unset($cart[$toolId]);
            save_checkout_cart($cart);
        }
        redirect('/checkout/new.php');
    }

    if ($action === 'complete_checkout') {
        if ($employee === null || !$cart) {
            flash('danger', 'Select an employee and add at least one tool.');
            redirect('/checkout/new.php');
        }

        $dueDate = trim((string)($_POST['due_date'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));
        $pdo = db();

        try {
            $pdo->beginTransaction();
            $user = current_user();
            $number = generate_transaction_number();

            $stmt = $pdo->prepare(
                'INSERT INTO checkout_transactions
                 (transaction_number, employee_id, due_date, notes, issued_by)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $number,
                (int)$employee['id'],
                $dueDate !== '' ? $dueDate : null,
                $notes !== '' ? $notes : null,
                $user['id'] ?? null,
            ]);

            $transactionId = (int)$pdo->lastInsertId();
            $inspectionItems = [];

            $insertItem = $pdo->prepare(
                'INSERT INTO checkout_items
                 (transaction_id, tool_id, checkout_condition)
                 VALUES (?, ?, ?)'
            );
            $updateTool = $pdo->prepare(
                'UPDATE tools SET status = "Checked Out" WHERE id = ? AND status = "Available"'
            );
            $history = $pdo->prepare(
                'INSERT INTO tool_status_history
                 (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
                 VALUES (?, "Available", "Checked Out", ?, ?, ?, ?)'
            );

            foreach ($cart as $tool) {
                $updateTool->execute([(int)$tool['id']]);

                if ($updateTool->rowCount() !== 1) {
                    throw new RuntimeException('One or more tools are no longer available.');
                }

                $insertItem->execute([
                    $transactionId,
                    (int)$tool['id'],
                    $tool['tool_condition'],
                ]);
                $checkoutItemId = (int)$pdo->lastInsertId();
                $inspectionItems[] = [
                    'type' => 'Checkout',
                    'tool_id' => (int)$tool['id'],
                    'transaction_id' => $transactionId,
                    'checkout_item_id' => $checkoutItemId,
                    'employee_id' => (int)$employee['id'],
                ];

                $history->execute([
                    (int)$tool['id'],
                    $tool['tool_condition'],
                    $tool['tool_condition'],
                    'Checked out on ' . $number,
                    $user['id'] ?? null,
                ]);

                record_scan(
                    'Tool Checkout',
                    (string)$tool['barcode'],
                    true,
                    'Checkout completed',
                    (int)$employee['id'],
                    (int)$tool['id'],
                    $transactionId
                );
            }

            $pdo->commit();
            audit_log('checkout_completed', null, $number);
            clear_checkout_cart();

            $inspectionUrl = inspection_create_queue('Checkout', $inspectionItems, inspection_url('/checkout/new.php?completed=' . $transactionId));
            flash('success', 'Checkout created. Complete the required inspections.');
            redirect($inspectionUrl);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', $e->getMessage());
            redirect('/checkout/new.php');
        }
    }
}

$employee = current_checkout_employee();
$cart = checkout_cart();

$pageTitle = 'New Checkout';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>New Checkout</h1>
    <a class="btn secondary" href="?reset=1">Reset</a>
</div>

<div class="grid">
    <div class="card">
        <h2>1. Scan Employee Badge</h2>

        <?php if ($employee): ?>
            <p><strong><?= e((string)$employee['first_name'] . ' ' . (string)$employee['last_name']) ?></strong></p>
            <p>Employee #<?= e((string)$employee['employee_number']) ?></p>
            <p><?= e((string)($employee['department_name'] ?? '')) ?></p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="scan_employee">

                <div class="form-group">
                    <label>Badge Code</label>
                    <input name="badge_code" autofocus required>
                </div>

                <button class="btn">Select Employee</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>2. Scan Tools</h2>

        <?php if ($employee): ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="scan_tool">

                <div class="form-group">
                    <label>Barcode, Internal ID, or Serial</label>
                    <input name="tool_code" autofocus required>
                </div>

                <button class="btn">Add Tool</button>
            </form>
        <?php else: ?>
            <p class="muted">Select an employee first.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h2>Checkout Cart</h2>

    <table class="table">
        <thead><tr><th>Tool</th><th>Internal ID</th><th>Condition</th><th></th></tr></thead>
        <tbody>
        <?php if (!$cart): ?>
            <tr><td colspan="4">No tools scanned.</td></tr>
        <?php endif; ?>

        <?php foreach ($cart as $tool): ?>
            <tr>
                <td><?= e((string)$tool['name']) ?></td>
                <td><?= e((string)$tool['internal_id']) ?></td>
                <td><?= e((string)$tool['tool_condition']) ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="remove_tool">
                        <input type="hidden" name="tool_id" value="<?= (int)$tool['id'] ?>">
                        <button class="btn secondary">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($employee && $cart): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="complete_checkout">

            <div class="grid">
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="datetime-local" name="due_date">
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <input name="notes">
                </div>
            </div>

            <button class="btn">Complete Checkout</button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
