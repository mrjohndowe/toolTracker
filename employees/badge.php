<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$employee = $id ? find_employee($id) : null;

if ($employee === null) {
    http_response_code(404);
    exit('Employee not found.');
}

$stmt = db()->prepare(
    'SELECT filename FROM employee_photos
     WHERE employee_id = ?
     ORDER BY is_primary DESC, id DESC
     LIMIT 1'
);
$stmt->execute([$id]);
$photo = $stmt->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Employee Badge</title>
<style>
body{font-family:Arial,sans-serif;margin:20px}
.badge-card{width:3.375in;height:2.125in;border:2px solid #111;padding:12px;display:flex;gap:12px;align-items:center}
.photo{width:1in;height:1.25in;border:1px solid #777;object-fit:cover;background:#eee}
.info{flex:1}
.company{font-weight:bold;font-size:16px}
.name{font-size:19px;font-weight:bold;margin:8px 0}
.code{font-family:monospace;font-size:17px;border-top:1px solid #222;border-bottom:1px solid #222;padding:6px 0;margin-top:8px}
.small{font-size:11px}
@media print{button{display:none}body{margin:0}}
</style>
</head>
<body>
<button onclick="window.print()">Print</button>
<div class="badge-card">
    <div>
        <?php if ($photo): ?>
            <img class="photo" src="<?= BASE_URL ?>/uploads/employees/<?= e((string)$photo) ?>" alt="">
        <?php else: ?>
            <div class="photo"></div>
        <?php endif; ?>
    </div>

    <div class="info">
        <div class="company"><?= e(APP_NAME) ?></div>
        <div class="name"><?= e((string)$employee['first_name'] . ' ' . (string)$employee['last_name']) ?></div>
        <div><?= e((string)($employee['job_title'] ?? 'Employee')) ?></div>
        <div class="small">Employee #<?= e((string)$employee['employee_number']) ?></div>
        <div class="code"><?= e((string)$employee['badge_code']) ?></div>
    </div>
</div>
</body>
</html>
