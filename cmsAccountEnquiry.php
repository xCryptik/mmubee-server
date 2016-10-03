<?php
include 'config.php';
checkActive();

//https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL
$arr = loginCms($sid, $cpw);


$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.N_SSF_ACNT_SUMMARY.GBL?Page=N_SSF_ACNT_SUMMARY&Action=U', $cookie);


preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_CUST_SS_DRVD_ACCOUNT_BALANCE'>(.*)<\/span>/Us", $html, $balance);

preg_match_all("/<DIV id='win0divDERIVED_SSF_MSG_SSF_MSG_LONG3'>(.*)<\/DIV>/Us", $html, $nobalance);


//$newHtml = str_replace("<img src=/cs/csprd/cache/BULLET_2.JPG width='4' height='4' alt='' title='' class='PSSTATICIMAGE' />", "",$content[1][0]);
// print_r($newHtml);
//print_r($subjectCode);
// ok(array('html'=>$newHtml));


/*
$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.N_SSF_ACT_ACTIVITY.GBL?Page=N_SSF_ACNT_ACTVTY&Action=U', $cookie);

preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_DESCR1\\\$\d*'>(.*)<\/span>/Us", $html, $component);
// preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_DESCR\\\$\d*'>(.*)<\/span>/Us", $html, $name);
// preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_N_CURR_ATTND\\\$\d*'>(.*)<\/span>/Us", $html, $attendance);
// preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_SR_AT_PCT_WRK_N_ATTND_PERC\\\$\d*'>(.*)<\/span>/Us", $html, $barring);*/

$html = '<style type="text/css">.item-body h2{ margin-top: 0px; margin-bottom: 7px; } .card .item{ min-height:0; }</style>';

if(isset($balance[1][0])){
	$html .= <<<html
	<div class="card" style="margin-top:20px;">
	  <div class="item item-text-wrap item-body">
	 
	 		Your current balance is : <br />
	 		<span style="color:#e74c3c;">RM {$balance[1][0]}</span>
	 		<br />
	 		<p>(after adjusting advance payments)</p>

	  </div>
	</div>
html;
}else if(isset($nobalance[1][0])){
	$html .= <<<html
	<div class="card" style="margin-top:20px;">
	  <div class="item item-text-wrap item-body">
	 
	 		<span style="color:#16a085">{$nobalance[1][0]}</span>

	  </div>
	</div>
html;
}else{
	$html .= <<<html
	<div class="card" style="margin-top:20px;">
	  <div class="item item-text-wrap item-body">
	 		<span style="color:#e74c3c">Error. Processing raw data failed. </span><br />
	 		<span style="font-size:13px">Please try again later or contact the developer.</span>
	  </div>
	</div>
html;
}


ok(array('html'=>$html));
?>
 