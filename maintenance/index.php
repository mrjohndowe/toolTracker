<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$summary = [
    'open' => (int)db()->query(
        'SELECT COUNT(*) FROM work_orders
         WHERE status IN ("Open","In Progress","Waiting Parts")'
    )->fetchColumn(),
    'overdue' => (int)db()->query(
        'SELECT COUNT(*) FROM work_orders
         WHERE due_date < CURDATE()
           AND status NOT IN ("Completed","Cancelled")'
    )->fetchColumn(),
    'due_soon' => (int)db()->query(
        'SELECT COUNT(*) FROM maintenance_schedules
         WHERE active = 1
           AND next_service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)'
    )->fetchColumn(),
    'calibration_due' => (int)db()->query(
        'SELECT COUNT(*) FROM calibration_records cr
         INNER JOIN (
             SELECT tool_id, MAX(id) AS latest_id
             FROM calibration_records
             GROUP BY tool_id
         ) x ON x.latest_id = cr.id
         WHERE cr.next_calibration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
    )->fetchColumn(),
];

$orders = db()->query(
    'SELECT
        wo.id, wo.work_order_number, wo.title, wo.priority, wo.status,
        wo.opened_date, wo.due_date,
        t.name AS tool_name, t.internal_id,
        mt.name AS maintenance_type_name
     FROM work_orders wo
     INNER JOIN tools t ON t.id = wo.tool_id
     LEFT JOIN maintenance_types mt ON mt.id = wo.maintenance_type_id
     WHERE wo.status NOT IN ("Completed","Cancelled")
     ORDER BY
        CASE wo.priority
            WHEN "Critical" THEN 1
            WHEN "High" THEN 2
            WHEN "Normal" THEN 3
            ELSE 4
        END,
        wo.due_date IS NULL,
        wo.due_date,
        wo.opened_date'
)->fetchAll();

$pageTitle = 'Maintenance';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Maintenance</h1>
    <div>
        <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/schedules.php">Schedules</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/types.php">Types</a>
        <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/calibration.php">Calibration</a>
        <a class="btn" href="<?= BASE_URL ?>/maintenance/work_order_add.php">New Work Order</a>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2><?= $summary['open'] ?></h2>
        <p>Open work orders</p>
    </div>

    <div class="card">
        <h2><?= $summary['overdue'] ?></h2>
        <p>Overdue work orders</p>
    </div>

    <div class="card">
        <h2><?= $summary['due_soon'] ?></h2>
        <p>Scheduled services due soon</p>
    </div>

    <div class="card">
        <h2><?= $summary['calibration_due'] ?></h2>
        <p>Calibrations due within 30 days</p>
    </div>
</div>

<div class="card">
    <h2>Active Work Orders</h2>

    <table class="table">
        <thead>
        <tr>
            <th>Work Order</th>
            <th>Tool</th>
            <th>Type</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Due</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$orders): ?>
            <tr><td colspan="7">No active work orders.</td></tr>
        <?php endif; ?>

        <?php foreach ($orders as $row): ?>
            <tr>
                <td>
                    <strong><?= e((string)$row['work_order_number']) ?></strong><br>
                    <?= e((string)$row['title']) ?>
                </td>
                <td>
                    <?= e((string)$row['tool_name']) ?><br>
                    <span class="muted"><?= e((string)$row['internal_id']) ?></span>
                </td>
                <td><?= e((string)($row['maintenance_type_name'] ?? '')) ?></td>
                <td><span class="badge"><?= e((string)$row['priority']) ?></span></td>
                <td><span class="badge"><?= e((string)$row['status']) ?></span></td>
                <td><?= e((string)($row['due_date'] ?? '')) ?></td>
                <td>
                    <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/work_order_view.php?id=<?= (int)$row['id'] ?>">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
