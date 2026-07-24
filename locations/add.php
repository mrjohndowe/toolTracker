<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string)($_POST['name'] ?? ''));
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    $address1 = trim((string)($_POST['address_line1'] ?? ''));
    $address2 = trim((string)($_POST['address_line2'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $state = trim((string)($_POST['state'] ?? ''));
    $postalCode = trim((string)($_POST['postal_code'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));

    if ($name === '' || $code === '') {
        flash('danger', 'Location name and code are required.');
    } else {
        try {
            db()->prepare(
                'INSERT INTO locations
                 (name, code, address_line1, address_line2, city, state,
                  postal_code, phone, email)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $name,
                $code,
                $address1 !== '' ? $address1 : null,
                $address2 !== '' ? $address2 : null,
                $city !== '' ? $city : null,
                $state !== '' ? $state : null,
                $postalCode !== '' ? $postalCode : null,
                $phone !== '' ? $phone : null,
                $email !== '' ? $email : null,
            ]);

            flash('success', 'Location created.');
            redirect('/locations/index.php');
        } catch (Throwable $e) {
            flash('danger', 'Unable to create location: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'New Location';
require __DIR__ . '/../includes/header.php';
?>
<h1>New Location</h1>

<div class="card">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="grid">
            <div class="form-group">
                <label>Name</label>
                <input name="name" required>
            </div>
            <div class="form-group">
                <label>Code</label>
                <input name="code" maxlength="30" required>
            </div>
        </div>

        <div class="form-group">
            <label>Address Line 1</label>
            <input name="address_line1">
        </div>

        <div class="form-group">
            <label>Address Line 2</label>
            <input name="address_line2">
        </div>

        <div class="grid">
            <div class="form-group">
                <label>City</label>
                <input name="city">
            </div>
            <div class="form-group">
                <label>State</label>
                <input name="state">
            </div>
            <div class="form-group">
                <label>Postal Code</label>
                <input name="postal_code">
            </div>
        </div>

        <div class="grid">
            <div class="form-group">
                <label>Phone</label>
                <input name="phone">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
        </div>

        <button class="btn">Create Location</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
