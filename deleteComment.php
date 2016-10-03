<?php
include 'config.php';
//sleep(9);
$mysql = db();
verify($mysql);
$cid = $_POST['cid'];


$stmt = $mysql->prepare("DELETE FROM `comment` WHERE id = ?;");

if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}

$stmt->bind_param('i',$cid);
$result = $stmt->execute();

if($result){
	ok();
}else{
	err('mysql', $mysql->error); 
}

$stmt->close();
$mysql->close();

?>