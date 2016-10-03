<?php
include 'config.php';
//sleep(9);
$mysql = db();
verify($mysql);
$src = $_POST['src'];
$uuid = $_POST['uuid'];


$stmt = $mysql->prepare("DELETE FROM `photo` WHERE photo_filename = ? and photo_object_id = ? ");

if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('ss',$src, $uuid);
$result = $stmt->execute();

if($result){
	ok();
}else{
	err('mysql', $mysql->error); 
}

$stmt->close();
$mysql->close();

?>