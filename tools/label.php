<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$tool = $id ? find_tool($id) : null;

if ($tool === null) {
    http_response_code(404);
    exit('Tool not found.');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= e((string)$tool['name']) ?> Label</title>
<style>
body{font-family:Arial,sans-serif;margin:20px}
.label{width:3.5in;min-height:2in;border:2px solid #000;padding:14px}
.company{font-size:18px;font-weight:bold}
.tool{font-size:22px;font-weight:bold;margin:10px 0}
.code{font-family:monospace;font-size:24px;letter-spacing:2px;border-top:1px solid #000;border-bottom:1px solid #000;padding:8px 0;margin:10px 0}
.small{font-size:12px}
@media print{button{display:none}body{margin:0}.label{page-break-after:always}}
</style>
</head>
<body>
<button onclick="window.print()">Print</button>
<div class="label">
    <div class="company"><?= e(APP_NAME) ?></div>
    <div class="tool"><?= e((string)$tool['name']) ?></div>
    <div><strong>ID:</strong> <?= e((string)$tool['internal_id']) ?></div>
    <div><strong>Serial:</strong> <?= e((string)($tool['serial_number'] ?? '')) ?></div>
    <div class="code"><?= e((string)$tool['barcode']) ?></div>
    <div class="small"><?= e(trim((string)$tool['manufacturer'] . ' ' . (string)$tool['model'])) ?></div>
</div>
</body>
</html>
