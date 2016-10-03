<?php
include 'config.php';
checkActive();

//https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL
$arr = loginCms($sid, $cpw);


$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SSR_SSENRL_SCHD_W.GBL', $cookie);


//get timetable

preg_match_all("/<table cellspacing='0' cellpadding='2' width='100%' class='PSLEVEL1GRIDNBO' id='WEEKLY_SCHED_HTMLAREA'>(.*)<!-- End HTML Area -->/Us", $html, $content);

if(!isset($content[0][0])){
	err('preg', 'Processing raw data failed', true, true);
}
//$newHtml = str_replace("width='100%'", "width='970' height='400'", $content[0][0]);
$newHtml = '<div class="timetableCenter">'.$content[0][0].'</div>';

$newHtml.='<br /><br />';
echo json_encode(array('html'=>$newHtml));
?>
 