<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $toolId = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
    $typeId = filter_input(INPUT_POST, 'maintenance_type_id', FILTER_VALIDATE_INT);
    $intervalDays = filter_input(INPUT_POST, 'interval_days', FILTER_VALIDATE_INT);
    $lastService = trim((string)($_POST['last_service_date'] ?? ''));
    $nextService = trim((string)($_POST['next_service_date'] ?? ''));
    $reminderDays = filter_input(INPUT_POST, 'reminder_days', FILTER_VALIDATE_INT) ?: 14;
    $notes = trim((string)($_POST['notes'] ?? ''));

    if (!$toolId || !$typeId) {
        flash('danger', 'Tool and maintenance type are required.');
    } else {
        try {
            $user = current_user();

            db()->prepare(
                'INSERT INTO maintenance_schedules
                 (tool_id, maintenance_type_id, interval_days, last_service_date,
                  next_service_date, reminder_days, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $toolId,
                $typeId,
                $intervalDays ?: null,
                $lastService !== '' ? $lastService : null,
                $nextService !== '' ? $nextService : null,
                $reminderDays,
                $notes !== '' ? $notes : null,
                $user['id'] ?? null,
            ]);

            audit_log('maintenance_schedule_created', null, 'Tool ID ' . $toolId);
            flash('success', 'Maintenance schedule added.');
        } catch (PDOException $e) {
            flash('danger', 'This tool already has that maintenance schedule.');
        }
    }

    redirect('/maintenance/schedules.php');
}

$rows = db()->query(
    'SELECT
        ms.*,
        t.name AS tool_name,
        t.internal_id,
        mt.name AS maintenance_type_name
     FROM maintenance_schedules ms
     INNER JOIN tools t ON t.id = ms.tool_id
     INNER JOIN maintenance_types mt ON mt.id = ms.maintenance_type_id
     ORDER BY
        ms.next_service_date IS NULL,
        ms.next_service_date,
        t.name'
)->fetchAll();

$pageTitle = 'Maintenance Schedules';
require __DIR__ . '/../includes/header.php';
?>
<h1>Maintenance Schedules</h1>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Tool</label>
                <select name="tool_id" required>
                    <option value="">Select tool</option>
                    <?php foreach (maintenance_tools_list() as $tool): ?>
                        <option value="<?= (int)$tool['id'] ?>">
                            <?= e((string)$tool['name']) ?> — <?= e((string)$tool['internal_id']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Maintenance Type</label>
                <select name="maintenance_type_id" required>
                    <option value="">Select type</option>
                    <?php foreach (maintenance_types_list() as $type): ?>
                        <option value="<?= (int)$type['id'] ?>">
                            <?= e((string)$type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Interval Days</label>
                <input type="number" min="1" name="interval_days">
            </div>

            <div class="form-group">
                <label>Reminder Days</label>
                <input type="number" min="0" name="reminder_days" value="14">
            </div>

            <div class="form-group">
                <label>Last Service Date</label>
                <input type="date" name="last_service_date">
            </div>

            <div class="form-group">
                <label>Next Service Date</label>
                <input type="date" name="next_service_date">
            </div>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3" style="width:100%;padding:11px"></textarea>
        </div>

        <button class="btn">Add Schedule</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Tool</th><th>Type</th><th>Last Service</th><th>Next Service</th><th>Interval</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $status = 'Scheduled';
            if ($row['next_service_date'] && $row['next_service_date'] < date('Y-m-d')) {
                $status = 'Overdue';
            } elseif ($row['next_service_date'] && $row['next_service_date'] <= date('Y-m-d', strtotime('+14 days'))) {
                $status = 'Due Soon';
            }
            ?>
            <tr>
                <td>
                    <?= e((string)$row['tool_name']) ?><br>
                    <span class="muted"><?= e((string)$row['internal_id']) ?></span>
                </td>
                <td><?= e((string)$row['maintenance_type_name']) ?></td>
                <td><?= e((string)($row['last_service_date'] ?? '')) ?></td>
                <td><?= e((string)($row['next_service_date'] ?? '')) ?></td>
                <td><?= e((string)($row['interval_days'] ?? '')) ?></td>
                <td><span class="badge"><?= e($status) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
