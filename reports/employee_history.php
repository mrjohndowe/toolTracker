<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$employeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT) ?: null;

$employees = db()->query(
    'SELECT id, employee_number, first_name, last_name
     FROM employees
     ORDER BY last_name, first_name'
)->fetchAll();

$rows = [];

if ($employeeId) {
    $stmt = db()->prepare(
        'SELECT
            ct.id, ct.transaction_number, ct.checkout_date, ct.due_date,
            ct.returned_date, ct.status,
            COUNT(ci.id) AS item_count,
            SUM(ci.return_status = "Pending") AS outstanding_count
         FROM checkout_transactions ct
         LEFT JOIN checkout_items ci ON ci.transaction_id = ct.id
         WHERE ct.employee_id = ?
         GROUP BY ct.id
         ORDER BY ct.checkout_date DESC'
    );
    $stmt->execute([$employeeId]);
    $rows = $stmt->fetchAll();
}

if (isset($_GET['export']) && $employeeId) {
    $csv = [];
    foreach ($rows as $row) {
        $csv[] = [
            $row['transaction_number'],
            $row['checkout_date'],
            $row['due_date'],
            $row['returned_date'],
            $row['status'],
            $row['item_count'],
            $row['outstanding_count'],
        ];
    }

    csv_download(
        'employee-checkout-history-' . $employeeId . '.csv',
        ['Transaction', 'Checkout Date', 'Due Date', 'Returned Date', 'Status', 'Item Count', 'Outstanding Count'],
        $csv
    );
}

$pageTitle = 'Employee Checkout History';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Employee Checkout History</h1>
    <?php if ($employeeId): ?>
        <a class="btn" href="?employee_id=<?= $employeeId ?>&export=1">Export CSV</a>
    <?php endif; ?>
</div>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Employee</label>
            <select name="employee_id" required>
                <option value="">Select employee</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int)$employee['id'] ?>" <?= $employeeId === (int)$employee['id'] ? 'selected' : '' ?>>
                        <?= e((string)$employee['last_name'] . ', ' . (string)$employee['first_name']) ?>
                        — <?= e((string)$employee['employee_number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn">Run Report</button>
    </form>
</div>

<?php if ($employeeId): ?>
    <div class="card">
        <table class="table">
            <thead>
            <tr><th>Transaction</th><th>Checkout</th><th>Due</th><th>Returned</th><th>Items</th><th>Outstanding</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8">No checkout history found.</td></tr>
            <?php endif; ?>

            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e((string)$row['transaction_number']) ?></td>
                    <td><?= e((string)$row['checkout_date']) ?></td>
                    <td><?= e((string)($row['due_date'] ?? '')) ?></td>
                    <td><?= e((string)($row['returned_date'] ?? '')) ?></td>
                    <td><?= (int)$row['item_count'] ?></td>
                    <td><?= (int)$row['outstanding_count'] ?></td>
                    <td><?= report_status_badge((string)$row['status']) ?></td>
                    <td><a class="btn secondary" href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$row['id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
