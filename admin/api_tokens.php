<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('Administrator');

$availableScopes = [
    'tools:read',
    'employees:read',
    'checkout:read',
    'checkout:write',
    'maintenance:read',
    'maintenance:write',
];

$newToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        $scopes = $_POST['scopes'] ?? [];
        $expiresAt = trim((string)($_POST['expires_at'] ?? ''));

        if ($name === '') {
            flash('danger', 'Token name is required.');
            redirect('/admin/api_tokens.php');
        }

        if (!is_array($scopes)) {
            $scopes = [];
        }

        $scopes = array_values(array_intersect($availableScopes, $scopes));

        $plainToken = 'tt_' . bin2hex(random_bytes(32));
        $prefix = substr($plainToken, 0, 12);
        $hash = hash('sha256', $plainToken);
        $user = current_user();

        db()->prepare(
            'INSERT INTO api_tokens
             (user_id, name, token_prefix, token_hash, scopes, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $user['id'] ?? null,
            $name,
            $prefix,
            $hash,
            json_encode($scopes),
            $expiresAt !== '' ? $expiresAt : null,
        ]);

        $_SESSION['new_api_token'] = $plainToken;
        flash('success', 'API token created. Copy it now; it will not be shown again.');
        redirect('/admin/api_tokens.php');
    }

    if ($action === 'revoke') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            db()->prepare(
                'UPDATE api_tokens
                 SET active = 0, revoked_at = NOW()
                 WHERE id = ?'
            )->execute([$id]);

            flash('success', 'API token revoked.');
        }

        redirect('/admin/api_tokens.php');
    }
}

if (isset($_SESSION['new_api_token'])) {
    $newToken = (string)$_SESSION['new_api_token'];
    unset($_SESSION['new_api_token']);
}

$tokens = db()->query(
    'SELECT at.*, u.username
     FROM api_tokens at
     LEFT JOIN users u ON u.id = at.user_id
     ORDER BY at.id DESC'
)->fetchAll();

$pageTitle = 'API Tokens';
require __DIR__ . '/../includes/header.php';
?>
<h1>API Tokens</h1>

<?php if ($newToken): ?>
    <div class="alert success">
        <strong>Copy this token now:</strong>
        <pre style="white-space:pre-wrap"><?= e($newToken) ?></pre>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Create Token</h2>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">

        <div class="grid">
            <div class="form-group">
                <label>Token Name</label>
                <input name="name" required>
            </div>

            <div class="form-group">
                <label>Expires At</label>
                <input type="datetime-local" name="expires_at">
            </div>
        </div>

        <div class="form-group">
            <label>Scopes</label>
            <?php foreach ($availableScopes as $scope): ?>
                <label style="display:block;margin:6px 0">
                    <input type="checkbox" name="scopes[]" value="<?= e($scope) ?>" style="width:auto">
                    <?= e($scope) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button class="btn">Create API Token</button>
    </form>
</div>

<div class="card">
    <h2>Existing Tokens</h2>

    <table class="table">
        <thead>
        <tr><th>Name</th><th>Prefix</th><th>Scopes</th><th>Last Used</th><th>Expires</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($tokens as $token): ?>
            <?php $scopes = json_decode((string)($token['scopes'] ?? '[]'), true) ?: []; ?>
            <tr>
                <td><?= e((string)$token['name']) ?></td>
                <td><code><?= e((string)$token['token_prefix']) ?>...</code></td>
                <td><?= e(implode(', ', $scopes)) ?></td>
                <td><?= e((string)($token['last_used_at'] ?? '')) ?></td>
                <td><?= e((string)($token['expires_at'] ?? 'Never')) ?></td>
                <td><?= (int)$token['active'] === 1 ? 'Active' : 'Revoked' ?></td>
                <td>
                    <?php if ((int)$token['active'] === 1): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="revoke">
                            <input type="hidden" name="id" value="<?= (int)$token['id'] ?>">
                            <button class="btn secondary">Revoke</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
