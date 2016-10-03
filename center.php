<?php
include 'config.php';
$mysql = db();
verify($mysql);
if($stmt = $mysql->prepare("SELECT name FROM student WHERE sid = ?;")) {
  $stmt -> bind_param("i", $sid);
  $stmt -> bind_result($name);
  $stmt -> execute();

  while ($stmt->fetch()) {
    $name =  $name;
  }
 
  $stmt -> close();
}else{
  //err('mysql', $mysql->error, true);
}

if($activation){
  $list[] = array('name'=>'Timetable', 'href'=>'cmsTimetable.php', 'icon'=>'ion-clock', 'emojs'=>'😳');
  $list[] = array('name'=>'Attendance', 'href'=>'cmsAttendance.php', 'icon'=>'ion-pie-graph', 'emojs'=>'😮');
  $list[] = array('name'=>'Academic Calendar', 'href'=>'calendar.php', 'icon'=>'ion-calendar', 'emojs'=>'😁');
  // $list[] = array('name'=>'Exam Timetable', 'href'=>'cmsExamTimetable.php', 'icon'=>'ion-pinpoint', 'emojs'=>'😨');
  $list[] = array('name'=>'Exam Timetable', 'href'=>'cmsExamSlip.php', 'icon'=>'ion-pinpoint', 'emojs'=>'😨');
  $list[] = array('name'=>'Exam Result', 'href'=>'cmsResultSlip.php', 'icon'=>'ion-document', 'emojs'=>'🙈');
  $list[] = array('name'=>'My Course', 'href'=>'cmsCourseHistory.php', 'icon'=>'ion-ribbon-a', 'emojs'=>'😛');
  $list[] = array('name'=>'Account Enquiry', 'href'=>'cmsAccountEnquiry.php', 'icon'=>'ion-card', 'emojs'=>'😓');
  $list[] = array('name'=>'About Me', 'href'=>'aboutMe.php', 'icon'=>'ion-coffee', 'emojs'=>'🐝');
  ok(array('text'=>"Selamat Datang <br /><span class='assertive'>{$name} ({$sid})</span>", 'list'=>$list, 'forceReload'=>false));
}else{
  $locked = '<span class="item-note">Locked</span>';
  $list[] = array('name'=>'Upgrade account to unlock', 'href'=>'activation.php', 'icon'=>'ion-ribbon-b', 'emojs'=>'😚');
  $list[] = array('name'=>'Timetable', 'href'=>'activation.php', 'icon'=>'ion-clock', 'emojs'=>$locked);
  $list[] = array('name'=>'Attendance', 'href'=>'activation.php', 'icon'=>'ion-pie-graph', 'emojs'=>$locked);
  $list[] = array('name'=>'Academic Calendar', 'href'=>'calendar.php', 'icon'=>'ion-calendar', 'emojs'=>'😁');
  // $list[] = array('name'=>'Exam Timetable', 'href'=>'activation.php', 'icon'=>'ion-pinpoint', 'emojs'=>$locked);
  $list[] = array('name'=>'Exam Timetable', 'href'=>'activation.php', 'icon'=>'ion-pinpoint', 'emojs'=>$locked);
  $list[] = array('name'=>'Exam Result', 'href'=>'cmsResultSlip.php', 'icon'=>'ion-document', 'emojs'=>'🙈');
  $list[] = array('name'=>'My Course', 'href'=>'activation.php', 'icon'=>'ion-ribbon-a', 'emojs'=>$locked);
  $list[] = array('name'=>'Account Enquiry', 'href'=>'activation.php', 'icon'=>'ion-card', 'emojs'=>$locked);
  $list[] = array('name'=>'About Me', 'href'=>'aboutMe.php', 'icon'=>'ion-coffee', 'emojs'=>'🐝');
  ok(array('text'=>"Selamat Datang <br /><span class='assertive'>{$name} ({$sid})</span>", 'list'=>$list, 'forceReload'=>true));
}
$mysql->close();
?>