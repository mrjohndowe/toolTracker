<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$rows = db()->query(
    'SELECT
        ct.id, ct.transaction_number, ct.checkout_date, ct.due_date,
        TIMESTAMPDIFF(DAY, ct.due_date, NOW()) AS days_overdue,
        e.employee_number, e.first_name, e.last_name, e.email, e.phone,
        COUNT(CASE WHEN ci.return_status = "Pending" THEN 1 END) AS outstanding_items
     FROM checkout_transactions ct
     INNER JOIN employees e ON e.id = ct.employee_id
     INNER JOIN checkout_items ci ON ci.transaction_id = ct.id
     WHERE ct.due_date < NOW()
       AND ct.status IN ("Open","Partially Returned")
     GROUP BY ct.id
     HAVING outstanding_items > 0
     ORDER BY days_overdue DESC, ct.due_date'
)->fetchAll();

if (isset($_GET['export'])) {
    $csv = [];
    foreach ($rows as $row) {
        $csv[] = [
            $row['transaction_number'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['employee_number'],
            $row['checkout_date'],
            $row['due_date'],
            $row['days_overdue'],
            $row['outstanding_items'],
            $row['email'],
            $row['phone'],
        ];
    }

    csv_download(
        'overdue-checkouts-' . date('Y-m-d') . '.csv',
        ['Transaction', 'Employee', 'Employee Number', 'Checkout Date', 'Due Date', 'Days Overdue', 'Outstanding Items', 'Email', 'Phone'],
        $csv
    );
}

$pageTitle = 'Overdue Checkouts';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Overdue Checkouts</h1>
    <a class="btn" href="?export=1">Export CSV</a>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Transaction</th>
            <th>Employee</th>
            <th>Due</th>
            <th>Days Overdue</th>
            <th>Outstanding</th>
            <th>Contact</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="7">No overdue checkouts.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['transaction_number']) ?></td>
                <td>
                    <?= e((string)$row['first_name'] . ' ' . (string)$row['last_name']) ?><br>
                    <span class="muted"><?= e((string)$row['employee_number']) ?></span>
                </td>
                <td><?= e((string)$row['due_date']) ?></td>
                <td><?= (int)$row['days_overdue'] ?></td>
                <td><?= (int)$row['outstanding_items'] ?></td>
                <td>
                    <?= e((string)($row['email'] ?? '')) ?><br>
                    <?= e((string)($row['phone'] ?? '')) ?>
                </td>
                <td><a class="btn secondary" href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$row['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
