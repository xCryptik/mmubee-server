<?php
include 'config.php';
//login
$id = 'studentid';
$pw = 'password';
$cookie = loginOnline($id,$pw);

//read news
$rawData = get("https://online.mmu.edu.my/bulletin.php", $cookie);
//<div id="tabs-1">*<div class="bulletinView">- End of list -</div></div><div id="tabs-3">
preg_match_all("/<div id=\"tabs-1\">(.*)<div class=\"bulletinView\">- End of list -<\/div><\/div><div id=\"tabs-3\">/Us", $rawData, $data);

preg_match_all("/<div class=\"bulletinContentAll\">(.*)<\/div>\s*<\/div>\s*<\/div>/Us", @$data[0][0], $list);
if(!isset($data[0][0]) || !isset($list[0])){
	err('preg','processing raw data failed', true); 
}


$new = array();
$mysql = db();

$stmt = $mysql->prepare('INSERT INTO news(title, mate, content, `date`) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE  mate = ?, content = ?, `date` = ?;');
foreach ($list[0] as $key => $value) {
	//title
	preg_match_all("/<a class='inline' href=\"#inline_content\d*\">(.*)<\/a>/Us", $value, $title);
	//mate e.g 19 Sep 2014 | CORPORATE COMMUNICATIONS UNIT (CCU)
	preg_match_all("/<span>(.*)<\/span><\/p>\s*<div style='display:none'>/Us", $value, $mate);
	//content
	preg_match_all("/<div id='inline_content\d*' style='padding:10px; background:url\(images\/gray3.jpg\);'>(.*)<\/div>\s*<\/div>\s*<\/div>/Us", $value, $content);

	$content = str_replace('"bulletinfile/', '"https://online.mmu.edu.my/bulletinfile/', $content[1][0]);

	// preg_match_all("/(.*) |/Us", $mate[1][0], $date); //match date
	// print_r($date[0]);
	$dateArr = explode(' | ', $mate[1][0]);
	//print_r($dateArr);
	$timestamp = strtotime($dateArr[0]);
	//echo $dateArr[0]." ";
	//echo $timestamp."\r\n";
	//echo $title[1][0]."\r\n";
	$date = date("Y-m-d H:i:s", $timestamp);
	//insert data
	$stmt->bind_param('sssssss', $title[1][0], $mate[1][0], $content, $date, $mate[1][0], $content, $date);
	//success meaning it's new
	if($stmt->execute()){
		$new[] = $title[1][0];
	}
}

$stmt->close();
$mysql->close();
//push news
//ok($new);
echo sizeof($new)." news updated";
?>