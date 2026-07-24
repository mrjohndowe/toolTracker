<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

define('API_VERSION', 'v1');

function api_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$GLOBALS['api_request_id'] = api_uuid();
$GLOBALS['api_started_at'] = microtime(true);
$GLOBALS['api_token_row'] = null;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Request-ID: ' . $GLOBALS['api_request_id']);

function api_json(array $payload, int $status = 200): never
{
    http_response_code($status);

    if (!isset($payload['request_id'])) {
        $payload['request_id'] = $GLOBALS['api_request_id'];
    }

    api_log_request($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $message, int $status = 400, array $details = []): never
{
    api_json([
        'success' => false,
        'error' => [
            'message' => $message,
            'details' => $details,
        ],
    ], $status);
}

function api_success(array $data = [], int $status = 200, array $meta = []): never
{
    $payload = [
        'success' => true,
        'data' => $data,
    ];

    if ($meta) {
        $payload['meta'] = $meta;
    }

    api_json($payload, $status);
}

function api_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        api_error('Invalid JSON request body.', 400);
    }

    return $decoded;
}

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function api_authenticate(array $requiredScopes = []): array
{
    $token = api_bearer_token();

    if ($token === null || $token === '') {
        api_error('Missing Bearer token.', 401);
    }

    $hash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT
            at.*,
            u.username,
            u.role
         FROM api_tokens at
         LEFT JOIN users u ON u.id = at.user_id
         WHERE at.token_hash = ?
           AND at.active = 1
           AND (at.expires_at IS NULL OR at.expires_at > NOW())
         LIMIT 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    if (!is_array($row)) {
        api_error('Invalid or expired API token.', 401);
    }

    $scopes = [];
    if (!empty($row['scopes'])) {
        $decodedScopes = json_decode((string)$row['scopes'], true);
        if (is_array($decodedScopes)) {
            $scopes = $decodedScopes;
        }
    }

    if (!in_array('*', $scopes, true)) {
        foreach ($requiredScopes as $scope) {
            if (!in_array($scope, $scopes, true)) {
                api_error('API token does not have the required scope.', 403, [
                    'required_scope' => $scope,
                ]);
            }
        }
    }

    db()->prepare(
        'UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?'
    )->execute([(int)$row['id']]);

    $row['decoded_scopes'] = $scopes;
    $GLOBALS['api_token_row'] = $row;

    return $row;
}

function api_log_request(int $statusCode): void
{
    static $logged = false;
    if ($logged) {
        return;
    }
    $logged = true;

    try {
        $token = $GLOBALS['api_token_row'];
        $duration = (int)round((microtime(true) - $GLOBALS['api_started_at']) * 1000);
        $endpoint = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        db()->prepare(
            'INSERT INTO api_request_logs
             (api_token_id, user_id, method, endpoint, status_code,
              ip_address, user_agent, request_id, duration_ms)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $token['id'] ?? null,
            $token['user_id'] ?? null,
            $method,
            $endpoint,
            $statusCode,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            $GLOBALS['api_request_id'],
            $duration,
        ]);
    } catch (Throwable $e) {
        // Never break an API response because request logging failed.
    }
}

function api_require_method(string ...$allowed): void
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $allowed = array_map('strtoupper', $allowed);

    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        api_error('Method not allowed.', 405);
    }
}

function api_pagination(): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_GET['per_page'] ?? 25);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    return [$page, $perPage, $offset];
}

function api_pagination_meta(int $page, int $perPage, int $total): array
{
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => max(1, (int)ceil($total / $perPage)),
    ];
}

set_exception_handler(function (Throwable $e): void {
    api_error('Internal server error.', 500, [
        'exception' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : null,
    ]);
});
