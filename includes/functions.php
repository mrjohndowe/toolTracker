<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(419);
        exit('Invalid or expired request token.');
    }
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function require_login(): void
{
    if (current_user() === null) {
        redirect('/login.php');
    }

    $lastActivity = $_SESSION['last_activity'] ?? time();

    if (!is_int($lastActivity)) {
        $lastActivity = time();
    }

    if ((time() - $lastActivity) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        flash('warning', 'Your session expired. Please log in again.');
        redirect('/login.php');
    }

    $_SESSION['last_activity'] = time();
}

function require_role(string $roleName): void
{
    require_login();

    $user = current_user();
    if (($user['role_name'] ?? '') !== $roleName) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function audit_log(string $action, ?int $targetUserId = null, ?string $details = null): void
{
    try {
        $user = current_user();

        $stmt = db()->prepare(
            'INSERT INTO activity_logs
             (user_id, target_user_id, action, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $user['id'] ?? null,
            $targetUserId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $e) {
        // Do not interrupt the main request when audit logging fails.
    }
}

function login_rate_limited(string $username): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM login_attempts
         WHERE username = ?
           AND success = 0
           AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$username]);

    return (int)$stmt->fetchColumn() >= 5;
}

function auto_copyright($year = 'auto'){
    if(INTVAL($year) == 'auto')
    {
        $year = DATE('Y'); 
    } 
    if(INTVAL($year) == DATE('Y'))
    { 
        ECHO INTVAL($year); 
    } 
    if(INTVAL($year) < DATE('Y'))
    { 
        ECHO INTVAL($year) . ' - ' . DATE('Y'); 
    } 
    if(INTVAL($year) > DATE('Y'))
    { 
        ECHO DATE('Y'); 
    } 
} 
