<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$metrics = [
    'total_tools' => (int)db()->query(
        'SELECT COUNT(*) FROM tools WHERE active = 1'
    )->fetchColumn(),

    'checked_out' => (int)db()->query(
        'SELECT COUNT(*) FROM tools WHERE active = 1 AND status = "Checked Out"'
    )->fetchColumn(),

    'available' => (int)db()->query(
        'SELECT COUNT(*) FROM tools WHERE active = 1 AND status = "Available"'
    )->fetchColumn(),

    'repair' => (int)db()->query(
        'SELECT COUNT(*) FROM tools WHERE active = 1 AND status IN ("Repair","Inspection")'
    )->fetchColumn(),

    'active_employees' => (int)db()->query(
        'SELECT COUNT(*) FROM employees WHERE active = 1 AND status = "Active"'
    )->fetchColumn(),

    'overdue_checkouts' => (int)db()->query(
        'SELECT COUNT(*) FROM checkout_transactions
         WHERE due_date < NOW()
           AND status IN ("Open","Partially Returned")'
    )->fetchColumn(),

    'open_work_orders' => (int)db()->query(
        'SELECT COUNT(*) FROM work_orders
         WHERE status IN ("Open","In Progress","Waiting Parts")'
    )->fetchColumn(),

    'maintenance_cost_ytd' => (float)db()->query(
        'SELECT COALESCE(SUM(labor_cost + parts_cost + other_cost), 0)
         FROM work_orders
         WHERE opened_date >= MAKEDATE(YEAR(CURDATE()), 1)'
    )->fetchColumn(),
];

$recentCheckouts = db()->query(
    'SELECT
        ct.id, ct.transaction_number, ct.checkout_date, ct.status,
        e.first_name, e.last_name,
        COUNT(ci.id) AS item_count
     FROM checkout_transactions ct
     INNER JOIN employees e ON e.id = ct.employee_id
     LEFT JOIN checkout_items ci ON ci.transaction_id = ct.id
     GROUP BY ct.id
     ORDER BY ct.checkout_date DESC
     LIMIT 8'
)->fetchAll();

$overdueMaintenance = db()->query(
    'SELECT
        wo.id, wo.work_order_number, wo.title, wo.due_date,
        t.name AS tool_name, t.internal_id
     FROM work_orders wo
     INNER JOIN tools t ON t.id = wo.tool_id
     WHERE wo.due_date < CURDATE()
       AND wo.status NOT IN ("Completed","Cancelled")
     ORDER BY wo.due_date
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Reports Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Reports Dashboard</h1>
    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/reports/tool_utilization.php">Tool Utilization</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/reports/overdue.php">Overdue</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/reports/maintenance_costs.php">Maintenance Costs</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/reports/employee_history.php">Employee History</a>
    </div>
</div>

<div class="grid">
    <div class="card"><h2><?= $metrics['total_tools'] ?></h2><p>Total active tools</p></div>
    <div class="card"><h2><?= $metrics['available'] ?></h2><p>Available tools</p></div>
    <div class="card"><h2><?= $metrics['checked_out'] ?></h2><p>Checked out</p></div>
    <div class="card"><h2><?= $metrics['repair'] ?></h2><p>Inspection or repair</p></div>
    <div class="card"><h2><?= $metrics['active_employees'] ?></h2><p>Active employees</p></div>
    <div class="card"><h2><?= $metrics['overdue_checkouts'] ?></h2><p>Overdue checkouts</p></div>
    <div class="card"><h2><?= $metrics['open_work_orders'] ?></h2><p>Open work orders</p></div>
    <div class="card"><h2>$<?= number_format($metrics['maintenance_cost_ytd'], 2) ?></h2><p>Maintenance cost YTD</p></div>
</div>

<div class="grid">
    <div class="card">
        <h2>Recent Checkouts</h2>
        <table class="table">
            <thead><tr><th>Transaction</th><th>Employee</th><th>Items</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentCheckouts as $row): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/checkout/view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['transaction_number']) ?></a></td>
                    <td><?= e((string)$row['first_name'] . ' ' . (string)$row['last_name']) ?></td>
                    <td><?= (int)$row['item_count'] ?></td>
                    <td><?= report_status_badge((string)$row['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Overdue Maintenance</h2>
        <table class="table">
            <thead><tr><th>Work Order</th><th>Tool</th><th>Due</th></tr></thead>
            <tbody>
            <?php if (!$overdueMaintenance): ?>
                <tr><td colspan="3">No overdue work orders.</td></tr>
            <?php endif; ?>

            <?php foreach ($overdueMaintenance as $row): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/maintenance/work_order_view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['work_order_number']) ?></a></td>
                    <td><?= e((string)$row['tool_name']) ?></td>
                    <td><?= e((string)$row['due_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
