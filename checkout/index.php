<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$open = db()->query(
    'SELECT
        ct.id, ct.transaction_number, ct.checkout_date, ct.due_date, ct.status,
        e.employee_number, e.first_name, e.last_name,
        COUNT(ci.id) AS item_count,
        SUM(ci.return_status = "Pending") AS pending_count
     FROM checkout_transactions ct
     INNER JOIN employees e ON e.id = ct.employee_id
     LEFT JOIN checkout_items ci ON ci.transaction_id = ct.id
     WHERE ct.status IN ("Open", "Partially Returned")
     GROUP BY ct.id
     ORDER BY ct.checkout_date DESC'
)->fetchAll();

$pageTitle = 'Checkout';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Checkout and Returns</h1>
    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/checkout/scan_history.php">Scan History</a>
        <a class="btn" href="<?= BASE_URL ?>/checkout/new.php">New Checkout</a>
    </div>
</div>

<div class="card">
    <h2>Open Transactions</h2>

    <table class="table">
        <thead>
        <tr>
            <th>Transaction</th>
            <th>Employee</th>
            <th>Checkout Date</th>
            <th>Due Date</th>
            <th>Items</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$open): ?>
            <tr><td colspan="7">No open transactions.</td></tr>
        <?php endif; ?>

        <?php foreach ($open as $row): ?>
            <tr>
                <td><?= e((string)$row['transaction_number']) ?></td>
                <td><?= e((string)$row['first_name'] . ' ' . (string)$row['last_name']) ?></td>
                <td><?= e((string)$row['checkout_date']) ?></td>
                <td><?= e((string)($row['due_date'] ?? '')) ?></td>
                <td><?= (int)$row['pending_count'] ?> / <?= (int)$row['item_count'] ?> out</td>
                <td><span class="badge"><?= e((string)$row['status']) ?></span></td>
                <td>
                    <a class="btn secondary" href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$row['id'] ?>">View</a>
                    <a class="btn" href="<?= BASE_URL ?>/checkout/return.php?id=<?= (int)$row['id'] ?>">Return</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
