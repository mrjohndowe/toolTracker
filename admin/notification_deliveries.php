<?php
declare(strict_types=1);

require_once __DIR__ . '/../notifications/_common.php';
require_role('Administrator');

$rows = db()->query(
    'SELECT *
     FROM notification_deliveries
     ORDER BY id DESC
     LIMIT 500'
)->fetchAll();

$pageTitle = 'Notification Deliveries';
require __DIR__ . '/../includes/header.php';
?>
<h1>Notification Deliveries</h1>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Template</th>
            <th>Channel</th>
            <th>Recipient</th>
            <th>Status</th>
            <th>Sent</th>
            <th>Response</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['created_at']) ?></td>
                <td><?= e((string)($row['template_key'] ?? '')) ?></td>
                <td><?= e((string)$row['channel']) ?></td>
                <td><?= e((string)($row['recipient'] ?? '')) ?></td>
                <td><?= e((string)$row['status']) ?></td>
                <td><?= e((string)($row['sent_at'] ?? '')) ?></td>
                <td><?= e((string)($row['response_message'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
