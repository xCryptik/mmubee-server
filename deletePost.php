<?php
include 'config.php';
//sleep(9);
$mysql = db();
verify($mysql);
$id = $_POST['id'];


$stmt = $mysql->prepare("DELETE FROM `plane` WHERE id = ? and sid = ? ");

if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('ss',$id, $sid);
$result = $stmt->execute();

if($result){
	ok();
}else{
	err('mysql', $mysql->error); 
}

$stmt->close();
$mysql->close();

?>