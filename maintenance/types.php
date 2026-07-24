<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $interval = filter_input(INPUT_POST, 'default_interval_days', FILTER_VALIDATE_INT);
    $requiresCalibration = isset($_POST['requires_calibration']) ? 1 : 0;

    if ($name === '') {
        flash('danger', 'Maintenance type name is required.');
    } else {
        try {
            db()->prepare(
                'INSERT INTO maintenance_types
                 (name, description, default_interval_days, requires_calibration)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $name,
                $description !== '' ? $description : null,
                $interval ?: null,
                $requiresCalibration,
            ]);

            audit_log('maintenance_type_created', null, $name);
            flash('success', 'Maintenance type added.');
        } catch (PDOException $e) {
            flash('danger', 'That maintenance type already exists.');
        }
    }

    redirect('/maintenance/types.php');
}

if (isset($_GET['toggle'])) {
    $id = filter_input(INPUT_GET, 'toggle', FILTER_VALIDATE_INT);
    if ($id) {
        db()->prepare(
            'UPDATE maintenance_types SET active = IF(active=1,0,1) WHERE id = ?'
        )->execute([$id]);
        flash('success', 'Maintenance type status updated.');
    }
    redirect('/maintenance/types.php');
}

$rows = db()->query(
    'SELECT * FROM maintenance_types ORDER BY active DESC, name'
)->fetchAll();

$pageTitle = 'Maintenance Types';
require __DIR__ . '/../includes/header.php';
?>
<h1>Maintenance Types</h1>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Name</label>
                <input name="name" required>
            </div>

            <div class="form-group">
                <label>Default Interval (Days)</label>
                <input type="number" min="1" name="default_interval_days">
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <input name="description">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="requires_calibration" style="width:auto">
                This type requires calibration tracking
            </label>
        </div>

        <button class="btn">Add Type</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr><th>Name</th><th>Interval</th><th>Calibration</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['name']) ?></td>
                <td><?= e((string)($row['default_interval_days'] ?? '')) ?></td>
                <td><?= (int)$row['requires_calibration'] === 1 ? 'Yes' : 'No' ?></td>
                <td><?= (int)$row['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                <td><a class="btn secondary" href="?toggle=<?= (int)$row['id'] ?>">Toggle</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
