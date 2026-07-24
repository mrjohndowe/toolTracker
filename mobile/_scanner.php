<div class="scanner-panel">
    <div id="reader" style="width:100%;max-width:540px"></div>
    <div class="actions" style="margin-top:12px">
        <button type="button" class="btn secondary" id="startScanner">Start Camera</button>
        <button type="button" class="btn secondary" id="stopScanner">Stop Camera</button>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/vendor/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;
let scannerRunning = false;

async function startScanner(onScan) {
    if (scannerRunning) return;

    html5QrCode = new Html5Qrcode('reader');

    try {
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 260, height: 180 },
                aspectRatio: 1.5
            },
            decodedText => {
                if (typeof onScan === 'function') {
                    onScan(decodedText);
                }
            },
            () => {}
        );

        scannerRunning = true;
    } catch (error) {
        alert('Unable to start camera: ' + error);
    }
}

async function stopScanner() {
    if (!html5QrCode || !scannerRunning) return;

    try {
        await html5QrCode.stop();
        await html5QrCode.clear();
    } finally {
        scannerRunning = false;
        html5QrCode = null;
    }
}

document.getElementById('stopScanner').addEventListener('click', stopScanner);
</script>
