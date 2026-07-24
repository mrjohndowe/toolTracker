<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate(['tools:read']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    api_error('A valid tool id is required.', 422);
}

$stmt = db()->prepare(
    'SELECT
        t.*,
        c.name AS category_name,
        l.name AS location_name
     FROM tools t
     LEFT JOIN tool_categories c ON c.id = t.category_id
     LEFT JOIN tool_locations l ON l.id = t.location_id
     WHERE t.id = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$tool = $stmt->fetch();

if (!is_array($tool)) {
    api_error('Tool not found.', 404);
}

$historyStmt = db()->prepare(
    'SELECT old_status, new_status, old_condition, new_condition,
            notes, created_at
     FROM tool_status_history
     WHERE tool_id = ?
     ORDER BY id DESC
     LIMIT 100'
);
$historyStmt->execute([$id]);

$tool['status_history'] = $historyStmt->fetchAll();

api_success($tool);
