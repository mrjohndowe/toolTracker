<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    if ($name === '') {
        flash('danger', 'Location name is required.');
    } else {
        try {
            db()->prepare(
                'INSERT INTO tool_locations (name, description) VALUES (?, ?)'
            )->execute([$name, $description !== '' ? $description : null]);

            audit_log('location_created', null, $name);
            flash('success', 'Location added.');
        } catch (PDOException $e) {
            flash('danger', 'That location already exists.');
        }
    }

    redirect('/tools/locations.php');
}

if (isset($_GET['toggle'])) {
    $id = filter_input(INPUT_GET, 'toggle', FILTER_VALIDATE_INT);
    if ($id) {
        db()->prepare('UPDATE tool_locations SET active = IF(active=1,0,1) WHERE id = ?')->execute([$id]);
        flash('success', 'Location status updated.');
    }
    redirect('/tools/locations.php');
}

$rows = db()->query('SELECT * FROM tool_locations ORDER BY active DESC, name')->fetchAll();

$pageTitle = 'Tool Locations';
require __DIR__ . '/../includes/header.php';
?>
<h1>Tool Locations</h1>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="grid">
            <div class="form-group">
                <label>Name</label>
                <input name="name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input name="description">
            </div>
        </div>
        <button class="btn">Add Location</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>Name</th><th>Description</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['name']) ?></td>
                <td><?= e((string)($row['description'] ?? '')) ?></td>
                <td><?= (int)$row['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                <td><a class="btn secondary" href="?toggle=<?= (int)$row['id'] ?>">Toggle</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
