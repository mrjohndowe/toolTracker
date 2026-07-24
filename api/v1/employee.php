<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate(['employees:read']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    api_error('A valid employee id is required.', 422);
}

$stmt = db()->prepare(
    'SELECT e.*, d.name AS department_name
     FROM employees e
     LEFT JOIN departments d ON d.id = e.department_id
     WHERE e.id = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!is_array($employee)) {
    api_error('Employee not found.', 404);
}

$checkoutStmt = db()->prepare(
    'SELECT id, transaction_number, checkout_date, due_date,
            returned_date, status
     FROM checkout_transactions
     WHERE employee_id = ?
     ORDER BY checkout_date DESC
     LIMIT 100'
);
$checkoutStmt->execute([$id]);

$employee['checkout_history'] = $checkoutStmt->fetchAll();

api_success($employee);
