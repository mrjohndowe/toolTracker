<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));

$sql = '
    SELECT
        ct.id, ct.transaction_number, ct.checkout_date, ct.returned_date, ct.status,
        e.employee_number, e.first_name, e.last_name,
        COUNT(ci.id) AS item_count
    FROM checkout_transactions ct
    INNER JOIN employees e ON e.id = ct.employee_id
    LEFT JOIN checkout_items ci ON ci.transaction_id = ct.id
    WHERE 1=1
';
$params = [];

if ($q !== '') {
    $sql .= ' AND (
        ct.transaction_number LIKE ? OR e.employee_number LIKE ?
        OR e.first_name LIKE ? OR e.last_name LIKE ?
    )';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

$sql .= ' GROUP BY ct.id ORDER BY ct.checkout_date DESC LIMIT 500';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Transaction History';
require __DIR__ . '/../includes/header.php';
?>
<h1>Transaction History</h1>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Search</label>
            <input name="q" value="<?= e($q) ?>" placeholder="Transaction or employee">
        </div>
        <button class="btn">Search</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Transaction</th><th>Employee</th><th>Checkout</th><th>Returned</th><th>Items</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['transaction_number']) ?></td>
                <td><?= e((string)$row['first_name'] . ' ' . (string)$row['last_name']) ?></td>
                <td><?= e((string)$row['checkout_date']) ?></td>
                <td><?= e((string)($row['returned_date'] ?? '')) ?></td>
                <td><?= (int)$row['item_count'] ?></td>
                <td><?= e((string)$row['status']) ?></td>
                <td><a class="btn secondary" href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$row['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
