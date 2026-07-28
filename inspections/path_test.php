<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_login();
header('Content-Type: text/plain; charset=utf-8');
echo 'BASE_URL=' . BASE_URL . "\n";
echo 'Queue redirect path=' . inspection_path('/inspections/queue.php') . "\n";
echo 'Queue public URL=' . inspection_public_url('/inspections/queue.php') . "\n";
echo 'queue.php exists=' . (is_file(__DIR__ . '/queue.php') ? 'yes' : 'no') . "\n";
