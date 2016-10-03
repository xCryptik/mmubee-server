<?php
include 'config.php';
$html = '<style type="text/css">.item-body h2{ margin-top: 0px; margin-bottom: 7px; } .card .item{ min-height:0; }
	table{
			margin:0 auto;
			text-align:center;
		    width: 200px;
		    border-collapse: collapse;
		    text-align: left;
		}
		th{
		    font-size: 14px;
		    font-weight: normal;
		    padding: 10px 8px;
		    border:1px solid #d0dbfd;
		    border-bottom: 2px solid #6678b1;
		}
		td{
		    border-bottom: 1px solid #EFF3FD;
		    padding: 13px 13px;
		}


</style>';
$html .= <<<html
<div class="card" style="margin-top:20px;">
  <div class="item item-text-wrap item-body">
 
 		<div class="logo" style="margin-top:0px;"></div>

		<table>
		  <tr>
		    <td width="80">I'm</td>
		    <td>MMUbee</td>
		  </tr>

		  <tr>
		    <td>Age</td>
		    <td>2.2.0</td>
		  </tr>


		  <tr>
		    <td>Platform</td>
		    <td>iOS, Android</td>
		  </tr>

		  <tr>
		    <td>Device</td>
		    <td>Phone, Pad</td>
		  </tr>


		  <tr>
		    <td>Father</td>
		    <td>Zhang Hang</td>
		  </tr>


		  <tr>
		    <td>Mother</td>
		    <td>Ma Guojiao</td>
		  </tr>
		  
		  <tr>
		    <td>Uncle</td>
		    <td>Zhang Qi</td>
		  </tr>

		  <tr>
		    <td>Contact</td>
		    <td><a href="mailto:mmubee@zh.my">mmubee@zh.my</a></td>
		  </tr>

		  <tr>
		    <td>Facebook</td>
		    <td><a href="https://www.facebook.com/zh.MMUbee">MMUbee</a></td>
		  </tr>

		  <tr>
		    <td>Web</td>
		    <td><a href="http://zh.my/">zh.my</a></td>
		  </tr>

		</table>
		<br /><br />
  </div>
</div>
<br /><br />
html;
echo json_encode(array('html'=>$html));
?>