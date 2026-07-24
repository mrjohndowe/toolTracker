<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$stmt = db()->prepare(
    'SELECT ct.*, e.employee_number, e.first_name, e.last_name, e.badge_code,
            u.username AS issued_by_name
     FROM checkout_transactions ct
     INNER JOIN employees e ON e.id = ct.employee_id
     LEFT JOIN users u ON u.id = ct.issued_by
     WHERE ct.id = ?'
);
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!is_array($transaction)) {
    http_response_code(404);
    exit('Transaction not found.');
}

$items = db()->prepare(
    'SELECT ci.*, t.internal_id, t.barcode, t.name, t.serial_number
     FROM checkout_items ci
     INNER JOIN tools t ON t.id = ci.tool_id
     WHERE ci.transaction_id = ?
     ORDER BY t.name'
);
$items->execute([$id]);
$items = $items->fetchAll();

$pageTitle = 'Transaction ' . $transaction['transaction_number'];
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <div>
        <h1><?= e((string)$transaction['transaction_number']) ?></h1>
        <div class="muted"><?= e((string)$transaction['status']) ?></div>
    </div>

    <?php if (in_array($transaction['status'], ['Open', 'Partially Returned'], true)): ?>
        <a class="btn" href="<?= BASE_URL ?>/checkout/return.php?id=<?= (int)$transaction['id'] ?>">Process Return</a>
    <?php endif; ?>
</div>

<div class="grid">
    <div class="card">
        <h2>Employee</h2>
        <p><strong><?= e((string)$transaction['first_name'] . ' ' . (string)$transaction['last_name']) ?></strong></p>
        <p>Employee #<?= e((string)$transaction['employee_number']) ?></p>
        <p>Badge: <?= e((string)$transaction['badge_code']) ?></p>
    </div>

    <div class="card">
        <h2>Transaction</h2>
        <p><strong>Checkout:</strong> <?= e((string)$transaction['checkout_date']) ?></p>
        <p><strong>Due:</strong> <?= e((string)($transaction['due_date'] ?? '')) ?></p>
        <p><strong>Returned:</strong> <?= e((string)($transaction['returned_date'] ?? '')) ?></p>
        <p><strong>Issued By:</strong> <?= e((string)($transaction['issued_by_name'] ?? 'System')) ?></p>
    </div>
</div>

<div class="card">
    <h2>Tools</h2>

    <table class="table">
        <thead>
        <tr><th>Tool</th><th>Internal ID</th><th>Checkout Condition</th><th>Return</th><th>Return Condition</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e((string)$item['name']) ?></td>
                <td><?= e((string)$item['internal_id']) ?></td>
                <td><?= e((string)$item['checkout_condition']) ?></td>
                <td><?= e((string)$item['return_status']) ?></td>
                <td><?= e((string)($item['return_condition'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Notes</h2>
    <p><?= nl2br(e((string)($transaction['notes'] ?? 'No notes.'))) ?></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
