<?php
declare(strict_types=1);

require_once __DIR__ . '/../locations/_common.php';
require_login();

$toolId = filter_input(INPUT_GET, 'tool_id', FILTER_VALIDATE_INT) ?: null;

$tools = db()->query(
    'SELECT id, internal_id, name
     FROM tools
     WHERE active = 1
     ORDER BY name'
)->fetchAll();

$rows = [];

if ($toolId) {
    $stmt = db()->prepare(
        'SELECT
            ch.*,
            lf.name AS from_location_name,
            lt.name AS to_location_name,
            fb.name AS from_bin_name,
            tb.name AS to_bin_name,
            u.username,
            tr.transfer_number
         FROM tool_custody_history ch
         LEFT JOIN locations lf ON lf.id = ch.from_location_id
         LEFT JOIN locations lt ON lt.id = ch.to_location_id
         LEFT JOIN storage_bins fb ON fb.id = ch.from_storage_bin_id
         LEFT JOIN storage_bins tb ON tb.id = ch.to_storage_bin_id
         LEFT JOIN users u ON u.id = ch.user_id
         LEFT JOIN transfer_requests tr ON tr.id = ch.transfer_id
         WHERE ch.tool_id = ?
         ORDER BY ch.created_at DESC, ch.id DESC'
    );
    $stmt->execute([$toolId]);
    $rows = $stmt->fetchAll();
}

$pageTitle = 'Chain of Custody';
require __DIR__ . '/../includes/header.php';
?>
<h1>Chain of Custody</h1>

<div class="card">
    <form method="get">
        <div class="form-group">
            <label>Tool</label>
            <select name="tool_id" required>
                <option value="">Select tool</option>
                <?php foreach ($tools as $tool): ?>
                    <option value="<?= (int)$tool['id'] ?>" <?= $toolId === (int)$tool['id'] ? 'selected' : '' ?>>
                        <?= e((string)$tool['name']) ?> — <?= e((string)$tool['internal_id']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn">Run Report</button>
    </form>
</div>

<?php if ($toolId): ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Date</th><th>Event</th><th>From</th><th>To</th><th>Transfer</th><th>User</th><th>Notes</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['created_at']) ?></td>
                <td><?= e((string)$row['event_type']) ?></td>
                <td>
                    <?= e((string)($row['from_location_name'] ?? '')) ?>
                    <?= !empty($row['from_bin_name']) ? ' / ' . e((string)$row['from_bin_name']) : '' ?>
                </td>
                <td>
                    <?= e((string)($row['to_location_name'] ?? '')) ?>
                    <?= !empty($row['to_bin_name']) ? ' / ' . e((string)$row['to_bin_name']) : '' ?>
                </td>
                <td><?= e((string)($row['transfer_number'] ?? '')) ?></td>
                <td><?= e((string)($row['username'] ?? 'System')) ?></td>
                <td><?= e((string)($row['notes'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
