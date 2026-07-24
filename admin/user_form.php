<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_role('Administrator');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$editingUser = null;

if ($id !== null) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $editingUser = $stmt->fetch();

    if (!is_array($editingUser)) {
        http_response_code(404);
        exit('User not found.');
    }
}

$roles = db()->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $postedId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
    $username = trim((string)($_POST['username'] ?? ''));
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
    $active = isset($_POST['active']) ? 1 : 0;
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $firstName === '' || $lastName === '' || !$roleId) {
        flash('danger', 'Complete all required fields.');
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Enter a valid email address.');
    } else {
        try {
            if ($postedId !== null) {
                if ($password !== '') {
                    if (strlen($password) < 10) {
                        throw new RuntimeException('Password must be at least 10 characters.');
                    }

                    $stmt = db()->prepare(
                        'UPDATE users
                         SET username = ?, first_name = ?, last_name = ?, email = ?,
                             role_id = ?, active = ?, password_hash = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $username,
                        $firstName,
                        $lastName,
                        $email !== '' ? $email : null,
                        $roleId,
                        $active,
                        password_hash($password, PASSWORD_DEFAULT),
                        $postedId,
                    ]);
                } else {
                    $stmt = db()->prepare(
                        'UPDATE users
                         SET username = ?, first_name = ?, last_name = ?, email = ?,
                             role_id = ?, active = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $username,
                        $firstName,
                        $lastName,
                        $email !== '' ? $email : null,
                        $roleId,
                        $active,
                        $postedId,
                    ]);
                }

                audit_log('user_updated', $postedId, $username);
                flash('success', 'User updated.');
            } else {
                if (strlen($password) < 10) {
                    throw new RuntimeException('Password must be at least 10 characters.');
                }

                $stmt = db()->prepare(
                    'INSERT INTO users
                     (username, password_hash, first_name, last_name, email, role_id, active)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $firstName,
                    $lastName,
                    $email !== '' ? $email : null,
                    $roleId,
                    $active,
                ]);

                audit_log('user_created', (int)db()->lastInsertId(), $username);
                flash('success', 'User created.');
            }

            redirect('/admin/users.php');
        } catch (PDOException $e) {
            flash('danger', 'Username or email may already be in use.');
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
        }
    }
}

$pageTitle = $editingUser ? 'Edit User' : 'Add User';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1><?= e($pageTitle) ?></h1>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)($editingUser['id'] ?? 0) ?>">

        <div class="grid">
            <div class="form-group">
                <label>First Name *</label>
                <input name="first_name" value="<?= e((string)($editingUser['first_name'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Last Name *</label>
                <input name="last_name" value="<?= e((string)($editingUser['last_name'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Username *</label>
                <input name="username" value="<?= e((string)($editingUser['username'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e((string)($editingUser['email'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Role *</label>
                <select name="role_id" required>
                    <option value="">Select role</option>

                    <?php foreach ($roles as $role): ?>
                        <option
                            value="<?= (int)$role['id'] ?>"
                            <?= (int)($editingUser['role_id'] ?? 0) === (int)$role['id'] ? 'selected' : '' ?>
                        >
                            <?= e((string)$role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>
                    Password
                    <?= $editingUser ? '(leave blank to keep current)' : '*' ?>
                </label>
                <input type="password" name="password" <?= $editingUser ? '' : 'required' ?>>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input
                    style="width:auto"
                    type="checkbox"
                    name="active"
                    <?= !isset($editingUser['active']) || (int)$editingUser['active'] === 1 ? 'checked' : '' ?>
                >
                Active
            </label>
        </div>

        <div class="actions">
            <button class="btn" type="submit">Save User</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/admin/users.php">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
