<?php
include 'config.php';

//sleep(9);
$mysql = db();

verify($mysql);
$name = @$_POST['name'];
$email = @$_POST['email'];
$birthday = @$_POST['birthday'];
$cover = @$_POST['cover'];
$gender = @$_POST['gender'];
$link = @$_POST['link'];
$fbid = $_POST['fbid'];
$iosToken = @$_POST['iosToken'];
$androidToken = @$_POST['androidToken'];
$platform = @$_POST['devicePlatform'];
$version = @$_POST['deviceVersion'];
$MMUbeeVersion = @$_POST['mmubeeVersion'];
$deviceUUID = @$_POST['deviceUUID'];
$deviceModel = @$_POST['deviceModel'];

if(empty($sid) || empty($fbid)){
	exit();
}


insertDevice($mysql, $iosToken, $androidToken, $platform, $version, $MMUbeeVersion, $deviceUUID, $deviceModel);

//update data
$stmt = $mysql->prepare('UPDATE student SET name = ?, email = ?, birthday = ?, cover = ?, gender = ?, link = ?, fbid = ? WHERE sid = ?');
if($stmt===false){ err('mysql', $mysql->error, true); }

$stmt->bind_param('ssssssss',$name, $email, $birthday, $cover, $gender, $link,$fbid, $sid);
$result = $stmt->execute();

if($result === false){
	err('mysql', $mysql->error, false, true);
}else{
	ok();
}

$stmt->close();
$mysql->close();


?>