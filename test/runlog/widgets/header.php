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
    $db_host = getenv('DB_HOST') ?: 'localhost';

    $dsn = "mysql:host={$db_host};dbname=u574399506_testlog;charset=utf8mb4";
    $pdo = new PDO($dsn, "u574399506_testlog", "Sandbox1PA$$", array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ));

    $query_d = "SELECT (SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as daily FROM tl_events WHERE date(run_date) = :today";
    $stmt_d = $pdo->prepare($query_d);
    $stmt_d->execute(array(':today' => date('Y-m-d')));
    $row_d = $stmt_d->fetch(PDO::FETCH_ASSOC);
    if ($row_d['daily'] == "" or $row_d['daily'] == null)
        $row_d['daily'] = 0;

    $query_w = "SELECT (SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as weekly FROM tl_events WHERE date(run_date) >= :start_date AND date(run_date) <= :end_date";
    $stmt_w = $pdo->prepare($query_w);
    $stmt_w->execute(array(':start_date' => $start_date, ':end_date' => $end_date));
    $row_w = $stmt_w->fetch(PDO::FETCH_ASSOC);
    if ($row_w['weekly'] == "" or $row_w['weekly'] == null)
        $row_w['weekly'] = 0;

    $query_bd = "SELECT member_name AS runner_name FROM tl_runners WHERE m_show_profile = true AND DATE_FORMAT(birthday, '%d-%m') = DATE_FORMAT(CURDATE(), '%d-%m')";
    $result_bd = $pdo->query($query_bd);

    $query_r_w = "SELECT (SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as runner_weekly FROM tl_events WHERE date(run_date) >= :start_date AND date(run_date) <= :end_date AND tl_events.runner_id = :runner_id";
    $stmt_r_w = $pdo->prepare($query_r_w);
    $stmt_r_w->execute(array(':start_date' => $start_date, ':end_date' => $end_date, ':runner_id' => $runner_id));
    $row_r_w = $stmt_r_w->fetch(PDO::FETCH_ASSOC);
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
                <td width="30%"><span id="swbdh" style="color:#ffbb99"><b> מזל טוב ליום ההולדת</b></span></td>
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
					while ($row_bd = $result_bd->fetch(PDO::FETCH_NUM)) {
						printf("<span style='color:yellow'><b> %s </b></span> <br>", $row_bd[0]);
						$counter++;
					}
					?>
					<!--<iframe src="https://giphy.com/embed/AwcmOV28QPnck" width="120" height="140" frameBorder="0" class="giphy-embed" ></iframe>
                    --><br><img src="./widgets/bd.gif" width="100" height="80" alt="">
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
                </td>
				
				
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
