<?php
include 'config.php';
//sleep(1);

checkActive();
$mysql = db();

// $_POST['code'] = 'MPW2133';


start:

if(isset($_POST['code'])){
	$code = $_POST['code'];
}else{
	err('Error', 'Please upgrade to new version!', true); 
}



$stmt = $mysql->prepare("SELECT announce, tutorial, lecture  FROM `mmlsDetail` WHERE code = ?;");
if ($stmt === false) {
  err('mysql', $mysql->error, true); 
}
$stmt->bind_param('s', $code);
$stmt->bind_result($announce, $tutorial, $lecture);
$stmt->execute();

if($stmt->fetch()){
	if ($announce == '[]'){
		$announce = '[{"name":null,"email":null,"phone":null,"date":null,"content":"<br \/><strong>No Announcement<\/strong>","attachment":[]}]';
	}
	echo '{"announcement":'.$announce.',"tutorial":'.$tutorial.',"lecture":'.$lecture.'}';
}else{
	//load alive if no record

	$url = $_POST['url'];
	$cookie = loginMMLS($sid, $mpw);
	$session = "PHPSESSID={$cookie}";

	if(mmlsService($cookie, $url, $mysql, $code)){
		goto start;
	}else{
		err('Error', 'Failed to load. please try again later.', true); 
	}



}

$stmt->close();
$mysql->close();






function mmlsService($cookie, $url, &$mysql, $code){
	global $max_requests, $pushQueue;
	$session = "PHPSESSID={$cookie}";
	//registering course page
	$url = str_replace('../Courses/coursecontent', 'Courses/coursecontent', $url);
	$course = get("https://mmls.mmu.edu.my/Student/{$url}", $session);


	//setup parallelc curl
	$curl_options = array(
	    CURLOPT_SSL_VERIFYPEER => FALSE,
	    CURLOPT_SSL_VERIFYHOST => FALSE,
	    CURLOPT_HEADER => FALSE,
	    CURLOPT_FOLLOWLOCATION => TRUE,
	    CURLOPT_COOKIE => $session,
	    CURLOPT_REFERER => 'https://mmls.mmu.edu.my/',
	    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36'
	);
	$parallel = new ParallelCurl($max_requests, $curl_options);


	//get announcement
	$announcement = array();
	$announcementDone = function($rawData, $url, $ch, $data) use(&$announcement){

		//#processing raw data
		preg_match_all('/<table width="94%" height="62" border="0" align="center" cellpadding="0" cellspacing="1">(.*)<\/table>\s*<br>/Usi', $rawData, $list);

		if(!isset($list[1])){
			//err('preg', 'processing raw data failed - ann', true);
			return;
		}


		foreach ($list[1] as $key => $item) {
			//lecture name
			preg_match_all('/<td width="70%" align="left"><strong>(.*)<\/strong><\/td>/Usi', $item, $name);
			$name = $name[1][0];
			//get lecture contact save to array
			preg_match_all('/<table width="100%" border="0" cellpadding="0" cellspacing="1">(.*)<\/table>/Usi', $rawData, $contact);
			$lecturesDetail = array();
			foreach ($contact[1] as $key => $value) {
				preg_match_all('/Lecturer\'s Name<\/strong><\/div><\/td>\s*<td width="83%" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $value, $lname);
				preg_match_all('/Email<\/strong><\/div><\/td>\s*<td width="83%" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $value, $email);
				preg_match_all('/Phone<\/strong><\/div><\/td>\s*<td width="83%" height="1" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $value, $phone);
				$lectureName = trim($lname[1][0]);
				$lecturesDetail[$lectureName][] = array('name'=>$lectureName, 'email'=>trim($email[1][0]), 'phone'=>trim($phone[1][0]));
			}
			$email = @$lecturesDetail[$name][0]['email'];
			$phone = @$lecturesDetail[$name][0]['phone'];
			//date
			preg_match_all('/<td width="25%" align="left">Date Posted: <font color="#000099">(.*)<\/font><\/td>/Usi', $item, $date);
			$date = $date[1][0];
			//content
			preg_match_all('/<td height="30" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $item, $content);
			$content = $content[1][0];
			//attachment
			preg_match_all('/<a href="(.*)".*>(.*)<\/a>/Usi', $item, $attachment);
			$filelink = @$attachment[1][0];
			$filelink = str_replace('../../../../', 'https://mmls.mmu.edu.my/', $filelink);
			//attachment filename
			$file = array();
			if(isset($attachment[2][0])){
				$file = array('filename'=>trim($attachment[2][0]), 'filelink'=>trim($filelink));
			}

			$announcement[] = array('name'=>$name, 'email'=>$email, 'phone'=>$phone, 'date'=>$date, 'content'=>$content, 'attachment'=>$file);
		}
		
	};

	$parallel->startRequest('https://mmls.mmu.edu.my/Student/Courses/coursecontent/CourseOutline/CourseInfo.php', $announcementDone, array());



	//get source files
	$tutorial = array();
	$lecture = array();
	$sourceDone = function($rawData, $url, $ch, $data) use(&$tutorial, &$lecture){

		preg_match_all('/<div class="panel radius">(.*)<\/div>/Usi', $rawData, $list);
		if(!isset($list[1])){
			//err('preg', 'processing raw data failed - source');
			return;
		}

		$source = array();
		foreach ($list[1] as $key => $item) {
			//title
			preg_match_all('/<h6>(.*)<\/h6>/Usi', $item, $title);
			//filename and url
			preg_match_all('/<p><a href="(.*)".*>(.*)<\/a><\/p>/Usi', $item, $file);

			$tmp = array();
			foreach ($file[1] as $key => $val) {
				$val = str_replace('../../../../', 'https://mmls.mmu.edu.my/', $val);
				$tmp[] = array('link'=>$val, 'name'=>$file[2][$key]);
			}

			$source[] = array('title' => trim($title[1][0]), 'file'=>$tmp);
		}

		if($data['type'] == 'tutorial'){
			$tutorial = $source;
		}elseif($data['type']  == 'lecture'){
			$lecture = $source;
		}

	};

	$parallel->startRequest('https://mmls.mmu.edu.my/Student/Courses/coursecontent/Tutorials_FD/view_tutorials.php', $sourceDone, array('type'=>'tutorial'));
	$parallel->startRequest('https://mmls.mmu.edu.my/Student/Courses/coursecontent/Lecture_Notes/view_lecture_notes.php', $sourceDone, array('type'=>'lecture'));


	$parallel->finishAllRequests();


	//get course details
	$stmt = $mysql->prepare("SELECT LENGTH(announce) FROM mmlsDetail WHERE code = ?;");
	if($stmt === false){
		err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('s', $code);
	$stmt->bind_result($announceSize);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();

	$a = json_encode($announcement);
	$t = json_encode($tutorial);
	$l = json_encode($lecture);

	if(strlen($a) != $announceSize){
		//add to push queue
		$pushQueue[] = array('code'=>$code);
	}


	//insert announcement
	$stmt = $mysql->prepare('INSERT INTO mmlsDetail(announce, tutorial, lecture, code) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE  announce = ?, tutorial = ?, lecture = ?;');
	if($stmt===false){ err('mysql', $mysql->error, true); }

	$stmt->bind_param('sssssss', $a, $t, $l, $code, $a, $t, $l);
	if($stmt===false){ err('mysql', $mysql->error, true); }
	$result = $stmt->execute();
	$stmt->close();

	return $result;

}






/*
//login MMLS
$url = $_POST['url'];
$cookie = loginMMLS($sid, $mpw);
$session = "PHPSESSID={$cookie}";

//registering course page
$url = str_replace('../Courses/coursecontent', 'Courses/coursecontent', $url);
$course = get("https://mmls.mmu.edu.my/Student/{$url}", $session);


//setup parallelc curl
$curl_options = array(
    CURLOPT_SSL_VERIFYPEER => FALSE,
    CURLOPT_SSL_VERIFYHOST => FALSE,
    CURLOPT_HEADER => FALSE,
    CURLOPT_FOLLOWLOCATION => TRUE,
    CURLOPT_COOKIE => $session,
    CURLOPT_REFERER => 'https://mmls.mmu.edu.my/',
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36'
);
$parallel = new ParallelCurl($max_requests, $curl_options);


//get announcement
$announcement = array();
$announcementDone = function($rawData, $url, $ch, $data) use(&$announcement){

	//#processing raw data
	preg_match_all('/<table width="94%" height="62" border="0" align="center" cellpadding="0" cellspacing="1">(.*)<\/table>\s*<br>/Usi', $rawData, $list);

	if(!isset($list[1])){
		//err('preg', 'processing raw data failed - ann', true);
		return;
	}


	foreach ($list[1] as $key => $item) {
		//lecture name
		preg_match_all('/<td width="70%" align="left"><strong>(.*)<\/strong><\/td>/Usi', $item, $name);
		$name = $name[1][0];
		//get lecture contact save to array
		preg_match_all('/<table width="100%" border="0" cellpadding="0" cellspacing="1">(.*)<\/table>/Usi', $rawData, $contact);
		$lecturesDetail = array();
		foreach ($contact[1] as $key => $value) {
			preg_match_all('/Lecturer\'s Name<\/strong><\/div><\/td>\s*<td width="83%" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $value, $lname);
			preg_match_all('/Email<\/strong><\/div><\/td>\s*<td width="83%" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $value, $email);
			preg_match_all('/Phone<\/strong><\/div><\/td>\s*<td width="83%" height="1" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $value, $phone);
			$lectureName = trim($lname[1][0]);
			$lecturesDetail[$lectureName][] = array('name'=>$lectureName, 'email'=>trim($email[1][0]), 'phone'=>trim($phone[1][0]));
		}
		$email = $lecturesDetail[$name][0]['email'];
		$phone = $lecturesDetail[$name][0]['phone'];
		//date
		preg_match_all('/<td width="25%" align="left">Date Posted: <font color="#000099">(.*)<\/font><\/td>/Usi', $item, $date);
		$date = $date[1][0];
		//content
		preg_match_all('/<td height="30" class="mmlsbox01FontTableContent"><div align="left">(.*)<\/div><\/td>/Usi', $item, $content);
		$content = $content[1][0];
		//attachment
		preg_match_all('/<a href="(.*)".*>(.*)<\/a>/Usi', $item, $attachment);
		$filelink = @$attachment[1][0];
		$filelink = str_replace('../../../../', 'https://mmls.mmu.edu.my/', $filelink);
		//attachment filename
		$file = array();
		if(isset($attachment[2][0])){
			$file = array('filename'=>trim($attachment[2][0]), 'filelink'=>trim($filelink));
		}

		$announcement[] = array('name'=>$name, 'email'=>$email, 'phone'=>$phone, 'date'=>$date, 'content'=>$content, 'attachment'=>$file);
	}
	
};

$parallel->startRequest('https://mmls.mmu.edu.my/Student/Courses/coursecontent/CourseOutline/CourseInfo.php', $announcementDone, array());



//get source files
$tutorial = array();
$lecture = array();
$sourceDone = function($rawData, $url, $ch, $data) use(&$tutorial, &$lecture){

	preg_match_all('/<div class="panel radius">(.*)<\/div>/Usi', $rawData, $list);
	if(!isset($list[1])){
		//err('preg', 'processing raw data failed - source');
		return;
	}

	$source = array();
	foreach ($list[1] as $key => $item) {
		//title
		preg_match_all('/<h6>(.*)<\/h6>/Usi', $item, $title);
		//filename and url
		preg_match_all('/<p><a href="(.*)".*>(.*)<\/a><\/p>/Usi', $item, $file);

		$tmp = array();
		foreach ($file[1] as $key => $val) {
			$val = str_replace('../../../../', 'https://mmls.mmu.edu.my/', $val);
			$tmp[] = array('link'=>$val, 'name'=>$file[2][$key]);
		}

		$source[] = array('title' => trim($title[1][0]), 'file'=>$tmp);
	}

	if($data['type'] == 'tutorial'){
		$tutorial = $source;
	}elseif($data['type']  == 'lecture'){
		$lecture = $source;
	}

};

$parallel->startRequest('https://mmls.mmu.edu.my/Student/Courses/coursecontent/Tutorials_FD/view_tutorials.php', $sourceDone, array('type'=>'tutorial'));
$parallel->startRequest('https://mmls.mmu.edu.my/Student/Courses/coursecontent/Lecture_Notes/view_lecture_notes.php', $sourceDone, array('type'=>'lecture'));


$parallel->finishAllRequests();

ok(array('announcement'=>$announcement, 'tutorial'=> $tutorial, 'lecture'=>$lecture));

*/
?>
