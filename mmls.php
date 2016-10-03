<?php
include 'config.php';
//sleep(3);
$cookie = loginMMLS($sid, $mpw);

$mysql = db();
$mmls = mmlsList($sid, $cookie);
mmlsSave($mysql, $mmls, $sid);
ok($mmls);

$mysql->close();


?>
