<?php
include 'config.php';
checkActive();

//https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL

$arr = loginCms($sid, $cpw);


$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SR_STUDENT_RECORDS.N_SR_SS_ATTEND_PCT.GBL?FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HCCC_ACADEMIC_RECORDS.HC_SSS_ATTENDANCE_PERCENT_GBL&IsFolder=false&IgnoreParamTempl=FolderPath%2cIsFolder', $cookie);


//get item sum.

preg_match_all("/<span class='PSGRIDCOUNTER' >.*of (.*)<\/span>/Us", $html, $content);

if(!isset($content[1][0])){

  //get error
  preg_match_all("/<DIV id='win0div\\\$ICField\\\$112\\\$'>(.*)<\/DIV>/Us", $html, $error);
  //print_r($error);

$html = <<<html
<div class="card" style="margin-top:20px;">
  <div class="item item-text-wrap item-body">
 
    <div class="icon ion-sad" style="font-size:50px; text-align:center;"></div>
    <br />
    {$error[1][0]}

  </div>
</div>
html;
  ok(array('html'=>$html));
  exit();
}

$sum = $content[1][0];

 

//get subject code
//<span  class='PSEDITBOX_DISPONLY' id='N_STN_ENRL_SSVW_CATALOG_NBR$0'>2133</span>
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_STN_ENRL_SSVW_CATALOG_NBR\\\$\d*'>(.*)<\/span>/Us", $html, $code);
//<span  class='PSEDITBOX_DISPONLY' id='N_STN_ENRL_SSVW_SUBJECT$0'>MPW</span>
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_STN_ENRL_SSVW_SUBJECT\\\$\d*'>(.*)<\/span>/Us", $html, $area);
$subjectCode = array();
foreach ($code[1] as $key => $value) {
  $subjectCode[] = $area[1][$key].$value;
}

//<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_DESCR1$0'>Lecture</span>
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='SSF_SS_CHRG_ACT_SSF_POSTED_DATE\\\$\d*'>(.*)<\/span>/Us", $html, $postDate);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_DESCR\\\$\d*'>(.*)<\/span>/Us", $html, $name);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_N_CURR_ATTND\\\$\d*'>(.*)<\/span>/Us", $html, $attendance);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_N_ATTND_PERC\\\$\d*'>(.*)<\/span>/Us", $html, $barring);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_DESCR1\\\$\d*'>(.*)<\/span>/Us", $html, $component);
/*

if(!isset($content[0][0])){
    $debug = array('title'=>'MMUbee error: examtimetable #1', 'body'=>"ID: {$sid}\r\nPW: {$pw}\r\nMPW: {$mpw}\r\nCPW: {$cpw}\r\n");
    err('Unable to processing raw data.', $debug);
    exit();
}
*/
$html = '<style type="text/css">.item-body h2{ margin-top: 0px; margin-bottom: 7px; } .card .item{ min-height:0; }</style>';
for($i = 0; $i<$sum; $i++){
  $html.="
    <li class='item'>
    <h2>".$name[1][$i]."</h2><br />
    <p style='margin-top:-25px; color:rgb(52, 152, 219);'>
      <span style='color:#4a6b82;'>".$subjectCode[$i]." - ".$component[1][$i]."</span><br />
      Attendance&nbsp;&nbsp;&nbsp;".$attendance[1][$i]."% <br />
      Barring&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$barring[1][$i]."%
    </p></li>
    ";
}
//print_r($subjectCode);
ok(array('html'=>$html));
?>
 