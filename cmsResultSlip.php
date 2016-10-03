<?php
include 'config.php';

$html = '<style type="text/css">.item-body h2{ margin-top: 0px; margin-bottom: 7px; } .card .item{ min-height:0; }</style>';

$html .= <<<html
<div class="card" style="margin-top:20px;">
  <div class="item item-text-wrap item-body">
	Your results will be released after the final exam :)
  </div>
</div>
html;

ok(array('html'=>$html));
?>