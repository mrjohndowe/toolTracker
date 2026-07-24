<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function mobile_find_tool(string $value): ?array
{
    $stmt = db()->prepare(
        'SELECT id, internal_id, barcode, serial_number, name, status, tool_condition
         FROM tools
         WHERE active = 1
           AND (barcode = ? OR internal_id = ? OR serial_number = ?)
         LIMIT 1'
    );
    $stmt->execute([$value, $value, $value]);
    $tool = $stmt->fetch();

    return is_array($tool) ? $tool : null;
}

function mobile_find_employee(string $badge): ?array
{
    $stmt = db()->prepare(
        'SELECT id, employee_number, badge_code, first_name, last_name, status
         FROM employees
         WHERE badge_code = ? AND active = 1
         LIMIT 1'
    );
    $stmt->execute([$badge]);
    $employee = $stmt->fetch();

    return is_array($employee) ? $employee : null;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}
