<!--[if IE]>
<style>
.tabrow li {
    margin: 0 -1px;
}
.tabrow li:before,
.tabrow li:after {
    border: none;
}
.tabrow:before {
    border-bottom: inherit;
}
</style>
<![endif]-->
<?php
    error_reporting(0);
    $ts = strtotime(date('Y-m-d'));
    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
    $start_date = date('Y-m-d', $start);
    $end_date = date('Y-m-d', strtotime('next saturday', $start)); 
	$runner_id = $memberAuthentication->getMemberId();
	$link = mysqli_connect("localhost", "u574399506_tlog", "LongMayURun20@#", "u574399506_tlog") or die("Could not connect");

//	$link = mysqli_connect("localhost", "u574399506_tlog", "LongMayURun20@#", "u574399506_tlog" ) or die("Could not connect");
	mysqli_set_charset($link, "utf8");
    mysqli_select_db($link, "u574399506_tlog") or die("Could not select database");
 
	$query_d = "SELECT (SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as daily from tl_events where date(run_date) = '" . date('Y-m-d') . "'" ;
	$result_d = mysqli_query($link, $query_d) or die("Query failed");
	$row_d = mysqli_fetch_array($result_d, MYSQLI_ASSOC);
	if ($row_d['daily'] == "" or $row_d['daily'] == null)
		$row_d['daily'] = 0;

    $query_w = "SELECT (SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as weekly from tl_events where date(run_date) >= '" . $start_date . "' and date(run_date) <= '" . $end_date . "'";
	$result_w = mysqli_query($link, $query_w) or die("Query failed");
	$row_w = mysqli_fetch_array($result_w, MYSQLI_ASSOC);
	
	if ($row_w['weekly'] == "" or $row_w['weekly'] == null)
		$row_w['weekly'] = 0;
	
	$query_bd = "SELECT member_name AS runner_name FROM  tl_runners WHERE m_show_profile = true and DATE_FORMAT(birthday, '%d-%m') = DATE_FORMAT(CURDATE(), '%d-%m')";
	$result_bd = mysqli_query($link, $query_bd) or die("Query failed");

	$query_r_w = "SELECT (SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as runner_weekly from tl_events where date(run_date) >= '" . $start_date . "' and date(run_date) <= '" . $end_date . "' and tl_events.runner_id = '" . $runner_id . "'";
	$result_r_w = mysqli_query($link, $query_r_w) or die("Query failed");
	$row_r_w = mysqli_fetch_array($result_r_w, MYSQLI_ASSOC);
	
	if ($row_r_w['runner_weekly'] == "" or $row_r_w['runner_weekly'] == null)
		$row_r_w['runner_weekly'] = 0;

	$admin = $memberAuthentication->isAdmin();
 	$coach = $memberAuthentication->isCoach();
	
?>


    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
	  
      google.load('visualization', '1', {packages:['gauge']});
	  google.charts.load("current", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
		var k_d = <?php echo $row_d['daily']; ?>;
        var k_w = <?php echo $row_w['weekly']; ?>;
		var k_r_w = <?php echo $row_r_w['runner_weekly']; ?>;
        var data_d = google.visualization.arrayToDataTable([
          ['Label', 'Value'],
          ['TODAY', k_d]
        ]);

        var options_d = {
          width: 200, height: 150,
          max: 900,
          redFrom: 800, redTo: 900,
          yellowFrom:600, yellowTo: 800,
          minorTicks: 10
        };

        var data_w = google.visualization.arrayToDataTable([
          ['Label', 'Value'],
		  ['WEEK', k_w]
        ]);

        var options_w = {
          width: 200, height: 150,
          max: 5000,
          redFrom: 4500, redTo: 5000,
          yellowFrom: 4000, yellowTo: 4500,
          minorTicks: 10
        };

		var data_r_w = google.visualization.arrayToDataTable([
			['Label', 'Value'],
		  ['MY WEEK', k_r_w]
		]);

        var options_r_w = {
		  width: 200, height: 150,
          max: 180,
          redFrom: 140, redTo: 180,
          yellowFrom: 100, yellowTo: 140,
          minorTicks: 10
        };

        var chart = new google.visualization.Gauge(document.getElementById('chart_div_d'));
        chart.draw(data_d, options_d);
		var chart = new google.visualization.Gauge(document.getElementById('chart_div_r_w'));
		chart.draw(data_r_w, options_r_w);
		var chart = new google.visualization.Gauge(document.getElementById('chart_div_w'));
		chart.draw(data_w, options_w);
      }
	</script>
	
<div id="header" class="dashboard_metrics">
	<div >
		<table dir="rtl" align="center" border="0">
			  <tr >
			    <td width="30%"><span id="swbdh" style="color:#ffbb99"><b> מזל טוב ליום ההולדת<b></td>
				<td align="center"><b><font color="white">קילומטרז' כללי היום</font></b></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td align="center"><b><font color="white">קילומטרז' אישי שבועי</font></b></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td align="center"><b><font color="white">קילומטרז' כללי שבועי</font></b></td>
				
			  </tr> 
				<td width="30%" align="right">
					
					<div align="right" id='swbd'>
					
					<?php
					$counter = 0;
					while ($row_bd = mysqli_fetch_array($result_bd, MYSQLI_NUM)) {
						printf("<span style='color:yellow'><b> %s </b></span> <br>", $row_bd[0]);
						$counter++;						
					}
					?>
					<!--<iframe src="https://giphy.com/embed/AwcmOV28QPnck" width="120" height="140" frameBorder="0" class="giphy-embed" ></iframe>
					--><br><image src="./widgets/bd.gif" width="100" height="80">
					</div>
				</td>
				<td>
					<div align="center" id='chart_div_d'></div>
				</td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>
					<div align="center" id='chart_div_r_w'></div>
				</td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>
					<div align="center" id='chart_div_w'></div>
				</td></font>
				
				
			  </tr>
		</table>
	</div>
</div>
<div id="header_space"></div>
<div id="header">
	
    <div id="header_graphics" >
        <div id="title">מעקב אימונים</div>
		<div id='content' style="display:none;">
            <a id="logout" href="logout.php">התנתק</a>
            <div id="tabs">
                <ul class="tabrow">
                    <li><a id="runlog" href="runlog.php">יומן ריצה</a></li>
                    <li><a id="fedd" href="feed.php">מה עשו החבר'ה</a></li>
                    <li><a id="shoes" href="shoes.php">הנעליים שלי</a></li>
                    <li><a id="courses" href="courses.php">המסלולים שלי</a></li>
					<li><a id="statistics" href="statistics.php">סטטיסטיקה</a></li>
					<li><a id="results" href="results.php">תוצאות</a></li>
					<li><a id="admin" href="users.php">משתמשים</a></li>
					<?php 
					$runner_id = $memberAuthentication->getMemberId();
					$stravaId = $memberAuthentication->getStravaId();
					if ($stravaId != null){?>
					    <li><a id="admin" href="strava.php">Strava</a></li>
					<?php } ?>
					<?php
					if ($admin) {
					?>
					
					<li><a id="admin" href="races.php">מרוצים</a></li>
					
					<?php } ?>
                </ul>
            </div>
        </div> 
    </div>
</div>
<?php if (!empty($runner_id)) { ?>
<section class="mobile_metrics" aria-label="סיכום קילומטרז׳">
    <div class="mobile_metric">
        <span class="mobile_metric_label">היום</span>
        <strong><?php echo number_format((float)$row_d['daily'], 1); ?></strong>
        <span class="mobile_metric_unit">ק״מ</span>
    </div>
    <div class="mobile_metric mobile_metric_primary">
        <span class="mobile_metric_label">השבוע שלי</span>
        <strong><?php echo number_format((float)$row_r_w['runner_weekly'], 1); ?></strong>
        <span class="mobile_metric_unit">ק״מ</span>
    </div>
    <div class="mobile_metric">
        <span class="mobile_metric_label">השבוע בקבוצה</span>
        <strong><?php echo number_format((float)$row_w['weekly'], 1); ?></strong>
        <span class="mobile_metric_unit">ק״מ</span>
    </div>
</section>
<?php } ?>
<script>
$(document).ready(function () {
	var counter = '<?php echo $counter; ?>';
	if (counter == 0){
	  $('#swbdh').hide();
	  $('#swbd').hide();
	}
});	
$(function(){
    var href = document.location.href;
    var page = href.substr(href.lastIndexOf('/') +1);
	
    $('.tabrow li').each(function()
    {
		  if ($(this).children('a').attr('href') == page)
        {
            $(this).addClass('selected');
            $('#content').show();
        }
    })
});
</script>
