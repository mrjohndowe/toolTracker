<?php
// Administrator page can be expanded to add, reorder, require, enable, or disable questions.
require_once __DIR__ . '/../inspections/_common.php';
require_role('Administrator');
$questions = inspection_questions((int)inspection_template_for('Checkout')['id']);
$pageTitle='Inspection Questions';require __DIR__.'/../includes/header.php';
?><h1>Inspection Questions</h1><div class="card"><table class="table"><thead><tr><th>Order</th><th>Question</th><th>Type</th><th>Required</th></tr></thead><tbody><?php foreach($questions as $q):?><tr><td><?= (int)$q['sort_order']?></td><td><?=e($q['question_text'])?></td><td><?=e($q['question_type'])?></td><td><?=(int)$q['required']===1?'Yes':'No'?></td></tr><?php endforeach;?></tbody></table></div><?php require __DIR__.'/../includes/footer.php';?>
