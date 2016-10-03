<?php
include 'config.php';
$result = get("https://www.mmu.edu.my/index.php?req=152", '');

preg_match_all('/<div id="acad_year">(.*)<\/div>/Us', $result, $content);

$result = $content[0][0];

//remove font tag
$result = preg_replace('/<font[^>]*>|<\/font>/i', '', $result);

//remove bycolor
$result = preg_replace( "/ bgcolor=\".*\"| bgcolor='.*'/Usi", "", $result);
$newHtml = '<div class="calendarCenter">'.$result.'</div>';
//echo $newHtml;

$newHtml = str_replace("Degree, Diploma and Foundation", "Week", $newHtml);

$newHtml = str_replace('<th class="col1">Date Range</th>', '<th class="col1">Range</th>', $newHtml);

//remove week
$newHtml = preg_replace("/(<td rowspan=\"2\"\>|<td>)\d{1,2}<\/td>/Usi", '', $newHtml);
$newHtml = str_replace('<th class="col2">Week</th>', '', $newHtml);
$newHtml = str_replace(' - ', '<br />', $newHtml);
/*
function addBr($match){
	print_r($match[0]);
	return "<td>16 June - 22 June</td>";
}

$newHtml = preg_replace_callback("/(<td rowspan=\"2\"\>|<td>)\d{1,2} \w.* - \d{1,2} \w.*<\/td>/Usi", "addBr", $newHtml);
*/
$newHtml.='<br /><br />';
ok(array('html'=>$newHtml));
?>