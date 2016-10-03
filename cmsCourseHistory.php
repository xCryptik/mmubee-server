<?php
include 'config.php';
checkActive();

//https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL
$arr = loginCms($sid, $cpw);


$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.SSS_MY_CRSEHIST.GBL?Page=SSS_MY_CRSEHIST&Action=U&ForceSearch=Y&EMPLID='.$sid.'&TargetFrameName=None', $cookie);

//echo $html;
//get item sum.

// preg_match_all("/<span class='PSGRIDCOUNTER' >.*of (.*)<\/span>/Us", $html, $content);
// $sum = $content[1][0];

 

//get subject code
//<span  class='PSEDITBOX_DISPONLY' id='CRSE_NAME$1'>L EI0027</span>
$ok = preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='CRSE_NAME\\\$\d*'>(.*)<\/span>/Us", $html, $code);

if(!$ok){
	preg_match_all("/<DIV id='win0div\\\$ICField\\\$87\\\$'>(.*)<\/DIV>/Us", $html, $error);

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

preg_match_all("/<a name='CRSE_LINK\\\$\d*' id='CRSE_LINK\\\$\d*' ptlinktgt='pt_peoplecode' tabindex='\d*' href=\"javascript:submitAction_win0\(document\.win0,'CRSE_LINK\\\$\d*'\);\"  class='PSHYPERLINK' >(.*)<\/a>/Us", $html, $name);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='CRSE_TERM\\\$\d*'>(.*)<\/span>/Us", $html, $term);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='N_MNG_EXAMS_WRK_DESCR\\\$\d*'>(.*)<\/span>/Us", $html, $grade);
preg_match_all("/<span  class='PSEDITBOX_DISPONLY' id='CRSE_UNITS\\\$\d*'>(.*)<\/span>/Us", $html, $units);

$html = '<style type="text/css">.item-body h2{ margin-top: 0px; margin-bottom: 7px; } .card .item{ min-height:0; }</style>';
foreach ($code[1] as $key => $value) {
  $html.="
    <li class='item'>
    <h2>".$name[1][$key]."</h2><br />
    <p style='margin-top:-25px; color:rgb(52, 152, 219);'>
      <span style='color:#4a6b82;'>".$value." - ".$term[1][$key]."</span><br />
      Grade&nbsp;&nbsp;&nbsp;".$grade[1][$key]." <br />
      Units&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$units[1][$key]."
    </p></li>
    ";
}
$html.='<br /><br />';
//print_r($subjectCode);
ok(array('html'=>$html));
?>
 