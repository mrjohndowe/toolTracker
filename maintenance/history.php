<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$q = trim((string)($_GET['q'] ?? ''));

$sql = '
    SELECT
        wo.id, wo.work_order_number, wo.title, wo.priority, wo.status,
        wo.opened_date, wo.completed_date, wo.labor_cost, wo.parts_cost, wo.other_cost,
        t.name AS tool_name, t.internal_id
    FROM work_orders wo
    INNER JOIN tools t ON t.id = wo.tool_id
    WHERE 1=1
';
$params = [];

if ($q !== '') {
    $sql .= ' AND (
        wo.work_order_number LIKE ? OR wo.title LIKE ?
        OR t.name LIKE ? OR t.internal_id LIKE ?
    )';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

$sql .= ' ORDER BY wo.opened_date DESC LIMIT 500';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Maintenance History';
require __DIR__ . '/../includes/header.php';
?>
<h1>Maintenance History</h1>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Search</label>
            <input name="q" value="<?= e($q) ?>" placeholder="Work order or tool">
        </div>
        <button class="btn">Search</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Work Order</th><th>Tool</th><th>Status</th><th>Opened</th><th>Completed</th><th>Total Cost</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <?= e((string)$row['work_order_number']) ?><br>
                    <span class="muted"><?= e((string)$row['title']) ?></span>
                </td>
                <td>
                    <?= e((string)$row['tool_name']) ?><br>
                    <span class="muted"><?= e((string)$row['internal_id']) ?></span>
                </td>
                <td><?= e((string)$row['status']) ?></td>
                <td><?= e((string)$row['opened_date']) ?></td>
                <td><?= e((string)($row['completed_date'] ?? '')) ?></td>
                <td>
                    $<?= number_format(
                        (float)$row['labor_cost'] + (float)$row['parts_cost'] + (float)$row['other_cost'],
                        2
                    ) ?>
                </td>
                <td>
                    <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/work_order_view.php?id=<?= (int)$row['id'] ?>">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
