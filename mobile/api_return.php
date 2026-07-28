<?php
declare(strict_types=1);
require_once __DIR__.'/_common.php';
require_once __DIR__.'/../inspections/_common.php';
require_login();
$csrf=$_SERVER['HTTP_X_CSRF_TOKEN']??'';if(!hash_equals(csrf_token(),$csrf))json_response(['success'=>false,'message'=>'Invalid CSRF token.'],403);
$input=json_decode((string)file_get_contents('php://input'),true);$value=trim((string)($input['value']??''));if($value==='')json_response(['success'=>false,'message'=>'Scan a tool.'],422);
$s=db()->prepare('SELECT ci.id AS checkout_item_id,ci.transaction_id,ci.tool_id,ct.employee_id,t.name FROM checkout_items ci INNER JOIN checkout_transactions ct ON ct.id=ci.transaction_id INNER JOIN tools t ON t.id=ci.tool_id WHERE ci.return_status="Pending" AND (t.barcode=? OR t.internal_id=? OR t.serial_number=?) ORDER BY ci.id DESC LIMIT 1');$s->execute([$value,$value,$value]);$item=$s->fetch();if(!is_array($item))json_response(['success'=>false,'message'=>'No open checkout was found for that tool.'],404);
$url=inspection_create_queue('Checkin',[['type'=>'Checkin','tool_id'=>(int)$item['tool_id'],'transaction_id'=>(int)$item['transaction_id'],'checkout_item_id'=>(int)$item['checkout_item_id'],'employee_id'=>(int)$item['employee_id']]],inspection_url('/mobile/return.php'));
json_response(['success'=>true,'tool_name'=>$item['name'],'inspection_url'=>$url]);
