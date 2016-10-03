<?php
include 'config.php';
$html = <<<html
<div class="card" style="margin-top:0px;">
  <div class="item item-text-wrap item-body">
 
 			<div class="logo" style="margin-top:0px;"></div>
 				Hello world!
 	</div>
</div>
html;
echo json_encode(array('html'=>$html));
?>