<?php
include 'config.php';
checkActive();

//https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL
$arr = loginCms($sid, $cpw);


$cookie =  implode(';', $arr['cookie']);
$html = get('https://cms.mmu.edu.my/psc/csprd/EMPLOYEE/HRMS/c/N_SELF_SERVICE.N_SM_EXAMSLIP_PNL.GBL', $cookie);

/*
$html = <<<html
html;*/
/*
preg_match_all("/<!-- Begin HTML Area Name Undisclosed -->(.*)<!-- End HTML Area -->/Us", $html, $content);

if(!isset($content[1][0])){
	err('preg', 'Processing raw data failed', true, true);
}
*/
//echo $html;
$ok = preg_match_all("/<td class=\"table-cell-1\">(.*)<\/td>/Us", $html, $code);
preg_match_all("/<td class=\"table-cell-2\">(.*)<\/td>/Us", $html, $name);
preg_match_all("/<td class=\"table-cell-3\">(.*)<\/td>/Us", $html, $hour);
preg_match_all("/<td class=\"table-cell-4\">(.*)<\/td>/Us", $html, $date);
preg_match_all("/<td class=\"table-cell-5\">(.*)<\/td>/Us", $html, $station);
preg_match_all("/<td class=\"table-cell-6\">(.*)<\/td>/Us", $html, $seat);

if(!$ok){
	$html = <<<html
	<div class="card" style="margin-top:20px;">
	  <div class="item item-text-wrap item-body">
	 
		You cannot view your exam slip due to financial barring

	  </div>
	</div>
html;
ok(array('html'=>$html));
exit();
}


$html = '<style type="text/css">.item-body h2{ margin-top: 0px; margin-bottom: 7px; } .card .item{ min-height:0; }</style>';

foreach ($code[1] as $key => $value) {
	$html .= <<<html
	<div class="list card">
	  <div class="item item-body">
	    <h2>{$value} - {$name[1][$key]}</h2>
	    <p><span style="color:#4a6b82;">{$date[1][$key]}</span> at <span style="color:#4a6b82">{$station[1][$key]}</span></p>
	    <p>
	      <span class="subdued">Seat {$seat[1][$key]}</span> &nbsp;&middot;&nbsp;
	      <span class="subdued">Hour {$hour[1][$key]}</span> 
	    </p>
	  </div>

	</div>
html;
}
$html.='<br /><br />';
ok(array('html'=>$html));
?>