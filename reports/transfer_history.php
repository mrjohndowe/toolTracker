<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

$rows = db()->query(
    'SELECT
        tr.id, tr.transfer_number, tr.status,
        tr.requested_at, tr.approved_at, tr.shipped_at, tr.received_at,
        lf.name AS from_location_name,
        lt.name AS to_location_name,
        COUNT(ti.id) AS item_count
     FROM transfer_requests tr
     INNER JOIN locations lf ON lf.id = tr.from_location_id
     INNER JOIN locations lt ON lt.id = tr.to_location_id
     LEFT JOIN transfer_items ti ON ti.transfer_id = tr.id
     GROUP BY tr.id
     ORDER BY tr.id DESC'
)->fetchAll();

$pageTitle = 'Transfer History';
require __DIR__ . '/../includes/header.php';
?>
<h1>Transfer History</h1>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Transfer</th><th>From</th><th>To</th><th>Items</th><th>Status</th><th>Requested</th><th>Received</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><a href="<?= BASE_URL ?>/transfers/view.php?id=<?= (int)$row['id'] ?>"><?= e((string)$row['transfer_number']) ?></a></td>
                <td><?= e((string)$row['from_location_name']) ?></td>
                <td><?= e((string)$row['to_location_name']) ?></td>
                <td><?= (int)$row['item_count'] ?></td>
                <td><?= e((string)$row['status']) ?></td>
                <td><?= e((string)$row['requested_at']) ?></td>
                <td><?= e((string)($row['received_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
