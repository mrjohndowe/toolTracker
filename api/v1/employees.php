<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate(['employees:read']);

[$page, $perPage, $offset] = api_pagination();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: null;

$where = ['e.active = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(e.employee_number LIKE ? OR e.badge_code LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($status !== '') {
    $where[] = 'e.status = ?';
    $params[] = $status;
}

if ($departmentId) {
    $where[] = 'e.department_id = ?';
    $params[] = $departmentId;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM employees e WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT
        e.id, e.employee_number, e.badge_code,
        e.first_name, e.last_name, e.email, e.phone,
        e.job_title, e.supervisor_name, e.hire_date, e.status,
        d.id AS department_id, d.name AS department_name,
        e.created_at, e.updated_at
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    WHERE {$whereSql}
    ORDER BY e.last_name, e.first_name
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = db()->prepare($sql);
$stmt->execute($params);

api_success($stmt->fetchAll(), 200, api_pagination_meta($page, $perPage, $total));
