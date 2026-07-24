<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$workOrder = $id ? find_work_order($id) : null;

if ($workOrder === null) {
    http_response_code(404);
    exit('Work order not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Select a valid attachment.';
    } else {
        $file = $_FILES['attachment'];

        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'Attachment must be 10 MB or smaller.';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (!isset($allowed[$mime])) {
            $errors[] = 'Only JPG, PNG, WEBP, and PDF files are allowed.';
        }

        if (!$errors) {
            ensure_maintenance_upload_directory();

            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            $destination = maintenance_upload_directory() . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Unable to save attachment.';
            } else {
                $user = current_user();

                db()->prepare(
                    'INSERT INTO work_order_attachments
                     (work_order_id, filename, original_name, mime_type, uploaded_by)
                     VALUES (?, ?, ?, ?, ?)'
                )->execute([
                    $id,
                    $filename,
                    basename((string)$file['name']),
                    $mime,
                    $user['id'] ?? null,
                ]);

                audit_log('work_order_attachment_uploaded', null, (string)$workOrder['work_order_number']);
                flash('success', 'Attachment uploaded.');
                redirect('/maintenance/work_order_view.php?id=' . $id);
            }
        }
    }
}

$pageTitle = 'Upload Attachment';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Upload Work Order Attachment</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert danger"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label>Attachment</label>
            <input type="file" name="attachment"
                   accept="image/jpeg,image/png,image/webp,application/pdf"
                   capture="environment"
                   required>
        </div>

        <div class="actions">
            <button class="btn">Upload</button>
            <a class="btn secondary" href="<?= BASE_URL ?>/maintenance/work_order_view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
