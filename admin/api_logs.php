<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('Administrator');

$rows = db()->query(
    'SELECT
        l.*, at.name AS token_name, at.token_prefix, u.username
     FROM api_request_logs l
     LEFT JOIN api_tokens at ON at.id = l.api_token_id
     LEFT JOIN users u ON u.id = l.user_id
     ORDER BY l.id DESC
     LIMIT 500'
)->fetchAll();

$pageTitle = 'API Request Logs';
require __DIR__ . '/../includes/header.php';
?>
<h1>API Request Logs</h1>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Method</th>
            <th>Endpoint</th>
            <th>Status</th>
            <th>Duration</th>
            <th>Token</th>
            <th>IP</th>
            <th>Request ID</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['created_at']) ?></td>
                <td><?= e((string)$row['method']) ?></td>
                <td><?= e((string)$row['endpoint']) ?></td>
                <td><?= (int)$row['status_code'] ?></td>
                <td><?= (int)$row['duration_ms'] ?> ms</td>
                <td><?= e((string)($row['token_name'] ?? 'Unknown')) ?></td>
                <td><?= e((string)($row['ip_address'] ?? '')) ?></td>
                <td><code><?= e((string)$row['request_id']) ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
