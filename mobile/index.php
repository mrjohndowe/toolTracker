<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$pageTitle = 'Mobile Tools';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Mobile Tools</h1>
    <button id="installApp" class="btn secondary" hidden>Install App</button>
</div>

<div class="grid">
    <div class="card">
        <h2>Mobile Checkout</h2>
        <p>Scan an employee badge and multiple tool barcodes using the device camera.</p>
        <a class="btn" href="<?= BASE_URL ?>/mobile/checkout.php">Open Checkout</a>
    </div>

    <div class="card">
        <h2>Mobile Return</h2>
        <p>Scan returned tools and route them to available, inspection, or repair.</p>
        <a class="btn" href="<?= BASE_URL ?>/mobile/return.php">Open Return</a>
    </div>

    <div class="card">
        <h2>Quick Lookup</h2>
        <p>Scan a tool barcode, internal ID, serial number, or employee badge.</p>
        <a class="btn" href="<?= BASE_URL ?>/mobile/lookup.php">Open Lookup</a>
    </div>

    <div class="card">
        <h2>QR Labels</h2>
        <p>Generate QR labels for tools and employee badges.</p>
        <a class="btn" href="<?= BASE_URL ?>/mobile/qr_labels.php">Generate Labels</a>
    </div>
</div>

<script>
let deferredPrompt;
const installButton = document.getElementById('installApp');

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    deferredPrompt = event;
    installButton.hidden = false;
});

installButton.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    installButton.hidden = true;
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
