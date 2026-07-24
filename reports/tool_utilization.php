<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$defaultFrom = date('Y-m-d', strtotime('-90 days'));
$defaultTo = date('Y-m-d');

$from = report_date((string)($_GET['from'] ?? ''), $defaultFrom);
$to = report_date((string)($_GET['to'] ?? ''), $defaultTo);

$stmt = db()->prepare(
    'SELECT
        t.id, t.internal_id, t.name, t.status,
        COUNT(ci.id) AS checkout_count,
        COALESCE(SUM(
            TIMESTAMPDIFF(
                HOUR,
                ct.checkout_date,
                COALESCE(ci.returned_at, NOW())
            )
        ), 0) AS hours_checked_out,
        MAX(ct.checkout_date) AS last_checkout
     FROM tools t
     LEFT JOIN checkout_items ci ON ci.tool_id = t.id
     LEFT JOIN checkout_transactions ct
        ON ct.id = ci.transaction_id
       AND DATE(ct.checkout_date) BETWEEN ? AND ?
     WHERE t.active = 1
     GROUP BY t.id
     ORDER BY checkout_count DESC, hours_checked_out DESC, t.name'
);
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

if (isset($_GET['export'])) {
    $csv = [];
    foreach ($rows as $row) {
        $csv[] = [
            $row['internal_id'],
            $row['name'],
            $row['status'],
            $row['checkout_count'],
            $row['hours_checked_out'],
            $row['last_checkout'],
        ];
    }

    csv_download(
        'tool-utilization-' . $from . '-to-' . $to . '.csv',
        ['Internal ID', 'Tool', 'Status', 'Checkout Count', 'Hours Checked Out', 'Last Checkout'],
        $csv
    );
}

$pageTitle = 'Tool Utilization';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Tool Utilization</h1>
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

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Tool</th><th>Status</th><th>Checkouts</th><th>Hours Out</th><th>Last Checkout</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <a href="<?= BASE_URL ?>/tools/view.php?id=<?= (int)$row['id'] ?>">
                        <?= e((string)$row['name']) ?>
                    </a><br>
                    <span class="muted"><?= e((string)$row['internal_id']) ?></span>
                </td>
                <td><?= report_status_badge((string)$row['status']) ?></td>
                <td><?= (int)$row['checkout_count'] ?></td>
                <td><?= number_format((float)$row['hours_checked_out'], 1) ?></td>
                <td><?= e((string)($row['last_checkout'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
