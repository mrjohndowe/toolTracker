<?php
require_once '../includes/functions.php';
require_login();
$tools=db()->query("SELECT * FROM tools ORDER BY name")->fetchAll();
require '../includes/header.php'; ?>
<h1>Tools</h1>
<a class="btn" href="add.php">Add Tool</a>
<table class="table">
<tr><th>ID</th><th>Name</th><th>Serial</th><th>Status</th><th></th></tr>
<?php foreach($tools as $t): ?>
<tr>
<td><?=e($t['internal_id'])?></td>
<td><?=e($t['name'])?></td>
<td><?=e($t['serial_number'])?></td>
<td><?=e($t['status'])?></td>
<td><a class="btn secondary" href="edit.php?id=<?=(int)$t['id']?>">Edit</a></td>
</tr>
<?php endforeach;?>
</table>
<?php require '../includes/footer.php'; ?>