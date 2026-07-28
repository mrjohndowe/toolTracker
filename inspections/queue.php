<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_login();
$key=(string)($_GET['queue']??$_POST['queue']??'');
$q=$_SESSION['inspection_queues'][$key]??null;
if(!is_array($q)){flash('danger','Inspection queue expired or was not found.');redirect(inspection_url('/'));}
$index=(int)$q['index']; $item=$q['items'][$index]??null;
if(!is_array($item)){ $url=(string)$q['return_url']; unset($_SESSION['inspection_queues'][$key]); flash('success','All required inspections are complete.'); redirect($url); }
$template=inspection_template_for((string)$item['type']); if(!$template) exit('No inspection template configured.');
$questions=inspection_questions((int)$template['id']);
$s=db()->prepare('SELECT id,name,internal_id,tool_condition FROM tools WHERE id=?');$s->execute([(int)$item['tool_id']]);$tool=$s->fetch();if(!is_array($tool))exit('Tool not found.');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{ $pdo=db();$pdo->beginTransaction();$u=current_user();save_inspection($item,is_array($_POST['answers']??null)?$_POST['answers']:[],$u['id']??null,trim((string)($_POST['notes']??''))?:null);$pdo->commit();$_SESSION['inspection_queues'][$key]['index']=$index+1;redirect(inspection_url('/inspections/queue.php?queue=' . rawurlencode($key))); }
 catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('danger',$e->getMessage());}
}
$pageTitle=$item['type'].' Inspection'; require __DIR__.'/../includes/header.php';
?>
<h1><?=e((string)$item['type'])?> Inspection</h1>
<div class="card"><h2><?=e((string)$tool['name'])?></h2><p><strong>ID:</strong> <?=e((string)$tool['internal_id'])?> | <strong>Questionnaire:</strong> <?=($index+1)?> of <?=count($q['items'])?></p></div>
<div class="card"><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="queue" value="<?=e($key)?>">
<?php foreach($questions as $i=>$question): ?><div class="form-group"><label><?=($i+1)?>. <?=e((string)$question['question_text'])?><?=((int)$question['required']===1?' *':'')?></label><?=inspection_render_field($question)?></div><?php endforeach; ?>
<div class="form-group"><label>Additional notes</label><textarea name="notes" rows="4"></textarea></div><button class="btn">Save and Continue</button></form></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
