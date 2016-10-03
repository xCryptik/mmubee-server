<?php
include 'config.php';


if($mpw != '' && $cpw != '' && $sid != ''){

	$mysql = db();

	$iosToken = @$_POST['iosToken'];
	$androidToken = @$_POST['androidToken'];
	$platform = @$_POST['devicePlatform'];
	$version = @$_POST['deviceVersion'];
	$MMUbeeVersion = @$_POST['mmubeeVersion'];
	$deviceUUID = @$_POST['deviceUUID'];
	$deviceModel = @$_POST['deviceModel'];


	//setup parallelc curl
	$curl_options = array(
	    CURLOPT_SSL_VERIFYPEER => FALSE,
	    CURLOPT_SSL_VERIFYHOST => FALSE,
	    CURLOPT_HEADER => TRUE,
	    CURLOPT_CONNECTTIMEOUT => 0,
	    CURLOPT_TIMEOUT => 1800, //1800 second = 30 minites
	    CURLOPT_FOLLOWLOCATION => FALSE,
	    CURLOPT_REFERER => 'https://mmls.mmu.edu.my/',
	    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36'
	);
	$parallel = new ParallelCurl($max_requests, $curl_options);

	//login mmls
	$mmlsCookie = '';
	$mmlsDone = function($content, $url, $ch, $data) use(&$mmlsCookie){
		//check network error
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  if(curl_errno($ch) > 0 || $httpCode != 302){
	  	$mmlsCookie = 'network';
	  	return;
	  }
	  //check login status
		$matchSession = preg_match_all("/Set-Cookie: PHPSESSID=(.*);/Us", $content, $sessid); //match session code
	 	$checkLogin = preg_match("/Location: Student\/Default\/Main\.php/s", $content); //match session code
		if($matchSession == false || $checkLogin == false){
	  	$mmlsCookie = 'login';
	  	//err('login', 'Invalid MMLS password or ID', true, false);
	  	return;
		}else{ //login success
			$mmlsCookie = $sessid[1][0];
		}
	};

	$parallel->startRequest('https://mmls.mmu.edu.my/check_login.php', $mmlsDone, array(), "hst=mmls.melaka&key=1809488277&txtUserID={$sid}&txtPassword={$mpw}&txtUser=1&Submit=Login&__ncforminfo=3QkCYnu4b_HKTglvVKEzbNywpoiK4wtfYDPSFrv6ZLTqF6r1hr4HX7s7B7fIzD0RNthmBCnsX_REV3A5FxUK6Yn9lq04tPG-92A01_xHG2fHo7G9CYJxfGYI5xs0IWiwhEO6W3sTpsQILYFLsQeyYlYFSwpE_sf4");



	//CMS login
	$cmsCookie = '';
	$CMSDone =  function($content, $url, $ch, $data) use(&$cmsCookie){	
		
		//check network
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  if(curl_errno($ch) > 0 || $httpCode != 302){
	  	$cmsCookie = 'network';
	  	return;
	  }

		//check if login succesed
		$matchLocation = preg_match_all("/Location: (.*)\r/Us", $content, $location); //match session code
		if(strpos($location[1][0], 'errorCode') !== false){
			$cmsCookie = 'login';
			//err('login', 'Invalid CaMSys password or ID', true, false);
			return;
		}

		//get all cookie
		$matchCookie = preg_match_all("/Set-cookie: (.*);/Us", $content, $setcookie); //match session code
		//$result = array('location'=>$location[1][0], 'cookie'=>$setcookie[1]);
	};

	$parallel->startRequest('https://cms.mmu.edu.my/psp/csprd/?cmd=login&languageCd=ENG', $CMSDone, array(), "timezoneOffset=-480&userid={$sid}&pwd={$cpw}");
 	$parallel->finishAllRequests();

	//checking result
 	switch ($mmlsCookie) {
 		case 'network':
 			err('network', curl_error($ch).' Httpcode:'.$httpCode, true, true);
 			break;
 		case 'login':
 			err('login', 'Invalid MMLS password or ID', true, false);
 			break;
 		default:
 			break;
 	}
 	switch ($cmsCookie) {
 		case 'network':
 			err('network', curl_error($ch).' Httpcode:'.$httpCode, true, true);
 			break;
 		case 'login':
 			err('login', 'Invalid CaMSys password or ID', true, false);
 			break;
 		default:
 			break;
 	}


	//get mmls list
	$mmls = mmlsList($sid, $mmlsCookie);

	//get student branch
	$branch = $mmls['subject'][0]['campus'];
	if(!isset($branch)){
		$branch = 'CYBERJAYA';
	}else{
		$branch = strtoupper($branch);
	}

	//insert student
	$stmt = $mysql->prepare('INSERT INTO student(sid, mpw, cpw, branch) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE  mpw = ?, cpw = ?, branch = ?;');
	if($stmt===false){ err('mysql', $mysql->error, true); }
	$stmt->bind_param('sssssss',$sid, $mpw, $cpw, $branch, $mpw, $cpw, $branch);
	$stmt->execute();
	$stmt->close();


	//insert mmls list
	mmlsSave($mysql, $mmls, $sid);

	//insert device
	insertDevice($mysql, $iosToken, $androidToken, $platform, $version, $MMUbeeVersion, $deviceUUID, $deviceModel);

	$mysql->close();

	ok(array('mmls'=>$mmls, 'branch'=>$branch));

}else{
	err('blank', 'ID and Passwords can not be blank', true);
}

?>