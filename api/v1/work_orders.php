<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate(['maintenance:read']);

[$page, $perPage, $offset] = api_pagination();

$status = trim((string)($_GET['status'] ?? ''));
$toolId = filter_input(INPUT_GET, 'tool_id', FILTER_VALIDATE_INT) ?: null;

$where = ['1=1'];
$params = [];

if ($status !== '') {
    $where[] = 'wo.status = ?';
    $params[] = $status;
}

if ($toolId) {
    $where[] = 'wo.tool_id = ?';
    $params[] = $toolId;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM work_orders wo WHERE {$whereSql}"
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
    SELECT
        wo.id, wo.work_order_number, wo.tool_id,
        wo.title, wo.description, wo.priority, wo.status,
        wo.assigned_to, wo.vendor_name, wo.opened_date,
        wo.due_date, wo.completed_date,
        wo.labor_cost, wo.parts_cost, wo.other_cost,
        t.internal_id, t.name AS tool_name,
        mt.name AS maintenance_type_name
    FROM work_orders wo
    INNER JOIN tools t ON t.id = wo.tool_id
    LEFT JOIN maintenance_types mt ON mt.id = wo.maintenance_type_id
    WHERE {$whereSql}
    ORDER BY wo.opened_date DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = db()->prepare($sql);
$stmt->execute($params);

api_success($stmt->fetchAll(), 200, api_pagination_meta($page, $perPage, $total));
