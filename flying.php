<?php
include 'config.php';
$mysql = db();
verify($mysql);
//$content = htmlentities($_POST['content'], ENT_HTML5);
$content = $_POST['content'];
$from = $_POST['from'];
$to = $_POST['to'];
$ip = get_client_ip();
$uuid = $_POST['uuid'];


$stmt = $mysql->prepare("INSERT INTO plane(`content`, `sid`, `to`, `ip`, `uuid`) VALUES(?,?,?,?,?)");

if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('sssss',$content, $from, $to, $ip, $uuid);
$result = $stmt->execute();
$stmt->close();

if($result){
	//ok();
	ignore();
}else{
	err('mysql', $mysql->error, true); 
}

//ignore();

if($to != 'CYBERJAYA' && $to != 'MELAKA'){
	//get all tokens by subject code
	$stmt = $mysql->prepare("SELECT d.iostoken, d.androidtoken from device as d, subject as s where s.sid = d.sid and s.code = ? GROUP by d.sid;");
	if ($stmt === false) {
	  err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('s', $to);
	$stmt->bind_result($itoken, $atoken);
	$stmt->execute();
	$itokenArr = array();
	$atokenArr = array();

	while ($stmt->fetch()) {
		if($itoken != ''){
			$itokenArr[] = $itoken;
		}else{
			$atokenArr[] = $atoken;
		}
	}

	$stmt->close();

	//from who? get name and token
	$stmt = $mysql->prepare("SELECT name from student where student.sid = ?;");
	if ($stmt === false) {
	  err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('s', $from);
	$stmt->bind_result($name);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();
	$detail = array('plane'=>1, 'mmls'=>0, 'center'=>0, 'news'=>0);
	if(!empty($itokenArr)){
		push("{$to}($name): ", $content, 1, $itokenArr, 'iOS', $detail);
	}

	if(!empty($atokenArr)){
		push("{$to}($name): ", $content, 1, $atokenArr, 'Android', $detail);
	}


}

$mysql->close();

?>