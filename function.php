<?php

function db(){
	$mysql = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE, DB_PORT);

	if ($mysql->connect_errno) {
		err('mysql', $mysql->connect_error, true, true);
		/*
		$err = "Failed to connect to MySQL: (" . $mysql->connect_errno . ") " . $mysql->connect_error;
		$debug = array('title'=>'MMUbee error: function.php:#1', 'body'=>$err);
		err($err,$debug);
		exit();
		*/
	}
	return $mysql;
}

function verify($mysql){
	global $sid, $mpw;
	$stmt = $mysql->prepare("SELECT id FROM student WHERE sid = ? and mpw = ?;");
	$stmt->bind_param('ss', $sid, $mpw);
	$stmt->execute();
	//$stmt->bind_result($id);
	$result = $stmt->fetch();
	$stmt->close();

	if(!$result){
		err('verify', 'failed to verify student account', true);
	}
}

function mmlsSave($mysql, $mmls, $id){
	$mmlsJSON = json_encode($mmls);
	$stmt = $mysql->prepare('INSERT INTO mmls(sid, json) VALUES (?, ?) ON DUPLICATE KEY UPDATE json=?;');
	if($stmt===false){
		err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('sss',$id, $mmlsJSON, $mmlsJSON);
	if($stmt===false){
		err('mysql', $mysql->error, true); 
	}
	$stmt->execute();
	$stmt->close();

	//clear subjects
	$stmt = $mysql->prepare('DELETE FROM subject where sid = ?;');
	if($stmt===false){
		err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('s',$id);
	if($stmt===false){
		err('mysql', $mysql->error, true); 
	}
	$stmt->execute();
	$stmt->close();

	//insert new subjects
	$stmt = $mysql->prepare('INSERT INTO subject(sid, name, code, url, class, campus, color) VALUES (?, ?, ?, ?, ?, ?, ?);');
	foreach ($mmls['subject'] as $key => $value) {
		if($stmt==false){
			err('mysql', $mysql->error, true); 
		}
		$stmt->bind_param('sssssss', $id, $value['name'],  $value['code'],  $value['url'],  $value['class'],  $value['campus'],  $value['color']);
		if($stmt==false){
			err('mysql', $mysql->error, true); 
		}
		$stmt->execute();
	}
	$stmt->close();


}

function mmlsList($id, $cookie){

	//loading resource
	$session = "PHPSESSID={$cookie}";
	$rawData = get("https://mmls.mmu.edu.my/Student/Default/Main.php", $session);
	if($rawData == false){
		err('network', 'Access MMLS failed', true, true);
	}
	//processing raw data
	preg_match_all("/<td class=\"view\">(.*)<\/tr>/Us", $rawData, $list);
	if(!isset($list[1])){
		err('preg', 'processing raw data failed', true, true);
	}

	//colors for course code
	$color = array(
		"#34495e",
		"#9b59b6",
		"#3498db",
		"#2ecc71",
		"#1abc9c",
		"#c0392b",
		"#2c3e50",
		"#2980b9",
		"#27ae60",
		"#16a085",
		"#95a5a6",
		"#e74c3c",
		"#8e44ad",
		"#e67e22",
		"#f1c40f",
		"#1abc9c",
		"#16a085",
		"#3498db",
		"#8e44ad",
		"#34495e" 
		);

	foreach($list[1] as $key => $item){
		//url
		preg_match_all("/<a href=\"(.*)\">/Us", $item, $url);

		//name
		preg_match_all("/\">(.*)\<\/a><\/td>/Us", $item, $name);

		//check whatever it's a Favourite Courses or not
		if(strpos($url[1][0], "../Search/") !== false){
			preg_match_all("/<td width=\"21%\" >(.*)<\/td>/Us", $item, $fname);
			$name[1][0] = $name[1][0]." - ".trim($fname[1][0]);
		}
		//group
		preg_match_all("/<td>(.*)<\/td>/Us", $item, $group);
	 	if(!isset($group[1][0])){
	 		$group[1][0] = "Favourite";
	 	}

	 	$name_code = explode(" - ", trim($name[1][0]));
	 	$url = str_replace('../Courses/coursecontent', 'Courses/coursecontent', trim($url[1][0]));
	 	global $activation;
	 	if($activation){
			$data['subject'][] = array('name'=>$name_code[1], 'code'=>trim($name_code[0]), 'url'=>trim($url),'class'=>@$group[1][0], 'campus'=>@$group[1][1], 'color'=>$color[$key], 'activation'=>true);
		}else{
			$data['subject'][] = array('name'=>$name_code[1], 'code'=>trim($name_code[0]), 'url'=>trim($url),'class'=>@$group[1][0], 'campus'=>@$group[1][1], 'color'=>$color[$key], 'activation'=>false);
		}
	}

	//$data['cookie'] = $cookie;
		
	if(count($data['subject'])<=0){
		err('empty', 'Subjects not found', true, true);
	}else{
		return $data;
	}
}

function saveProfile($session){
	$url = 'https://icems.mmu.edu.my/sic/profile/viewprofile.jsp';
	$content = get($url, $session);

	preg_match_all('/Name<\/b><\/font><\/td>(.*)<\/font><\/td>/Usi', $content, $nameArr);
	preg_match_all('/Degree<\/font><\/b><\/td>(.*)<\/font><\/td>/Usi', $content, $degreeArr);
	preg_match_all('/Faculty<\/font><\/b><\/td>(.*)<\/font><\/td>/Usi', $content, $facultyArr);
	preg_match_all('/Branch\s*<\/font><\/b><\/td>(.*)<\/font><\/td>/Usi', $content, $branchArr);
	preg_match_all('/Citizenship<\/font><\/b><\/font><\/td>(.*)<\/font><\/td>/Usi', $content, $citizenshipArr);

	function trimRaw($str){
		$n = strip_tags($str);
		$n = str_replace(':&nbsp;', '', $n);
		$n = str_replace(':', '', $n);
		return trim($n);
	}

	$name = trimRaw($nameArr[1][0]);
	$degree = trimRaw($degreeArr[1][0]);
	$faculty = trimRaw($facultyArr[1][0]);
	$branch = trimRaw($branchArr[1][0]);
	$branch = str_replace('MULTIMEDIA UNIVERSITY - ', '', $branch);
	$citizenship = trimRaw($citizenshipArr[1][0]);
	return array('name'=>$name, 'degree'=>$degree, 'faculty'=>$faculty, 'branch'=>$branch, 'citizenship'=>$citizenship);
}


//get content by url and sessionid
function get($url,$session){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_REFERER, "https://icems.mmu.edu.my/sic/login.jsp");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false );
	curl_setopt($ch, CURLOPT_COOKIE, $session);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1800);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_HEADER,0);
	$result = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_errno($ch) > 0){
  	err('Network error', curl_error($ch).' Httpcode:'.$httpCode, true);
  }else{ 
     curl_close($ch);
  }
	return $result;
}

function checkActive(){
	global $activation;
	if(!$activation){
		err('activation', 'Please upgrade your standard student account before using this.', true);
	}
}

function loginOnline($id,$password){

	$loginInfo = "form_loginUsername={$id}&form_loginPassword={$password}&submit=LOG+IN";
	//get login session
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://online.mmu.edu.my/index.php");
	curl_setopt($ch, CURLOPT_REFERER, "https://www.mmu.edu.my/");
	curl_setopt($ch,CURLOPT_POST, count($loginInfo));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $loginInfo);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 400);
	curl_setopt($ch, CURLOPT_HEADER,1);
	$result = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_errno($ch) > 0 || $httpCode != 200){
  	err('network', curl_error($ch).' Httpcode:'.$httpCode, true, true);
  	// echo json_encode(array('err'=>'network', 'detail'=> curl_error($ch), 'httpCode'=>$httpCode));
  	// exit();
  }else{ 
     curl_close($ch);
  }
	$matchSession = preg_match_all("/PHPSESSID=(.*);/Us", $result, $sessid); //match session code

	if (strpos($result,'Sorry, Invalid') !== false || $matchSession == false) {
    	//fail to login
    	err('login', 'Invalid Onlie Portal password or ID', true, false);
	}else{
		return $sessid[0][0];
	}
}


function loginMMU($id,$password){

	$loginInfo = "id={$id}&pwd={$password}";
	//get login session
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://icems.mmu.edu.my/sic/vlogin.jsp");
	curl_setopt($ch, CURLOPT_REFERER, "https://icems.mmu.edu.my/sic/login.jsp");
	curl_setopt($ch, CURLOPT_POST, count($loginInfo));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $loginInfo);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_HEADER,1);
	$result = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_errno($ch) > 0 || $httpCode != 200){
  	err('network', curl_error($ch).' Httpcode:'.$httpCode, true, true);
  	// echo json_encode(array('err'=>'network', 'detail'=> curl_error($ch), 'httpCode'=>$httpCode));
  	// exit();
  }else{ 
     curl_close($ch);
  }

	$matchSession = preg_match_all("/JSESSIONID=(.*);/", $result, $sessid); //match session code

	if (strpos($result,'Sorry, Invalid') !== false || $matchSession == false) {
    	//fail to login
    	// echo json_encode(array('err'=>'login'));
    	// exit();
		err('login', 'Invalid ICEMS password or ID', true, false);
	}else{
		return $sessid[0][0];
	}
}


function insertDevice($mysql, $iosToken, $androidToken, $platform, $version, $MMUbeeVersion, $deviceUUID, $deviceModel){
	global $sid;
	if(empty($iosToken)){
		$iosToken = NULL;
	}

	if(empty($androidToken)){
		$androidToken = NULL;
	}


	//insert student
	$stmt = $mysql->prepare('INSERT INTO device(sid, iostoken, androidtoken, platform, version, mmubee, uuid, model) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE sid = ?, iostoken = ?, androidtoken = ?, platform = ?, version = ?, mmubee = ?, uuid = ?, model = ?;');
	if($stmt===false){ err('mysql', $mysql->error, true); }
	$stmt->bind_param('ssssssssssssssss', $sid, $iosToken, $androidToken, $platform, $version, $MMUbeeVersion, $deviceUUID, $deviceModel, $sid, $iosToken, $androidToken, $platform, $version, $MMUbeeVersion, $deviceUUID, $deviceModel);
	$stmt->execute();
	$stmt->close();

/*
	//delete all android tokens since android generate token in every launch 
	$stmt = $mysql->prepare('DELETE FROM `device` WHERE sid = ? AND androidtoken is not NULL;');
	if($stmt===false){ err('mysql', $mysql->error, true); }
	$stmt->bind_param('s',$sid);
	if($stmt===false){ err('mysql', $mysql->error, true); }
	$stmt->execute();
	$stmt->close();
*/

	//check if iostoken and androidtoken not exists
	/*
	$stmt = $mysql->prepare("SELECT EXISTS(SELECT * FROM device WHERE iostoken = ? or androidtoken = ?);");
	if ($stmt === false) {
	  err('mysql', $mysql->error, true); 
	}
	$stmt->bind_param('ss', $iosToken, $androidToken);
	$stmt->bind_result($exists);
	$stmt->execute();
	$stmt->fetch();
	$stmt->close();
	if($exists == 0 && ($androidToken != NULL || $iosToken != NULL)){
		//update device tokens
		$stmt = $mysql->prepare('INSERT INTO device(sid, iostoken, androidtoken, platform, version, mmubee, uuid, model) VALUES(?,?,?,?,?,?,?,?);');
		if($stmt===false){ err('mysql', $mysql->error, true); }
		$stmt->bind_param('ssssssss',$sid, $iosToken, $androidToken, $platform, $version, $MMUbeeVersion, $deviceUUID, $deviceModel);
		if($stmt===false){ err('mysql', $mysql->error, true); }
		$stmt->execute();
		$stmt->close();

	}

*/
	//insert student
	/* this version get user detail from mmu website
	*  removed due to unstable reason.
	$profile = saveProfile($session2);
	$stmt = $mysql->prepare('INSERT INTO student(sid, pw, mpw, cpw, mName, degree, faculty, branch, citizenship) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE  pw = ?, mpw = ?, cpw = ?, mName = ?, degree = ?, faculty = ?, branch = ?, citizenship = ?;');
	if($stmt===false){ err('mysql', $mysql->error, true); }
	$stmt->bind_param('sssssssssssssssss',$sid, $pw, $mpw, $cpw, $profile['name'], $profile['degree'], $profile['faculty'], $profile['branch'], $profile['citizenship'], $pw, $mpw, $cpw, $profile['name'], $profile['degree'], $profile['faculty'], $profile['branch'], $profile['citizenship']);
	$stmt->execute();
	$stmt->close();
	*/
	
}

function loginMMLS($id, $password){

	//$loginInfo = "hst=mmls.melaka&key=1429051737&txtUserID={$id}&txtPassword={$password}";
	$loginInfo = "hst=mmls.melaka&key=1809488277&txtUserID={$id}&txtPassword={$password}&txtUser=1&Submit=Login&__ncforminfo=3QkCYnu4b_HKTglvVKEzbNywpoiK4wtfYDPSFrv6ZLTqF6r1hr4HX7s7B7fIzD0RNthmBCnsX_REV3A5FxUK6Yn9lq04tPG-92A01_xHG2fHo7G9CYJxfGYI5xs0IWiwhEO6W3sTpsQILYFLsQeyYlYFSwpE_sf4";
	//get login session
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://mmls.mmu.edu.my/check_login.php");
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:210.195.237.141', 'CLIENT-IP:210.195.237.141'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	//curl_setopt($ch, CURLOPT_TIMEOUT, 4000);
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	//curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
	//curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

/*
	//$proxyauth = 'user:password';
	$filename = "proxyList.txt";
	//get rand line
	$lines = file($filename);
	$proxy = $lines[array_rand($lines)];
	$proxy = trim($proxy);
 
	//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);

	if($proxy != ''){
		//echo "Using ".$proxy."\r\n";
    	curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
	*/
	 
	curl_setopt($ch, CURLOPT_REFERER, "https://mmls.mmu.edu.my/");
	curl_setopt($ch,CURLOPT_POST, count($loginInfo));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $loginInfo);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_HEADER,1);
	$result = curl_exec($ch);

	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_errno($ch) > 0 || $httpCode != 302){
  	err('network', curl_error($ch).' Httpcode:'.$httpCode, true, true);
  	// echo json_encode(array('err'=>'network', 'detail'=> curl_error($ch), 'httpCode'=>$httpCode));
  	// exit();
  }else{ 
     curl_close($ch);
  }

	//print_r($result);
	$matchSession = preg_match_all("/Set-Cookie: PHPSESSID=(.*);/Us", $result, $sessid); //match session code
	//print_r($result);
 	$checkLogin = preg_match("/Location: Student\/Default\/Main\.php/s", $result); //match session code
 	//print_r($checkLogin);
	//print_r($sessid);
	if($matchSession == false || $checkLogin == false){
		// echo json_encode(array('err'=>'login'));
		// exit();
		err('login', 'Invalid MMLS password or ID', true, false);
	}else{
		return $sessid[1][0]; 
	}
}


function loginCms($id, $password){

	$loginInfo = "timezoneOffset=-480&userid={$id}&pwd={$password}";
	$cookie = "_ga=GA1.3.1062791823.1411891315; __utma=49467066.1062791823.1411891315.1417060002.1417929821.9; __utmz=49467066.1417929821.9.6.utmcsr=online.mmu.edu.my|utmccn=(referral)|utmcmd=referral|utmcct=/index.php; VP168CBJ3W-8000-PORTAL-PSJSESSIONID=ok4q0IzC1NXWIfEERFKwa0OKsL2gJseq!-1094664518; SignOnDefault={$id}; PS_TOKENEXPIRE=08_Dec_2014_16:54:11_GMT; HPTabName=DEFAULT; HPTabNameRemote=; https%3a%2f%2fcms.mmu.edu.my%2fpsp%2fcsprd%2femployee%2fhrms%2frefresh=list:%20%3Ftab%3Dremoteunifieddashboard%7C%3Frp%3Dremoteunifieddashboard; PS_LOGINLIST=; ExpirePage=; PS_TOKENEXPIRE=";
	//get login session
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://cms.mmu.edu.my/psp/csprd/?cmd=login&languageCd=ENG");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Origin:https://cms.mmu.edu.my', 'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_TIMEOUT, 400);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	// curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
	// curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
	curl_setopt($ch, CURLOPT_REFERER, "https://cms.mmu.edu.my/psp/csprd/?cmd=login&languageCd=ENG");
	curl_setopt($ch, CURLOPT_POST, count($loginInfo));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $loginInfo);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_HEADER,1);
	$result = curl_exec($ch);

	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if(curl_errno($ch) > 0 || $httpCode != 302){
  	err('network', curl_error($ch).' Httpcode:'.$httpCode, true, true);
  	// echo json_encode(array('err'=>'network', 'detail'=> curl_error($ch), 'httpCode'=>$httpCode));
  	// exit();
  }else{ 
     curl_close($ch);
  }


	//get location and check if login succesed
	$matchLocation = preg_match_all("/Location: (.*)\r/Us", $result, $location); //match session code
	 
	if(strpos($location[1][0], 'errorCode') !== false){
		// echo json_encode(array('err'=>'login'));
		// exit();
		err('login', 'Invalid CaMSys password or ID', true, false);
	}

	//get all cookie
	$matchCookie = preg_match_all("/Set-cookie: (.*);/Us",$result, $setcookie); //match session code
	//print_r($setcookie);
	return array('location'=>$location[1][0], 'cookie'=>$setcookie[1]);

}

/*
function loginFinancial($id,$password){
	$loginInfo = "studid={$id}&passwd={$password}&submit=Login";
	//get login session
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://icems.mmu.edu.my/sfik/sik_vlogin.jsp");
	curl_setopt($ch, CURLOPT_REFERER, "https://icems.mmu.edu.my/sfik/");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch,CURLOPT_POST, count($loginInfo));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $loginInfo);
	curl_setopt($ch, CURLOPT_TIMEOUT, 400);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_HEADER,1);
	$result = curl_exec($ch);
	//print_r($result);
	$login = preg_match_all("/JSESSIONID=(.*);/", $result, $sessid); //match session code
	//print_r($sessid[0][0]);
	if($login == false){
		return false;
	}{
		return $sessid[0][0];
	}
}*/

//send email in background
function ok($arr = array('ok')){
	echo json_encode($arr);
}

function err($errCode, $errDetail, $exit = false,  $store = false){

	$err = array('err'=>$errCode, 'detail'=>$errDetail);
	echo json_encode($err);
	if($store){
		//TODO store err
	}
	if($exit){
		exit();
	}
}

function ignore($arr = array('ok')){

	if(ob_get_contents()) ob_end_clean();
	@header("Connection: close");
	ignore_user_abort(true); // just to be safe
	ob_start();
 
	//output something to user

	echo json_encode($arr);

	$size = ob_get_length();
	@header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush(); // Unless both are called !
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
	}

	// $response = json_encode($arr);
	// ignore_user_abort(true);
	// header("Connection: close");
	// header("Content-Length: " . mb_strlen($response));
	// echo $response;
	// flush();
}
/*
function err($msg, $params){
 
	if(ob_get_contents()) ob_end_clean();
	header("Connection: close");
	ignore_user_abort(true); // just to be safe
	ob_start();
 
	//output something to user
	echo json_encode(array('err'=>$msg));
 
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush(); // Unless both are called !

	//sending email
	$postData = '';
	//create name value pairs seperated by &
	foreach($params as $k => $v){ 
		$postData .= $k . '='.$v.'&'; 
	}
	rtrim($postData, '&');
	$ch = curl_init();  
	curl_setopt($ch,CURLOPT_URL, "http://zh.my:7000/email");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_HEADER, false); 
	curl_setopt($ch, CURLOPT_POST, count($postData));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);    
	curl_exec($ch);
	curl_close($ch);
 
}
*/


// Function to get the client IP address
function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}



function notice($mysql, $plane, $object, $type){
	global $sid;

	//check if not my own's plane
	if($stmt = $mysql->prepare("SELECT p.sid FROM plane as p WHERE p.id = ?;")) {
	  // s - string, b - blob, i - int, etc 
	  $stmt->bind_param("s", $plane);
	  $stmt->bind_result($psid);
	  $stmt->execute();
	  $stmt->fetch();
	  $stmt->close();
	}else{
	  err('mysql', $mysql->error, true); 
	}

	if($psid == $sid){
		return false;
	}

	if($type == 1){ //comment
		$stmt = $mysql->prepare("INSERT INTO `notice`(plane_id, comment_id, sid, `read`) VALUES(?,?,?,?);");
	}else if($type == 2){ //like
		$stmt = $mysql->prepare("INSERT INTO `notice`(plane_id, like_id, sid, `read`) VALUES(?,?,?,?);");
	}else if($type == 3){ // flying plane
		//$stmt = $mysql->prepare("INSERT INTO `notice`(plane_id, sid, `read`) VALUES(?,?,?);");
	}

	if ($stmt === false) {
	 err('mysql', $mysql->error, true); 
	}
	$read = 0;
	$stmt->bind_param('dddd', $plane, $object, $sid, $read);
	/*
	if($type == 3){
		$stmt->bind_param('ddd', $plane, $id, $read);
	}else{
		$stmt->bind_param('dddd', $plane, $object, $id, $read);
	}
	*/
	if ($stmt === false) {
	 err('mysql', $mysql->error, true); 
	}
	$result = $stmt->execute();

	$stmt->close();

	if($result){
		return true;
	}else{
		return false;
	}


}



function getNotice($mysql){
	global $sid;
	/*
  //sleep(9);
  $mysql = @new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
  if ($mysql->connect_errno) {
    $err = "Failed to connect to MySQL: (" . $mysql->connect_errno . ") " . $mysql->connect_error;
    $debug = array('title'=>'MMUbee error: notice.php:#1', 'body'=>$err);
    err($err,$debug);
    exit();
  }
  */

  $data = array();

  //select all comment notice
  if($stmt = $mysql->prepare("SELECT n.id, n.plane_id, n.comment_id, n.like_id, n.sid, s.name, s.fbid, n.date, c.content from notice as n, plane as p, student as s, comment as c where n.plane_id = p.id and p.sid = ? and n.read = 0 and n.sid = s.sid and n.sid != p.sid and  c.id = n.comment_id  GROUP BY n.id ORDER BY n.id DESC;")) {
    $stmt -> bind_param("d", $sid);

    $stmt -> bind_result($nid, $plane, $comment, $like, $sid_, $name, $fbid, $date, $content);
    $stmt -> execute();
  
    while ($stmt->fetch()) {
      $data[] = array('nid'=>$nid, 'plane'=>$plane, 'sid'=>$sid_, 'type'=>'comment', 'name'=>$name,'fbid'=>$fbid, 'date'=>$date, 'content'=>$content, 'comment_id'=>$comment, 'like_id'=>$like);
    }
 
  	$stmt -> close();
  }else{
  	err('mysql', $mysql->error, true); 
  }

  
   //select all like notice
  if($stmt = $mysql->prepare("SELECT n.id, n.plane_id, n.comment_id, n.like_id, n.sid, s.name, s.fbid, n.date  from notice as n, plane as p, student as s, `like` as l where n.plane_id = p.id and p.sid = ? and n.read = 0 and n.sid = s.sid and n.sid != p.sid and  l.id = n.like_id GROUP BY n.id ORDER BY n.id DESC;")) {
    $stmt -> bind_param("d", $sid);

    $stmt -> bind_result($nid, $plane, $comment, $like, $sid_, $name, $fbid, $date);
    $stmt -> execute();
    while ($stmt->fetch()) {
      $data[] = array('nid'=>$nid, 'plane'=>$plane, 'sid'=>$sid_, 'type'=>'like', 'name'=>$name,'fbid'=>$fbid, 'date'=>$date, 'content'=>'', 'comment_id'=>$comment, 'like_id'=>$like);
    }
 		$stmt -> close();
  }else{
  	err('mysql', $mysql->error, true); 
  }

  //save all as readed
  return $data;
}

 

function push($title, $body, $badge, $token, $platform, $detail = array()){
	global $APNSKey, $APNSPassphrase, $GCMKey;

	if($platform == 'iOS'){

		$message = $title.$body;
		$push = new ApnsPHP_Push(
			ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
			$APNSKey
		);

		$push->setLogger(new ApnsPHP_Log_Custom);
		
		//passphrase code
		$push->setProviderCertificatePassphrase($APNSPassphrase);
		$push->connect();

		foreach ($token as $key => $value) {
			$apns = new ApnsPHP_Message($value);
			$apns->setBadge($badge);
			$apns->setText($message);
			$apns->setSound();
			$apns->setCustomProperty('detail', $detail);
			$push->add($apns);
		}

		// Send all messages in the message queue
		$push->send();
		// Disconnect from the Apple Push Notification Service
		$push->disconnect();

		$aErrorQueue = $push->getErrors();
		if (!empty($aErrorQueue)) {
			echo $aErrorQueue;
		}
	
	}else if($platform == 'Android'){
		$gcpm = new GCMPushMessage($GCMKey);
		$gcpm->setDevices($token);
		$response = $gcpm->send($body, array('title' => $title, 'msgcnt'=>$badge, 'detail'=>$detail));
		//print_r($response);
		$responseArr = json_decode($response,1);
		if($responseArr['failure']){
			//delete device record if invalid registration number
			foreach ($responseArr['results'] as $key => $value) {
				if(isset($value['error']) && $value['error'] == 'InvalidRegistration'){
					$mysql = db();
					$stmt = $mysql->prepare("DELETE FROM `device` WHERE androidtoken = ?;");

					if ($stmt === false) {
					  err('mysql', $mysql->error, true); 
					}

					$stmt->bind_param('s',$token[$key]);
					$result = $stmt->execute();

					if(!$result){
						err('mysql', $mysql->error); 
					}

					$stmt->close();
					$mysql->close();
				}
			}
		}
		//print_r($responseArr);
	}
}
?>