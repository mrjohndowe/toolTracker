<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

function inspection_url(string $path = ''): string {
    $base = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}


function inspection_template_for(string $type): ?array {
    $stmt=db()->prepare('SELECT * FROM inspection_templates WHERE active=1 AND inspection_type IN (?,"Both") ORDER BY inspection_type=? DESC,id LIMIT 1');
    $stmt->execute([$type,$type]); $r=$stmt->fetch(); return is_array($r)?$r:null;
}
function inspection_questions(int $templateId): array {
    $s=db()->prepare('SELECT * FROM inspection_questions WHERE template_id=? AND active=1 ORDER BY sort_order,id');
    $s->execute([$templateId]); return $s->fetchAll();
}
function inspection_create_queue(string $type,array $items,string $returnUrl): string {
    $key=bin2hex(random_bytes(16));
    $_SESSION['inspection_queues'][$key]=['type'=>$type,'items'=>array_values($items),'index'=>0,'return_url'=>$returnUrl,'created_at'=>time()];
    return inspection_url('/inspections/queue.php?queue=' . rawurlencode($key));
}
function inspection_answer_value(array $q,$value): array {
    $text=$bool=$num=null;
    if ($q['question_type']==='YesNo') $bool=((string)$value==='1'?1:0);
    elseif ($q['question_type']==='Number') $num=($value===''?null:(float)$value);
    else $text=trim((string)$value);
    return [$text,$bool,$num];
}
function save_inspection(array $item,array $answers,?int $userId,?string $notes): int {
    $type=(string)$item['type']; $template=inspection_template_for($type);
    if(!$template) throw new RuntimeException('No active inspection template is configured.');
    $questions=inspection_questions((int)$template['id']); $map=[];
    foreach($questions as $q){$map[(int)$q['id']]=$q; $v=$answers[(int)$q['id']]??null; if((int)$q['required']===1 && ($v===null||$v==='')) throw new RuntimeException('Please answer: '.$q['question_text']);}
    $overall=null;$contents=null;$working=null;
    foreach($questions as $q){$v=$answers[(int)$q['id']]??null;$qt=strtolower((string)$q['question_text']);
      if($q['question_type']==='Condition'&&$v!=='')$overall=(string)$v;
      if(str_contains($qt,'all listed contents'))$contents=($v===null||$v==='')?null:((string)$v==='1'?1:0);
      if(str_contains($qt,'power on and operate'))$working=($v===null||$v==='')?null:((string)$v==='1'?1:0);
    }
    $pdo=db();
    $pdo->prepare('INSERT INTO inspection_sessions (inspection_type,transaction_id,checkout_item_id,tool_id,employee_id,template_id,completed_by,overall_condition,contents_complete,working_condition,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$type,$item['transaction_id']??null,$item['checkout_item_id']??null,$item['tool_id'],$item['employee_id']??null,$template['id'],$userId,$overall,$contents,$working,$notes]);
    $sid=(int)$pdo->lastInsertId();
    $ins=$pdo->prepare('INSERT INTO inspection_responses (inspection_session_id,question_id,answer_text,answer_boolean,answer_number) VALUES (?,?,?,?,?)');
    foreach($answers as $qid=>$v){$qid=(int)$qid;if(!isset($map[$qid]))continue;[$t,$b,$n]=inspection_answer_value($map[$qid],$v);$ins->execute([$sid,$qid,$t,$b,$n]);}
    if($type==='Checkin') finalize_checkin_from_inspection($item,$overall,$contents,$working,$notes,$userId);
    return $sid;
}
function finalize_checkin_from_inspection(array $item,?string $condition,?int $contents,?int $working,?string $notes,?int $userId): void {
    $pdo=db();
    $s=$pdo->prepare('SELECT ci.*,t.status AS tool_status,t.tool_condition,t.name,t.barcode FROM checkout_items ci INNER JOIN tools t ON t.id=ci.tool_id WHERE ci.id=? AND ci.return_status="Pending" FOR UPDATE');
    $s->execute([(int)$item['checkout_item_id']]); $row=$s->fetch(); if(!is_array($row)) throw new RuntimeException('This item was already returned.');
    $condition=$condition?:'Good';
    if($working===0||$condition==='Not Working'){$returnStatus='Repair';$toolStatus='Repair';}
    elseif($contents===0||$condition==='Poor'){$returnStatus='Inspection';$toolStatus='Inspection';}
    else {$returnStatus='Returned';$toolStatus='Available';}
    $pdo->prepare('UPDATE checkout_items SET returned_at=NOW(),return_condition=?,return_status=?,inspection_notes=?,returned_by=? WHERE id=?')->execute([$condition,$returnStatus,$notes,$userId,$row['id']]);
    $pdo->prepare('UPDATE tools SET status=?,tool_condition=? WHERE id=?')->execute([$toolStatus,$condition,$row['tool_id']]);
    $pdo->prepare('INSERT INTO tool_status_history (tool_id,old_status,new_status,old_condition,new_condition,notes,changed_by) VALUES (?,?,?,?,?,?,?)')->execute([$row['tool_id'],$row['tool_status'],$toolStatus,$row['tool_condition'],$condition,$notes?:'Returned after required inspection',$userId]);
    if(function_exists('record_scan')) record_scan('Tool Return',(string)$row['barcode'],true,'Return inspection completed',(int)($item['employee_id']??0),(int)$row['tool_id'],(int)$row['transaction_id']);
    if(function_exists('update_transaction_status')) update_transaction_status((int)$row['transaction_id']);
}
function inspection_render_field(array $q): string {
    $id=(int)$q['id'];$name='answers['.$id.']';$req=(int)$q['required']===1?' required':'';$type=$q['question_type'];
    if($type==='YesNo')return '<select name="'.e($name).'"'.$req.'><option value="">Select</option><option value="1">Yes</option><option value="0">No</option></select>';
    if($type==='Textarea')return '<textarea name="'.e($name).'" rows="3"'.$req.'></textarea>';
    if($type==='Number')return '<input type="number" step="0.01" name="'.e($name).'"'.$req.'>';
    if($type==='Select'||$type==='Condition'){$o=json_decode((string)($q['options_json']??'[]'),true)?:[];$h='<select name="'.e($name).'"'.$req.'><option value="">Select</option>';foreach($o as $v)$h.='<option value="'.e((string)$v).'">'.e((string)$v).'</option>';return $h.'</select>';}
    return '<input name="'.e($name).'"'.$req.'>';
}
