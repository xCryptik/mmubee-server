<?php
include 'config.php';
checkActive();

//https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL
$arr = loginCms($sid, $cpw);


$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_MANAGE_EXAMS.N_SS_EXAM_TIMETBL.GBL?FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.N_SS_EXAM_TIMETBL_GBL&IsFolder=false&IgnoreParamTempl=FolderPath%2cIsFolder', $cookie);

preg_match_all("/<table  border='0' id='ACE_N_SS_TTBL_WRK_HIDE_COMMENTS'.*(.*)<div id='pt_dragtxt' class='PSLEVEL1GRIDCOLUMNHDR'><\/div>/Us", $html, $content);

if(!isset($content[0][0])){
	err('preg', 'Processing raw data failed', true, true);
}


$findme   = 'Your Exam Timetable for current trimester is not yet not ready.';
$pos = strpos($content[0][0], $findme);
if ($pos === false) { //not found
	$html = $content[0][0];
} else {
$html = <<<html
<div class="card" style="margin-top:20px;">
  <div class="item item-text-wrap item-body">
 
    Your Exam Timetable for current trimester is not yet not ready.

  </div>
</div>
html;
}

ok(array('html'=>$html));
?>