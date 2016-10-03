<?php
include 'config.php';
$mysql = db();
verify($mysql);

$iNotice = getNotice($mysql);
$ids = '';
foreach ($iNotice as $key => $value) {
	$ids.= 'OR plane_id = '.$value['nid'].' ';
}

$stmt = $mysql->prepare("UPDATE `notice` set `read` = 1 WHERE 1=1 {$ids};");
if($stmt){
	ok();
}else{
	err('mysql', $mysql->error);
}

$stmt->execute();
$stmt->close();
$mysql->close();

 
?>