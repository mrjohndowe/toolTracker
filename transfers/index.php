<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

$status = trim((string)($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'tr.status = ?';
    $params[] = $status;
}

$sql = '
    SELECT
        tr.*,
        lf.name AS from_location_name,
        lt.name AS to_location_name,
        COUNT(ti.id) AS item_count
    FROM transfer_requests tr
    INNER JOIN locations lf ON lf.id = tr.from_location_id
    INNER JOIN locations lt ON lt.id = tr.to_location_id
    LEFT JOIN transfer_items ti ON ti.transfer_id = tr.id
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY tr.id ORDER BY tr.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Transfers';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Transfers</h1>
    <a class="btn" href="<?= BASE_URL ?>/transfers/create.php">New Transfer</a>
</div>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach (['Draft','Pending Approval','Approved','In Transit','Received','Rejected','Cancelled'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn secondary">Filter</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Transfer</th><th>From</th><th>To</th><th>Items</th><th>Status</th><th>Requested</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['transfer_number']) ?></td>
                <td><?= e((string)$row['from_location_name']) ?></td>
                <td><?= e((string)$row['to_location_name']) ?></td>
                <td><?= (int)$row['item_count'] ?></td>
                <td><?= e((string)$row['status']) ?></td>
                <td><?= e((string)$row['requested_at']) ?></td>
                <td><a class="btn secondary" href="<?= BASE_URL ?>/transfers/view.php?id=<?= (int)$row['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
