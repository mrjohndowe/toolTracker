<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$values = [
    'internal_id' => '',
    'barcode' => '',
    'serial_number' => '',
    'name' => '',
    'manufacturer' => '',
    'model' => '',
    'category_id' => null,
    'location_id' => null,
    'status' => 'Available',
    'tool_condition' => 'Good',
    'purchase_date' => '',
    'replacement_value' => 0,
    'notes' => '',
    'active' => 1,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values = tool_form_values($_POST);
    $errors = validate_tool($values);

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO tools
                 (internal_id, barcode, serial_number, name, manufacturer, model,
                  category_id, location_id, status, tool_condition, purchase_date,
                  replacement_value, notes, active, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $user = current_user();
            $stmt->execute([
                $values['internal_id'],
                $values['barcode'],
                $values['serial_number'] !== '' ? $values['serial_number'] : null,
                $values['name'],
                $values['manufacturer'] !== '' ? $values['manufacturer'] : null,
                $values['model'] !== '' ? $values['model'] : null,
                $values['category_id'],
                $values['location_id'],
                $values['status'],
                $values['tool_condition'],
                $values['purchase_date'] !== '' ? $values['purchase_date'] : null,
                $values['replacement_value'],
                $values['notes'] !== '' ? $values['notes'] : null,
                $values['active'],
                $user['id'] ?? null,
            ]);

            $toolId = (int)db()->lastInsertId();

            db()->prepare(
                'INSERT INTO tool_status_history
                 (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
                 VALUES (?, NULL, ?, NULL, ?, ?, ?)'
            )->execute([
                $toolId,
                $values['status'],
                $values['tool_condition'],
                'Tool created',
                $user['id'] ?? null,
            ]);

            audit_log('tool_created', null, $values['internal_id'] . ' - ' . $values['name']);
            flash('success', 'Tool added.');
            redirect('/tools/view.php?id=' . $toolId);
        } catch (PDOException $e) {
            $errors[] = 'Internal ID or barcode already exists.';
        }
    }
}

$pageTitle = 'Add Tool';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Add Tool</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php require __DIR__ . '/_form.php'; ?>

        <div class="actions">
            <button class="btn">Save Tool</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/tools/index.php">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
