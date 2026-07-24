<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function tool_statuses(): array
{
    return ['Available', 'Checked Out', 'Inspection', 'Repair', 'Retired'];
}

function tool_conditions(): array
{
    return ['Excellent', 'Good', 'Fair', 'Poor'];
}

function tool_categories(): array
{
    return db()->query(
        'SELECT id, name FROM tool_categories WHERE active = 1 ORDER BY name'
    )->fetchAll();
}

function tool_locations(): array
{
    return db()->query(
        'SELECT id, name FROM tool_locations WHERE active = 1 ORDER BY name'
    )->fetchAll();
}

function find_tool(int $id): ?array
{
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

    return is_array($tool) ? $tool : null;
}

function tool_form_values(array $source): array
{
    return [
        'internal_id' => trim((string)($source['internal_id'] ?? '')),
        'barcode' => trim((string)($source['barcode'] ?? '')),
        'serial_number' => trim((string)($source['serial_number'] ?? '')),
        'name' => trim((string)($source['name'] ?? '')),
        'manufacturer' => trim((string)($source['manufacturer'] ?? '')),
        'model' => trim((string)($source['model'] ?? '')),
        'category_id' => filter_var($source['category_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        'location_id' => filter_var($source['location_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
        'status' => (string)($source['status'] ?? 'Available'),
        'tool_condition' => (string)($source['tool_condition'] ?? 'Good'),
        'purchase_date' => trim((string)($source['purchase_date'] ?? '')),
        'replacement_value' => (float)($source['replacement_value'] ?? 0),
        'notes' => trim((string)($source['notes'] ?? '')),
        'active' => isset($source['active']) ? 1 : 0,
    ];
}

function validate_tool(array $data): array
{
    $errors = [];

    if ($data['internal_id'] === '') $errors[] = 'Internal ID is required.';
    if ($data['barcode'] === '') $errors[] = 'Barcode is required.';
    if ($data['name'] === '') $errors[] = 'Tool name is required.';
    if (!in_array($data['status'], tool_statuses(), true)) $errors[] = 'Invalid status.';
    if (!in_array($data['tool_condition'], tool_conditions(), true)) $errors[] = 'Invalid condition.';
    if ($data['replacement_value'] < 0) $errors[] = 'Replacement value cannot be negative.';

    return $errors;
}

function upload_directory(): string
{
    return __DIR__ . '/../uploads/tools';
}

function ensure_upload_directory(): void
{
    $dir = upload_directory();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
