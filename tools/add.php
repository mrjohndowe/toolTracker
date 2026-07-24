<?php
require_once '../includes/functions.php';
require_login();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $sql="INSERT INTO tools(internal_id,barcode,serial_number,name,manufacturer,model,status) VALUES(?,?,?,?,?,?,?)";
 db()->prepare($sql)->execute([
 $_POST['internal_id'],$_POST['barcode'],$_POST['serial_number'],
 $_POST['name'],$_POST['manufacturer'],$_POST['model'],$_POST['status']
 ]);
 flash('success','Tool added.');
 redirect('/tools/index.php');
}
require '../includes/header.php'; ?>
<h1>Add Tool</h1>
<form method="post">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Internal ID</label><input name="internal_id" required>
<label>Barcode</label><input name="barcode" required>
<label>Serial</label><input name="serial_number">
<label>Name</label><input name="name" required>
<label>Manufacturer</label><input name="manufacturer">
<label>Model</label><input name="model">
<label>Status</label>
<select name="status">
<option>Available</option><option>Inspection</option><option>Repair</option><option>Retired</option>
</select><br><br>
<button class="btn">Save Tool</button>
</form>
<?php require '../includes/footer.php'; ?>