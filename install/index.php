<?php
declare(strict_types=1);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string)($_POST['host'] ?? '127.0.0.1'));
    $database = trim((string)($_POST['database'] ?? 'tooltrack'));
    $dbUsername = trim((string)($_POST['db_username'] ?? 'root'));
    $dbPassword = (string)($_POST['db_password'] ?? '');
    $adminUsername = trim((string)($_POST['admin_username'] ?? 'admin'));
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $firstName = trim((string)($_POST['first_name'] ?? 'System'));
    $lastName = trim((string)($_POST['last_name'] ?? 'Administrator'));

    if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
        $errors[] = 'Database name may contain only letters, numbers, and underscores.';
    }

    if (strlen($adminPassword) < 10) {
        $errors[] = 'Administrator password must be at least 10 characters.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO(
                "mysql:host={$host};charset=utf8mb4",
                $dbUsername,
                $dbPassword,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => true,
                ]
            );

            $sql = (string)file_get_contents(__DIR__ . '/database.sql');
            $sql = str_replace(
                'CREATE DATABASE IF NOT EXISTS tooltrack',
                "CREATE DATABASE IF NOT EXISTS `{$database}`",
                $sql
            );
            $sql = str_replace(
                'USE tooltrack;',
                "USE `{$database}`;",
                $sql
            );

            $pdo->exec($sql);
            $pdo->exec("USE `{$database}`");

            $stmt = $pdo->prepare(
                'INSERT INTO users
                 (username, password_hash, first_name, last_name, role_id, active)
                 VALUES (?, ?, ?, ?, 1, 1)
                 ON DUPLICATE KEY UPDATE
                    password_hash = VALUES(password_hash),
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    role_id = 1,
                    active = 1'
            );

            $stmt->execute([
                $adminUsername,
                password_hash($adminPassword, PASSWORD_DEFAULT),
                $firstName,
                $lastName,
            ]);

            $success = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ToolTrack Installer</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<main class="container">
    <div class="card">
        <h1>ToolTrack Pro Installer</h1>

        <?php if ($success): ?>
            <div class="alert success">
                Installation completed. Update config/database.php with the same database values,
                then delete or rename the install folder.
            </div>

            <a class="btn" href="../login.php">Go to Login</a>
        <?php else: ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>

            <form method="post">
                <div class="grid">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input name="host" value="127.0.0.1" required>
                    </div>

                    <div class="form-group">
                        <label>Database Name</label>
                        <input name="database" value="tooltrack" required>
                    </div>

                    <div class="form-group">
                        <label>Database Username</label>
                        <input name="db_username" value="root" required>
                    </div>

                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_password">
                    </div>

                    <div class="form-group">
                        <label>Admin Username</label>
                        <input name="admin_username" value="admin" required>
                    </div>

                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_password" minlength="10" required>
                    </div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input name="first_name" value="System" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input name="last_name" value="Administrator" required>
                    </div>
                </div>

                <button class="btn" type="submit">Install</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
