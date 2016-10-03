<?php
include 'config.php';
//sleep(9);


$mysql = db();
verify($mysql);
$mysql->set_charset('utf8mb4');
//$content = htmlentities($_POST['content'], ENT_HTML5);
$content = $_POST['content'];
$plane_id = $_POST['plane_id'];
$comment_id = @$_POST['comment_id'];
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
*/
$detail =  array('plane'=>1, 'mmls'=>0, 'center'=>0, 'news'=>0);
if($comment_id == '' || isset($comment_id) == false){
	$comment_id = NULL;
}

//var_dump($comment_id);

$stmt = $mysql->prepare("INSERT INTO `comment`(content, sid, plane_id, comment_id) VALUES(?, ?, ?, ?)");

if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('ssss',$content, $sid, $plane_id, $comment_id);
$result = $stmt->execute();



if($result){
	//ok();
	ignore();
}else{
	//err('mysql', $mysql->error); 
	err('Error', "This post has been deleted", true); 
}


//insert notice
notice($mysql, $plane_id, $mysql->insert_id, 1);


/*select details for push notification*/

//whos plane? get token
$stmt = $mysql->prepare("SELECT s.sid, d.iostoken, d.androidtoken FROM `student` as s, `device` as d, `plane` as p WHERE p.sid = s.sid and p.sid = d.sid and p.id = ?;");
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('s', $plane_id);
$stmt->bind_result($id, $itoken, $atoken);
$stmt->execute();
/*
$stmt->fetch();
if($itoken != ''){
	$platform = 'iOS';
	$token = array($itoken);
}else{
	$platform = 'Android';
	$token = array($atoken);
}
*/
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

//from who? get name
$stmt = $mysql->prepare("SELECT name from student where student.sid = ?;");
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('s', $sid);
$stmt->bind_result($name);
$stmt->execute();
$stmt->fetch();
$stmt->close();

if($sid != $id){
	if(!empty($atokenArr)){
		// echo "ANDROID";
		// print_r($atokenArr);
		push($name." comments: ", $content, 1, $atokenArr, 'Android', $detail);
	}

	if(!empty($itokenArr)){
		// echo "IOS";
		//print_r($itokenArr);
		push($name." comments: ", $content, 1, $itokenArr, 'iOS', $detail);
	}

}

//push notification for replie  
if($comment_id != NULL){

	//whos comment? get token
	$stmt = $mysql->prepare("SELECT s.sid, d.iostoken, d.androidtoken FROM `student` as s, `device` as d, `comment` as c WHERE c.sid = s.sid and c.sid = d.sid and c.id = ?;");
	if ($stmt === false) {
	  err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('s', $comment_id);
	$stmt->bind_result($cid, $itoken, $atoken);
	$stmt->execute();
	/*
	$stmt->fetch();
	if($itoken != ''){
		$platform = 'iOS';
		$token = array($itoken);
	}else{
		$platform = 'Android';
		$token = array($atoken);
	}
	*/

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

	if($id != $cid){ //if plane's owner and this comment's owner are differnt people. (To avoid push twice when repliying plane's owner )

		if(!empty($atokenArr)){
			// echo "ANDROID";
			// print_r($atokenArr);
			push($name." replies: ", $content, 1, $atokenArr, 'Android', $detail);
		}

		if(!empty($itokenArr)){
			// echo "IOS";
			//print_r($itokenArr);
			push($name." replies: ", $content, 1, $itokenArr, 'iOS', $detail);
		}
	}
}

$mysql->close();

?>