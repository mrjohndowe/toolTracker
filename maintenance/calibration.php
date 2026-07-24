<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $toolId = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
    $certificateNumber = trim((string)($_POST['certificate_number'] ?? ''));
    $calibrationDate = trim((string)($_POST['calibration_date'] ?? ''));
    $nextDate = trim((string)($_POST['next_calibration_date'] ?? ''));
    $result = (string)($_POST['result'] ?? 'Passed');
    $performedBy = trim((string)($_POST['performed_by'] ?? ''));
    $standardsUsed = trim((string)($_POST['standards_used'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));

    if (!$toolId) $errors[] = 'Tool is required.';
    if ($calibrationDate === '') $errors[] = 'Calibration date is required.';
    if (!in_array($result, calibration_results(), true)) {
        $errors[] = 'Invalid calibration result.';
    }

    if (!$errors) {
        $user = current_user();

        db()->prepare(
            'INSERT INTO calibration_records
             (tool_id, certificate_number, calibration_date, next_calibration_date,
              result, performed_by, standards_used, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $toolId,
            $certificateNumber !== '' ? $certificateNumber : null,
            $calibrationDate,
            $nextDate !== '' ? $nextDate : null,
            $result,
            $performedBy !== '' ? $performedBy : null,
            $standardsUsed !== '' ? $standardsUsed : null,
            $notes !== '' ? $notes : null,
            $user['id'] ?? null,
        ]);

        if ($result === 'Failed') {
            db()->prepare(
                'UPDATE tools SET status = "Inspection" WHERE id = ?'
            )->execute([$toolId]);
        }

        audit_log('calibration_recorded', null, 'Tool ID ' . $toolId);
        flash('success', 'Calibration record saved.');
        redirect('/maintenance/calibration.php');
    }
}

$rows = db()->query(
    'SELECT
        cr.*,
        t.name AS tool_name,
        t.internal_id
     FROM calibration_records cr
     INNER JOIN tools t ON t.id = cr.tool_id
     ORDER BY cr.calibration_date DESC, cr.id DESC'
)->fetchAll();

$pageTitle = 'Calibration';
require __DIR__ . '/../includes/header.php';
?>
<h1>Calibration</h1>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <?php foreach ($errors as $error): ?>
            <div class="alert danger"><?= e($error) ?></div>
        <?php endforeach; ?>

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
                <label>Certificate Number</label>
                <input name="certificate_number">
            </div>

            <div class="form-group">
                <label>Calibration Date</label>
                <input type="date" name="calibration_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label>Next Calibration Date</label>
                <input type="date" name="next_calibration_date">
            </div>

            <div class="form-group">
                <label>Result</label>
                <select name="result">
                    <?php foreach (calibration_results() as $result): ?>
                        <option value="<?= e($result) ?>"><?= e($result) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Performed By</label>
                <input name="performed_by">
            </div>
        </div>

        <div class="form-group">
            <label>Standards Used</label>
            <input name="standards_used">
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="4" style="width:100%;padding:11px"></textarea>
        </div>

        <button class="btn">Save Calibration</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Tool</th><th>Date</th><th>Next Date</th><th>Result</th><th>Certificate</th><th>Performed By</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <?= e((string)$row['tool_name']) ?><br>
                    <span class="muted"><?= e((string)$row['internal_id']) ?></span>
                </td>
                <td><?= e((string)$row['calibration_date']) ?></td>
                <td><?= e((string)($row['next_calibration_date'] ?? '')) ?></td>
                <td><span class="badge"><?= e((string)$row['result']) ?></span></td>
                <td><?= e((string)($row['certificate_number'] ?? '')) ?></td>
                <td><?= e((string)($row['performed_by'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
