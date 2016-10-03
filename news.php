<?php
include 'config.php';
$mysql = db();

$stmt = $mysql->prepare("SELECT title, mate, content FROM news ORDER BY `date` DESC LIMIT 0, 50");
if($stmt === false){
	err('mysql', $mysql->error, true); 
}
$stmt->execute();
$stmt->bind_result($title, $mate, $content);
$data = array();
while ($stmt->fetch()) {
	$data[] = array('title'=>$title, 'mate'=>$mate, 'content'=>$content);
}

$stmt->close();
$mysql->close();

ok($data);

?>