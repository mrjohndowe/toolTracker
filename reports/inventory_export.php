<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$rows = db()->query(
    'SELECT
        t.internal_id, t.barcode, t.serial_number, t.name,
        t.manufacturer, t.model,
        c.name AS category_name,
        l.name AS location_name,
        t.status, t.tool_condition,
        t.purchase_date, t.replacement_value,
        t.active, t.created_at, t.updated_at
     FROM tools t
     LEFT JOIN tool_categories c ON c.id = t.category_id
     LEFT JOIN tool_locations l ON l.id = t.location_id
     ORDER BY t.name'
)->fetchAll();

$csv = [];
foreach ($rows as $row) {
    $csv[] = [
        $row['internal_id'],
        $row['barcode'],
        $row['serial_number'],
        $row['name'],
        $row['manufacturer'],
        $row['model'],
        $row['category_name'],
        $row['location_name'],
        $row['status'],
        $row['tool_condition'],
        $row['purchase_date'],
        $row['replacement_value'],
        (int)$row['active'] === 1 ? 'Yes' : 'No',
        $row['created_at'],
        $row['updated_at'],
    ];
}

csv_download(
    'tool-inventory-' . date('Y-m-d') . '.csv',
    [
        'Internal ID', 'Barcode', 'Serial Number', 'Tool Name',
        'Manufacturer', 'Model', 'Category', 'Location',
        'Status', 'Condition', 'Purchase Date', 'Replacement Value',
        'Active', 'Created At', 'Updated At'
    ],
    $csv
);
