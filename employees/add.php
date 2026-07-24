<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$values = [
    'employee_number' => '',
    'badge_code' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'department_id' => null,
    'job_title' => '',
    'supervisor_name' => '',
    'hire_date' => '',
    'status' => 'Active',
    'notes' => '',
    'active' => 1,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values = employee_form_values($_POST);
    $errors = validate_employee($values);

    if (!$errors) {
        try {
            $user = current_user();

            $stmt = db()->prepare(
                'INSERT INTO employees
                 (employee_number, badge_code, first_name, last_name, email, phone,
                  department_id, job_title, supervisor_name, hire_date, status,
                  notes, active, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
                $user['id'] ?? null,
            ]);

            $employeeId = (int)db()->lastInsertId();
            record_employee_history($employeeId, 'employee_created', 'Employee record created');
            audit_log('employee_created', null, $values['employee_number']);

            flash('success', 'Employee added.');
            redirect('/employees/view.php?id=' . $employeeId);
        } catch (PDOException $e) {
            $errors[] = 'Employee number or badge code already exists.';
        }
    }
}

$pageTitle = 'Add Employee';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Add Employee</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php require __DIR__ . '/_form.php'; ?>

        <div class="actions">
            <button class="btn">Save Employee</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/employees/index.php">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
