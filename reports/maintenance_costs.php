<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$defaultFrom = date('Y-01-01');
$defaultTo = date('Y-m-d');

$from = report_date((string)($_GET['from'] ?? ''), $defaultFrom);
$to = report_date((string)($_GET['to'] ?? ''), $defaultTo);

$stmt = db()->prepare(
    'SELECT
        t.id, t.internal_id, t.name,
        COUNT(wo.id) AS work_order_count,
        COALESCE(SUM(wo.labor_cost), 0) AS labor_cost,
        COALESCE(SUM(wo.parts_cost), 0) AS parts_cost,
        COALESCE(SUM(wo.other_cost), 0) AS other_cost,
        COALESCE(SUM(wo.labor_cost + wo.parts_cost + wo.other_cost), 0) AS total_cost
     FROM tools t
     LEFT JOIN work_orders wo
        ON wo.tool_id = t.id
       AND DATE(wo.opened_date) BETWEEN ? AND ?
     WHERE t.active = 1
     GROUP BY t.id
     HAVING work_order_count > 0
     ORDER BY total_cost DESC, t.name'
);
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

if (isset($_GET['export'])) {
    $csv = [];
    foreach ($rows as $row) {
        $csv[] = [
            $row['internal_id'],
            $row['name'],
            $row['work_order_count'],
            $row['labor_cost'],
            $row['parts_cost'],
            $row['other_cost'],
            $row['total_cost'],
        ];
    }

    csv_download(
        'maintenance-costs-' . $from . '-to-' . $to . '.csv',
        ['Internal ID', 'Tool', 'Work Orders', 'Labor Cost', 'Parts Cost', 'Other Cost', 'Total Cost'],
        $csv
    );
}

$totals = [
    'labor' => array_sum(array_map(fn($r) => (float)$r['labor_cost'], $rows)),
    'parts' => array_sum(array_map(fn($r) => (float)$r['parts_cost'], $rows)),
    'other' => array_sum(array_map(fn($r) => (float)$r['other_cost'], $rows)),
    'total' => array_sum(array_map(fn($r) => (float)$r['total_cost'], $rows)),
];

$pageTitle = 'Maintenance Costs';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Maintenance Costs</h1>
    <a class="btn" href="?from=<?= e($from) ?>&to=<?= e($to) ?>&export=1">Export CSV</a>
</div>

<div class="card">
    <form method="get">
        <div class="grid">
            <div class="form-group">
                <label>From</label>
                <input type="date" name="from" value="<?= e($from) ?>">
            </div>
            <div class="form-group">
                <label>To</label>
                <input type="date" name="to" value="<?= e($to) ?>">
            </div>
        </div>
        <button class="btn">Run Report</button>
    </form>
</div>

<div class="grid">
    <div class="card"><h2>$<?= number_format($totals['labor'], 2) ?></h2><p>Labor</p></div>
    <div class="card"><h2>$<?= number_format($totals['parts'], 2) ?></h2><p>Parts</p></div>
    <div class="card"><h2>$<?= number_format($totals['other'], 2) ?></h2><p>Other</p></div>
    <div class="card"><h2>$<?= number_format($totals['total'], 2) ?></h2><p>Total</p></div>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Tool</th><th>Work Orders</th><th>Labor</th><th>Parts</th><th>Other</th><th>Total</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <a href="<?= BASE_URL ?>/tools/view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['name']) ?></a><br>
                    <span class="muted"><?= e((string)$row['internal_id']) ?></span>
                </td>
                <td><?= (int)$row['work_order_count'] ?></td>
                <td>$<?= number_format((float)$row['labor_cost'], 2) ?></td>
                <td>$<?= number_format((float)$row['parts_cost'], 2) ?></td>
                <td>$<?= number_format((float)$row['other_cost'], 2) ?></td>
                <td><strong>$<?= number_format((float)$row['total_cost'], 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
