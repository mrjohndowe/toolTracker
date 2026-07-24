<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';

api_require_method('GET');
api_authenticate([]);

api_success([
    'name' => 'ToolTrack Pro API',
    'version' => API_VERSION,
    'endpoints' => [
        'GET /api/v1/tools.php',
        'GET /api/v1/tool.php?id={id}',
        'GET /api/v1/employees.php',
        'GET /api/v1/employee.php?id={id}',
        'GET /api/v1/checkouts.php',
        'POST /api/v1/checkout.php',
        'POST /api/v1/return.php',
        'GET /api/v1/work_orders.php',
        'POST /api/v1/work_order.php',
    ],
]);
