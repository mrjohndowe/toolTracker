<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
header('Content-Type: text/plain; charset=utf-8');
echo "BASE_URL=" . (defined('BASE_URL') ? BASE_URL : '[not defined]') . "\n";
echo "Inspection queue URL=" . inspection_url('/inspections/queue.php') . "\n";
echo "Current directory=" . __DIR__ . "\n";
echo "queue.php exists=" . (is_file(__DIR__ . '/queue.php') ? 'yes' : 'no') . "\n";
