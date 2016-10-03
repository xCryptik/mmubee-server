<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true'); 
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept'); 
//header("Content-type=html/text; charset=utf-8;");

date_default_timezone_set('Asia/Kuala_Lumpur');
set_time_limit(300);

require_once 'function.php';
require_once 'parallelcurl.php';
require_once 'GCMPushMessage.php'; //Android push service
require_once 'ApnsPHP/Autoload.php'; //iOS push service

define('DEBUG', true);
define('DB_SERVER',   '');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', '3306');

define('DB_DATABASE', 'mmubee');

if(DEBUG){
	error_reporting(E_ALL);
}else{
	error_reporting(E_ERROR);
}


$sid = @$_POST['sid'];
$pw = @$_POST['pw'];
$mpw = @$_POST['mpw'];
$cpw = @$_POST['cpw'];
$cpw = urlencode($cpw);
$activation = true;
$max_requests = 10; //ParallelCurl

$GCMKey = '';
//configure Apns log
class ApnsPHP_Log_Custom implements ApnsPHP_Log_Interface { public function log($sMessage){} }
$APNSKey = 'cert_key/dev.pem';
$APNSPassphrase = '';
?>
