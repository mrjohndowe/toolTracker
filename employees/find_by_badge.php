<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$badge = trim((string)($_GET['badge'] ?? ''));

if ($badge === '') {
    http_response_code(400);
    exit('Badge code is required.');
}

$stmt = db()->prepare(
    'SELECT id FROM employees
     WHERE badge_code = ? AND active = 1
     LIMIT 1'
);
$stmt->execute([$badge]);
$id = $stmt->fetchColumn();

if (!$id) {
    flash('danger', 'Employee badge not found.');
    redirect('/employees/index.php');
}

redirect('/employees/view.php?id=' . (int)$id);
