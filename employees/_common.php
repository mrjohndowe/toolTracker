<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function employee_statuses(): array
{
    return ['Active', 'Inactive', 'Suspended', 'Terminated'];
}

function employee_departments(): array
{
    return db()->query(
        'SELECT id, name FROM departments WHERE active = 1 ORDER BY name'
    )->fetchAll();
}

function find_employee(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT e.*, d.name AS department_name
         FROM employees e
         LEFT JOIN departments d ON d.id = e.department_id
         WHERE e.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $employee = $stmt->fetch();

    return is_array($employee) ? $employee : null;
}

function employee_form_values(array $source): array
{
    return [
        'employee_number' => trim((string)($source['employee_number'] ?? '')),
        'badge_code' => trim((string)($source['badge_code'] ?? '')),
        'first_name' => trim((string)($source['first_name'] ?? '')),
        'last_name' => trim((string)($source['last_name'] ?? '')),
        'email' => trim((string)($source['email'] ?? '')),
        'phone' => trim((string)($source['phone'] ?? '')),
        'department_id' => filter_var($source['department_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        'job_title' => trim((string)($source['job_title'] ?? '')),
        'supervisor_name' => trim((string)($source['supervisor_name'] ?? '')),
        'hire_date' => trim((string)($source['hire_date'] ?? '')),
        'status' => (string)($source['status'] ?? 'Active'),
        'notes' => trim((string)($source['notes'] ?? '')),
        'active' => isset($source['active']) ? 1 : 0,
    ];
}

function validate_employee(array $data): array
{
    $errors = [];

    if ($data['employee_number'] === '') $errors[] = 'Employee number is required.';
    if ($data['badge_code'] === '') $errors[] = 'Badge code is required.';
    if ($data['first_name'] === '') $errors[] = 'First name is required.';
    if ($data['last_name'] === '') $errors[] = 'Last name is required.';
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!in_array($data['status'], employee_statuses(), true)) {
        $errors[] = 'Invalid employee status.';
    }

    return $errors;
}

function employee_upload_directory(): string
{
    return __DIR__ . '/../uploads/employees';
}

function ensure_employee_upload_directory(): void
{
    $dir = employee_upload_directory();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function record_employee_history(int $employeeId, string $action, ?string $details = null): void
{
    $user = current_user();

    db()->prepare(
        'INSERT INTO employee_history (employee_id, action, details, changed_by)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $employeeId,
        $action,
        $details,
        $user['id'] ?? null,
    ]);
}
