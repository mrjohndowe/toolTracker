<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$toolId = filter_input(INPUT_GET, 'tool_id', FILTER_VALIDATE_INT) ?: null;

$tools = db()->query(
    'SELECT id, internal_id, name
     FROM tools
     ORDER BY name'
)->fetchAll();

$checkoutRows = [];
$maintenanceRows = [];
$statusRows = [];

if ($toolId) {
    $stmt = db()->prepare(
        'SELECT
            ct.id, ct.transaction_number, ct.checkout_date, ct.due_date,
            ci.returned_at, ci.checkout_condition, ci.return_condition,
            ci.return_status,
            e.first_name, e.last_name, e.employee_number
         FROM checkout_items ci
         INNER JOIN checkout_transactions ct ON ct.id = ci.transaction_id
         INNER JOIN employees e ON e.id = ct.employee_id
         WHERE ci.tool_id = ?
         ORDER BY ct.checkout_date DESC'
    );
    $stmt->execute([$toolId]);
    $checkoutRows = $stmt->fetchAll();

    $stmt = db()->prepare(
        'SELECT id, work_order_number, title, status, priority,
                opened_date, completed_date,
                labor_cost + parts_cost + other_cost AS total_cost
         FROM work_orders
         WHERE tool_id = ?
         ORDER BY opened_date DESC'
    );
    $stmt->execute([$toolId]);
    $maintenanceRows = $stmt->fetchAll();

    $stmt = db()->prepare(
        'SELECT h.*, u.username
         FROM tool_status_history h
         LEFT JOIN users u ON u.id = h.changed_by
         WHERE h.tool_id = ?
         ORDER BY h.created_at DESC'
    );
    $stmt->execute([$toolId]);
    $statusRows = $stmt->fetchAll();
}

$pageTitle = 'Tool History';
require __DIR__ . '/../includes/header.php';
?>
<h1>Tool History</h1>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Tool</label>
            <select name="tool_id" required>
                <option value="">Select tool</option>
                <?php foreach ($tools as $tool): ?>
                    <option value="<?= (int)$tool['id'] ?>" <?= $toolId === (int)$tool['id'] ? 'selected' : '' ?>>
                        <?= e((string)$tool['name']) ?> — <?= e((string)$tool['internal_id']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn">Run Report</button>
    </form>
</div>

<?php if ($toolId): ?>
    <div class="card">
        <h2>Checkout History</h2>
        <table class="table">
            <thead><tr><th>Transaction</th><th>Employee</th><th>Checkout</th><th>Returned</th><th>Condition</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($checkoutRows as $row): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['transaction_number']) ?></a></td>
                    <td><?= e((string)$row['first_name'] . ' ' . (string)$row['last_name']) ?></td>
                    <td><?= e((string)$row['checkout_date']) ?></td>
                    <td><?= e((string)($row['returned_at'] ?? '')) ?></td>
                    <td><?= e((string)$row['checkout_condition']) ?> → <?= e((string)($row['return_condition'] ?? '')) ?></td>
                    <td><?= e((string)$row['return_status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Maintenance History</h2>
        <table class="table">
            <thead><tr><th>Work Order</th><th>Title</th><th>Priority</th><th>Status</th><th>Opened</th><th>Total Cost</th></tr></thead>
            <tbody>
            <?php foreach ($maintenanceRows as $row): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/maintenance/work_order_view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['work_order_number']) ?></a></td>
                    <td><?= e((string)$row['title']) ?></td>
                    <td><?= e((string)$row['priority']) ?></td>
                    <td><?= e((string)$row['status']) ?></td>
                    <td><?= e((string)$row['opened_date']) ?></td>
                    <td>$<?= number_format((float)$row['total_cost'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Status History</h2>
        <table class="table">
            <thead><tr><th>Date</th><th>Status</th><th>Condition</th><th>User</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($statusRows as $row): ?>
                <tr>
                    <td><?= e((string)$row['created_at']) ?></td>
                    <td><?= e((string)($row['old_status'] ?? '')) ?> → <?= e((string)$row['new_status']) ?></td>
                    <td><?= e((string)($row['old_condition'] ?? '')) ?> → <?= e((string)$row['new_condition']) ?></td>
                    <td><?= e((string)($row['username'] ?? 'System')) ?></td>
                    <td><?= e((string)($row['notes'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
