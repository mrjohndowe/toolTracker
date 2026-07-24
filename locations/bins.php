<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);
    $name = trim((string)($_POST['name'] ?? ''));
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    $description = trim((string)($_POST['description'] ?? ''));

    if (!$locationId || $name === '' || $code === '') {
        flash('danger', 'Location, bin name, and code are required.');
    } else {
        try {
            db()->prepare(
                'INSERT INTO storage_bins
                 (location_id, name, code, description)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $locationId,
                $name,
                $code,
                $description !== '' ? $description : null,
            ]);

            flash('success', 'Storage bin created.');
        } catch (Throwable $e) {
            flash('danger', 'Unable to create bin: ' . $e->getMessage());
        }
    }

    redirect('/locations/bins.php');
}

$locations = locations_list();

$rows = db()->query(
    'SELECT
        sb.*,
        l.name AS location_name,
        COUNT(t.id) AS tool_count
     FROM storage_bins sb
     INNER JOIN locations l ON l.id = sb.location_id
     LEFT JOIN tools t ON t.storage_bin_id = sb.id AND t.active = 1
     GROUP BY sb.id
     ORDER BY l.name, sb.name'
)->fetchAll();

$pageTitle = 'Storage Bins';
require __DIR__ . '/../includes/header.php';
?>
<h1>Storage Bins</h1>

<div class="card">
    <h2>Create Bin</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Location</label>
                <select name="location_id" required>
                    <option value="">Select location</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?= (int)$location['id'] ?>"><?= e((string)$location['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Bin Name</label>
                <input name="name" required>
            </div>

            <div class="form-group">
                <label>Bin Code</label>
                <input name="code" required>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <input name="description">
        </div>

        <button class="btn">Create Bin</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>Location</th><th>Bin</th><th>Code</th><th>Tools</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['location_name']) ?></td>
                <td><?= e((string)$row['name']) ?></td>
                <td><?= e((string)$row['code']) ?></td>
                <td><?= (int)$row['tool_count'] ?></td>
                <td><?= (int)$row['active'] === 1 ? 'Active' : 'Inactive' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
