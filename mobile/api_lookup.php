<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$value = trim((string)($_GET['value'] ?? ''));

if ($value === '') {
    json_response(['success' => false, 'message' => 'A scan value is required.'], 400);
}

$tool = mobile_find_tool($value);

if ($tool !== null) {
    json_response([
        'success' => true,
        'type' => 'tool',
        'item' => $tool,
    ]);
}

$employee = mobile_find_employee($value);

if ($employee !== null) {
    json_response([
        'success' => true,
        'type' => 'employee',
        'item' => $employee,
    ]);
}

json_response([
    'success' => false,
    'message' => 'No matching tool or employee was found.',
], 404);
