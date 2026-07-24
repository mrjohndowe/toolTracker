<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (current_user() !== null) {
    redirect('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash('danger', 'Username and password are required.');
    } elseif (login_rate_limited($username)) {
        flash('danger', 'Too many failed attempts. Try again in 15 minutes.');
    } else {
        $stmt = db()->prepare(
            'SELECT
                u.id,
                u.username,
                u.password_hash,
                u.first_name,
                u.last_name,
                u.active,
                r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $valid = is_array($user)
            && (int)$user['active'] === 1
            && password_verify($password, (string)$user['password_hash']);

        $attempt = db()->prepare(
            'INSERT INTO login_attempts (username, success, ip_address)
             VALUES (?, ?, ?)'
        );
        $attempt->execute([
            $username,
            $valid ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        if ($valid) {
            session_regenerate_id(true);

            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            $_SESSION['last_activity'] = time();

            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')
                ->execute([(int)$user['id']]);

            audit_log('login', (int)$user['id']);
            redirect('/dashboard.php');
        }

        flash('danger', 'Invalid username or password.');
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="login-wrap">
    <div class="card">
        <h1>Sign In</h1>
        <p class="muted">Tool room staff login</p>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>

            <button class="btn" type="submit">Login</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
