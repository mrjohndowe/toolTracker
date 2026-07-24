<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate(['tools:read']);

[$page, $perPage, $offset] = api_pagination();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$locationId = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT) ?: null;

$where = ['t.active = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(t.name LIKE ? OR t.internal_id LIKE ? OR t.barcode LIKE ? OR t.serial_number LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($status !== '') {
    $where[] = 't.status = ?';
    $params[] = $status;
}

if ($categoryId) {
    $where[] = 't.category_id = ?';
    $params[] = $categoryId;
}

if ($locationId) {
    $where[] = 't.location_id = ?';
    $params[] = $locationId;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM tools t WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT
        t.id, t.internal_id, t.barcode, t.serial_number, t.name,
        t.manufacturer, t.model, t.status, t.tool_condition,
        t.purchase_date, t.replacement_value, t.notes,
        c.id AS category_id, c.name AS category_name,
        l.id AS location_id, l.name AS location_name,
        t.created_at, t.updated_at
    FROM tools t
    LEFT JOIN tool_categories c ON c.id = t.category_id
    LEFT JOIN tool_locations l ON l.id = t.location_id
    WHERE {$whereSql}
    ORDER BY t.name
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = db()->prepare($sql);
$stmt->execute($params);

api_success($stmt->fetchAll(), 200, api_pagination_meta($page, $perPage, $total));
