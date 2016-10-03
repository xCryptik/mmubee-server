<?php
include 'config.php';
//sleep(9);
$mysql = db();
verify($mysql);
$plane_id = $_POST['plane_id'];

/*
//check if this plane not exists
$stmt = $mysql->prepare("SELECT EXISTS(SELECT * FROM plane WHERE id = ?);");
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('s', $plane_id);
$stmt->bind_result($exists);
$stmt->execute();
$stmt->fetch();
$stmt->close();
if($exists == 0){
	err('Error', "This post has been deleted", true); 
}


//check if already liked this plane
$stmt = $mysql->prepare("SELECT EXISTS(SELECT * FROM `like` WHERE sid = ? and plane_id = ?);");
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('ss', $sid, $plane_id);
$stmt->bind_result($exists);
$stmt->execute();
$stmt->fetch();
$stmt->close();
if($exists == 1){
	err('Error', "you already liked this plane.", true); 
}
*/

//insert like
$stmt = $mysql->prepare("INSERT INTO `like`(sid, plane_id) VALUES(?,?)");
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('ss',$sid, $plane_id);
$result = $stmt->execute();
$stmt->close();
if($result){
	ok();
}else{
	err('Error', "you already liked this plane or This post has been deleted.", true); 
}


notice($mysql, $plane_id, $mysql->insert_id, 2);

$mysql->close();

?>