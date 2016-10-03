<?php
include 'config.php';
//sleep(9);
$mysql = db();
verify($mysql);
$iosToken = @$_POST['iosToken'];
$androidToken = @$_POST['androidToken'];

if(empty($iosToken)){
	$sql = "DELETE FROM `device` WHERE sid = ? and androidtoken = ? ";
	$token = $androidToken;
}else if(empty($androidToken)){
	$sql = "DELETE FROM `device` WHERE sid = ? and iostoken = ? ";
	$token = $iosToken;
}

$stmt = $mysql->prepare($sql);

if ($stmt === false) {
	err('mysql', $mysql->error, true); 
}
$stmt->bind_param('ss',$sid, $token);
$result = $stmt->execute();

if($result){
	ok();
}else{
	err('mysql', $mysql->error); 
}

$stmt->close();
$mysql->close();

?>