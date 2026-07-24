<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || find_employee($id) === null) {
    http_response_code(404);
    exit('Employee not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Select a valid image.';
    } else {
        $file = $_FILES['photo'];

        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be 5 MB or smaller.';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
        }

        if (!$errors) {
            ensure_employee_upload_directory();

            $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
            $destination = employee_upload_directory() . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Unable to save the image.';
            } else {
                $user = current_user();

                if (isset($_POST['is_primary'])) {
                    db()->prepare(
                        'UPDATE employee_photos SET is_primary = 0 WHERE employee_id = ?'
                    )->execute([$id]);
                }

                db()->prepare(
                    'INSERT INTO employee_photos
                     (employee_id, filename, original_name, is_primary, uploaded_by)
                     VALUES (?, ?, ?, ?, ?)'
                )->execute([
                    $id,
                    $filename,
                    basename((string)$file['name']),
                    isset($_POST['is_primary']) ? 1 : 0,
                    $user['id'] ?? null,
                ]);

                record_employee_history($id, 'photo_uploaded', 'Employee photo uploaded');
                audit_log('employee_photo_uploaded', null, 'Employee ID ' . $id);

                flash('success', 'Photo uploaded.');
                redirect('/employees/view.php?id=' . $id);
            }
        }
    }
}

$pageTitle = 'Upload Employee Photo';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Upload Employee Photo</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label>Photo</label>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="user" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_primary" style="width:auto">
                Set as primary photo
            </label>
        </div>

        <div class="actions">
            <button class="btn">Upload</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/employees/view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
