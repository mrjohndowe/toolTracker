<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid tool ID.');
}

$tool = find_tool($id);
if ($tool === null) {
    http_response_code(404);
    exit('Tool not found.');
}

$values = [
    'internal_id' => $tool['internal_id'],
    'barcode' => $tool['barcode'],
    'serial_number' => $tool['serial_number'] ?? '',
    'name' => $tool['name'],
    'manufacturer' => $tool['manufacturer'] ?? '',
    'model' => $tool['model'] ?? '',
    'category_id' => $tool['category_id'],
    'location_id' => $tool['location_id'],
    'status' => $tool['status'],
    'tool_condition' => $tool['tool_condition'],
    'purchase_date' => $tool['purchase_date'] ?? '',
    'replacement_value' => $tool['replacement_value'],
    'notes' => $tool['notes'] ?? '',
    'active' => (int)$tool['active'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values = tool_form_values($_POST);
    $errors = validate_tool($values);

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'UPDATE tools
                 SET internal_id = ?, barcode = ?, serial_number = ?, name = ?,
                     manufacturer = ?, model = ?, category_id = ?, location_id = ?,
                     status = ?, tool_condition = ?, purchase_date = ?,
                     replacement_value = ?, notes = ?, active = ?
                 WHERE id = ?'
            );

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
                $id,
            ]);

            if (
                $tool['status'] !== $values['status'] ||
                $tool['tool_condition'] !== $values['tool_condition']
            ) {
                $user = current_user();

                db()->prepare(
                    'INSERT INTO tool_status_history
                     (tool_id, old_status, new_status, old_condition, new_condition, notes, changed_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $id,
                    $tool['status'],
                    $values['status'],
                    $tool['tool_condition'],
                    $values['tool_condition'],
                    'Tool record updated',
                    $user['id'] ?? null,
                ]);
            }

            audit_log('tool_updated', null, $values['internal_id'] . ' - ' . $values['name']);
            flash('success', 'Tool updated.');
            redirect('/tools/view.php?id=' . $id);
        } catch (PDOException $e) {
            $errors[] = 'Internal ID or barcode already exists.';
        }
    }
}

$pageTitle = 'Edit Tool';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Edit Tool</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php require __DIR__ . '/_form.php'; ?>

        <div class="actions">
            <button class="btn">Save Changes</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/tools/view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
