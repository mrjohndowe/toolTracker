<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$rows = db()->query(
    'SELECT
        s.*,
        e.employee_number,
        CONCAT(e.first_name, " ", e.last_name) AS employee_name,
        t.internal_id,
        t.name AS tool_name,
        u.username
     FROM scan_history s
     LEFT JOIN employees e ON e.id = s.employee_id
     LEFT JOIN tools t ON t.id = s.tool_id
     LEFT JOIN users u ON u.id = s.scanned_by
     ORDER BY s.id DESC
     LIMIT 250'
)->fetchAll();

$pageTitle = 'Scan History';
require __DIR__ . '/../includes/header.php';
?>
<h1>Scan History</h1>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Value</th>
            <th>Employee</th>
            <th>Tool</th>
            <th>Result</th>
            <th>User</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['scanned_at']) ?></td>
                <td><?= e((string)$row['scan_type']) ?></td>
                <td><?= e((string)$row['scanned_value']) ?></td>
                <td><?= e((string)($row['employee_name'] ?? '')) ?></td>
                <td><?= e((string)($row['tool_name'] ?? '')) ?></td>
                <td>
                    <?= (int)$row['success'] === 1 ? 'Success' : 'Failed' ?>
                    <?= $row['message'] ? ' — ' . e((string)$row['message']) : '' ?>
                </td>
                <td><?= e((string)($row['username'] ?? 'System')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
