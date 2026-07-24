<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$type = (string)($_GET['type'] ?? 'tools');

if ($type === 'employees') {
    $rows = db()->query(
        'SELECT id, badge_code AS code,
                CONCAT(first_name, " ", last_name) AS label,
                employee_number AS secondary
         FROM employees
         WHERE active = 1
         ORDER BY last_name, first_name'
    )->fetchAll();
} else {
    $type = 'tools';
    $rows = db()->query(
        'SELECT id, barcode AS code, name AS label, internal_id AS secondary
         FROM tools
         WHERE active = 1
         ORDER BY name'
    )->fetchAll();
}

$pageTitle = 'QR Labels';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>QR Labels</h1>
    <button class="btn" onclick="window.print()">Print</button>
</div>

<div class="card no-print">
    <a class="btn secondary" href="?type=tools">Tool Labels</a>
    <a class="btn secondary" href="?type=employees">Employee Badges</a>
</div>

<div class="label-grid">
    <?php foreach ($rows as $row): ?>
        <div class="qr-label">
            <div class="qr-code" data-value="<?= e((string)$row['code']) ?>"></div>
            <strong><?= e((string)$row['label']) ?></strong>
            <span><?= e((string)$row['secondary']) ?></span>
            <small><?= e((string)$row['code']) ?></small>
        </div>
    <?php endforeach; ?>
</div>

<script src="<?= BASE_URL ?>/assets/vendor/qrcode.min.js"></script>
<script>
document.querySelectorAll('.qr-code').forEach(element => {
    new QRCode(element, {
        text: element.dataset.value,
        width: 110,
        height: 110,
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>

<style>
.label-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
    gap:14px;
}
.qr-label{
    border:1px solid #222;
    padding:12px;
    text-align:center;
    break-inside:avoid;
}
.qr-label strong,.qr-label span,.qr-label small{
    display:block;
    margin-top:5px;
}
.qr-code img,.qr-code canvas{
    margin:auto;
}
@media print{
    .no-print,nav,header,footer,.actions button{display:none!important}
    .label-grid{grid-template-columns:repeat(3,1fr)}
    .qr-label{page-break-inside:avoid}
}
</style>

<?php require __DIR__ . '/../includes/footer.php'; ?>
