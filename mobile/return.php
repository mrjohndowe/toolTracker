<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_login();

$pageTitle = 'Mobile Return';
require __DIR__ . '/../includes/header.php';
?>
<div class="actions" style="justify-content:space-between">
    <h1>Mobile Return</h1>
    <a class="btn secondary" href="<?= BASE_URL ?>/mobile/index.php">Back</a>
</div>

<div class="card">
    <?php require __DIR__ . '/_scanner.php'; ?>
    <div class="form-group"><label>Tool Barcode, Internal ID, or Serial</label><input id="returnCode" autocomplete="off"></div>
    <button class="btn" id="processReturn">Start Check-In Inspection</button>
</div>
<script>
async function startReturn(value){const response=await fetch('<?= BASE_URL ?>/mobile/api_return.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-Token':'<?= e(csrf_token()) ?>'},body:JSON.stringify({value})});const data=await response.json();if(!response.ok||!data.success)throw new Error(data.message||'Return failed.');window.location.href=data.inspection_url;}
document.getElementById('startScanner').addEventListener('click',()=>startScanner(decodedText=>{document.getElementById('returnCode').value=decodedText;startReturn(decodedText).catch(e=>alert(e.message));}));
document.getElementById('processReturn').addEventListener('click',()=>startReturn(document.getElementById('returnCode').value.trim()).catch(e=>alert(e.message)));
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
