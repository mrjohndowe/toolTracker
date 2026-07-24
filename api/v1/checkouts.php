<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate(['checkout:read']);

[$page, $perPage, $offset] = api_pagination();

$status = trim((string)($_GET['status'] ?? ''));
$employeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT) ?: null;

$where = ['1=1'];
$params = [];

if ($status !== '') {
    $where[] = 'ct.status = ?';
    $params[] = $status;
}

if ($employeeId) {
    $where[] = 'ct.employee_id = ?';
    $params[] = $employeeId;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM checkout_transactions ct WHERE {$whereSql}"
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT
        ct.id, ct.transaction_number, ct.employee_id,
        ct.checkout_date, ct.due_date, ct.returned_date,
        ct.status, ct.notes,
        e.employee_number, e.first_name, e.last_name,
        COUNT(ci.id) AS item_count,
        SUM(ci.return_status = 'Pending') AS outstanding_count
    FROM checkout_transactions ct
    INNER JOIN employees e ON e.id = ct.employee_id
    LEFT JOIN checkout_items ci ON ci.transaction_id = ct.id
    WHERE {$whereSql}
    GROUP BY ct.id
    ORDER BY ct.checkout_date DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = db()->prepare($sql);
$stmt->execute($params);

api_success($stmt->fetchAll(), 200, api_pagination_meta($page, $perPage, $total));
