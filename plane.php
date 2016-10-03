<?php
include 'config.php';
//sleep(8);

$limit = 10; //item per page
$action = $_POST['action'];
$pid = (int)$_POST['pid'];
$page = (int)$_POST['page'];
$start = ($page * $limit) - $limit; // find out query stat point
$sidOf = @$_POST['sidOf'];

//$id = $_GET['id'];
# query for page navigation
/*
if( mysql_num_rows(mysql_query($sql)) > ($page * $limit) ){
  $next = ++$page;
}
*/

//sleep(9);
$mysql = db();
verify($mysql);

/*
//select all comment
if($stmt = $mysql->prepare("SELECT comment.id, student.sid, comment.plane_id, comment.content, comment.`date`, student.fbid,  student.name, student.cover FROM comment, student WHERE comment.sid = student.sid GROUP BY comment.id asc;")) {
  // s - string, b - blob, i - int, etc 
  //$stmt -> bind_param("ss", $user, $pass);

  $stmt -> bind_result($cid, $sid_, $reTo, $reContent, $reDate, $reFbid, $reName, $reCover);
  $stmt -> execute();
  $replies = array();
  while ($stmt->fetch()) {
  	$replies[$reTo][] = array('cid'=> $cid, 'sid'=>$sid_, 'content'=>$reContent, 'date'=>$reDate, 'fbid'=>$reFbid, 'name'=>$reName, 'cover'=>$reCover);
  }
  $stmt -> close();
}else{
  err('mysql', $mysql->error, true); 
}
*/

//select mmls list
/*
if($stmt = $mysql->prepare("SELECT json FROM mmls WHERE sid = ?;")) {
  $stmt -> bind_param("i", $sid);
  $stmt -> bind_result($json);
  $stmt -> execute();
  while ($stmt->fetch()) {
    $d =  json_decode($json, 1);
  }
  $stmt -> close();
}else{
  err('mysql', $mysql->error, true); 
}
*/
/*
foreach ($d['subject'] as $key => $value) {
  $subjects.= "OR p.to = '".$value['code']."' ";
}
*/

//be carefull here... if mmls not exist there will be error!
$subjects = "";

if($stmt = $mysql->prepare("SELECT code, campus FROM subject WHERE sid = ? and class != 'Favourite';")) {
  $stmt -> bind_param("s", $sid);
  $stmt -> bind_result($code, $branch);
  $stmt -> execute();
  while ($stmt->fetch()) {
    $subjects.= "OR p.to = '".$code."' ";
  }
  $stmt -> close();
}else{
  err('mysql', $mysql->error, true); 
}

if(empty($branch)){
  err('Error', "Couldn't detect your campus, please try to relogin or report this bug.", true); 
}
$branch = strtoupper($branch);

//echo $branch;

switch ($action) {
  case 'refresh':
    $sql = "SELECT 
      p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
       (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
       ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
       ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
    FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND p.sid = s.sid and p.id >= ? GROUP BY p.id desc";
    break;

  case 'infinite':
    $sql = "SELECT 
      p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
       (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
       ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
       ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
    FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND p.sid = s.sid and p.id < ? GROUP BY p.id desc LIMIT 0, ?";
    break;

  case 'one':
    $sql = "SELECT 
      p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
       (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
       ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
       ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
    FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND p.sid = s.sid and p.id = ? GROUP BY p.id desc";
    break;

  case 'of':
    if($pid == 0){//default sql
      $sql = "SELECT 
        p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
         (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
         ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
         ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
      FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND  p.sid = ? and p.sid = s.sid and p.id >= ? GROUP BY p.id desc LIMIT 0, ?";
    }else{
      $sql = "SELECT 
        p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
         (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
         ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
         ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
      FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND  p.sid = ? and p.sid = s.sid and p.id < ? GROUP BY p.id desc LIMIT 0, ?";
    }
  /*
    $sql = "
    SELECT 
      p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
       (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
       ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
       ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
    FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND p.sid = ? and p.sid = s.sid and p.id >= ? GROUP BY p.id desc LIMIT 0, 10;
    ";
    */
    break;

  default:
    $sql = "SELECT 
      p.id, p.content, p.date, p.sid, p.to, p.uuid, s.name, s.fbid, s.cover,
       (EXISTS (SELECT * FROM `like`  WHERE like.sid = ? and like.plane_id = p.id)) as `iLike`,
       ( SELECT GROUP_CONCAT(student.`name` separator', ') FROM `student`, `like` WHERE student.sid = like.sid and like.plane_id = p.id) as `likes`,
       ( SELECT GROUP_CONCAT(photo.`photo_hash`) FROM `photo` WHERE photo.photo_object_id = p.uuid) as `photos`
    FROM `plane`  AS p, `student` as s WHERE ( p.to = ? {$subjects}) AND p.sid = s.sid  GROUP BY p.id desc LIMIT 0, ?";
    break;
}
//echo $sql;
$stmt = $mysql->prepare($sql);
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}

$sqlComment = "SELECT c.id, c.comment_id, s.sid, c.plane_id, c.content, c.`date`, s.fbid,  s.name, s.cover FROM student as s, comment as c INNER JOIN ($sql) AS P ON c.plane_id = P.id WHERE c.sid = s.sid GROUP BY c.id asc;";
$stmtC = $mysql->prepare($sqlComment);
if ($stmtC === false) {
  err('mysql', $mysql->error, true); 
}


switch ($action) {
  case 'refresh':
    $stmt->bind_param('sss',$sid, $branch, $pid);
    $stmtC->bind_param('sss',$sid, $branch, $pid);
    break;

  case 'infinite':
    $stmt->bind_param('ssss',$sid, $branch, $pid, $limit);
    $stmtC->bind_param('ssss',$sid, $branch, $pid, $limit);
    break;

  case 'one':
    $stmt->bind_param('sss',$sid, $branch, $pid);
    $stmtC->bind_param('sss',$sid, $branch, $pid);
    break;

  case 'of':
    $stmt->bind_param('sssss',$sid, $branch, $sidOf, $pid, $limit);
    $stmtC->bind_param('sssss',$sid, $branch, $sidOf, $pid, $limit);
    break;

  default:
    $stmt->bind_param('sss',$sid, $branch, $limit);
    $stmtC->bind_param('sss',$sid, $branch, $limit);
    break;
}

//comments
$stmtC->bind_result($cid, $commentID, $sid_, $reTo, $reContent, $reDate, $reFbid, $reName, $reCover);
$stmtC->execute();
$replies = array();
while ($stmtC->fetch()) {
  $replies[$reTo][] = array('cid'=> $cid, 'commentID'=>$commentID, 'sid'=>$sid_, 'content'=>$reContent, 'date'=>$reDate, 'fbid'=>$reFbid, 'name'=>$reName, 'cover'=>$reCover);
}
$stmtC->close();

//planes
$stmt->bind_result($id, $content, $date, $sid_, $to, $uuid, $name, $fbid, $cover, $iLike, $likes, $photos);
$stmt->execute();
$data = array();
while ($stmt->fetch()) {
  $photosArr = explode(',', $photos);

	$data['plane'][] = array('id'=>$id, 'content'=>$content, 'date'=>$date, 'sid'=>$sid_, 'to'=>$to, 'name'=>$name, 'fbid'=>$fbid, 'photo'=>$photosArr, 'iLike'=>$iLike, 'like'=>$likes, 'comment'=>@$replies[$id], 'cover'=>$cover);
}
$stmt->close();





if(sizeof(@$data['plane']) > 0){
  //get notice
  $notice = getNotice($mysql);
  $data['notice'] = $notice;
}
$mysql->close();

ok($data);

?>
