<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid employee ID.');
}

$employee = find_employee($id);
if ($employee === null) {
    http_response_code(404);
    exit('Employee not found.');
}

$values = [
    'employee_number' => $employee['employee_number'],
    'badge_code' => $employee['badge_code'],
    'first_name' => $employee['first_name'],
    'last_name' => $employee['last_name'],
    'email' => $employee['email'] ?? '',
    'phone' => $employee['phone'] ?? '',
    'department_id' => $employee['department_id'],
    'job_title' => $employee['job_title'] ?? '',
    'supervisor_name' => $employee['supervisor_name'] ?? '',
    'hire_date' => $employee['hire_date'] ?? '',
    'status' => $employee['status'],
    'notes' => $employee['notes'] ?? '',
    'active' => (int)$employee['active'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values = employee_form_values($_POST);
    $errors = validate_employee($values);

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'UPDATE employees
                 SET employee_number = ?, badge_code = ?, first_name = ?, last_name = ?,
                     email = ?, phone = ?, department_id = ?, job_title = ?,
                     supervisor_name = ?, hire_date = ?, status = ?, notes = ?, active = ?
                 WHERE id = ?'
            );

            $stmt->execute([
                $values['employee_number'],
                $values['badge_code'],
                $values['first_name'],
                $values['last_name'],
                $values['email'] !== '' ? $values['email'] : null,
                $values['phone'] !== '' ? $values['phone'] : null,
                $values['department_id'],
                $values['job_title'] !== '' ? $values['job_title'] : null,
                $values['supervisor_name'] !== '' ? $values['supervisor_name'] : null,
                $values['hire_date'] !== '' ? $values['hire_date'] : null,
                $values['status'],
                $values['notes'] !== '' ? $values['notes'] : null,
                $values['active'],
                $id,
            ]);

            record_employee_history($id, 'employee_updated', 'Employee record updated');
            audit_log('employee_updated', null, $values['employee_number']);

            flash('success', 'Employee updated.');
            redirect('/employees/view.php?id=' . $id);
        } catch (PDOException $e) {
            $errors[] = 'Employee number or badge code already exists.';
        }
    }
}

$pageTitle = 'Edit Employee';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Edit Employee</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php require __DIR__ . '/_form.php'; ?>

        <div class="actions">
            <button class="btn">Save Changes</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/employees/view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
