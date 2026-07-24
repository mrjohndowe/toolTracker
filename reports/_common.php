<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

function report_date(string $value, string $fallback): string
{
    $value = trim($value);
    $date = DateTime::createFromFormat('Y-m-d', $value);

    return ($date && $date->format('Y-m-d') === $value) ? $value : $fallback;
}

function csv_download(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $output = fopen('php://output', 'wb');
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

function report_status_badge(string $status): string
{
    return '<span class="badge">' . e($status) . '</span>';
}
