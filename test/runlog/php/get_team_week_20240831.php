<?php 
require_once 'ajax_page_init.php';

$string_date = $_GET['weekly_date'];
if ($string_date == 0 or !isset($string_date)){
	$string_date=date('Y-m-d');
}
$conn = getConnection();

$day_of_week = date('N', strtotime($string_date));
$newDate = mktime(0,0,0,date("m", strtotime($string_date)),date("d", strtotime($string_date)),date("Y", strtotime($string_date)));
if (date("l",$newDate) != "Sunday"){
	$week_first_day = date('Y-m-d', strtotime($string_date . " - " . ($day_of_week) . " days"));
	$week_last_day = date('Y-m-d', strtotime($string_date . " + " . (6 - $day_of_week) . " days"));
} else {
	$week_first_day = $string_date;
	$week_last_day = date('Y-m-d', strtotime($string_date) + (6*60*60*24));
}
//echo $week_first_day . " " . $week_last_day;
$title=date("d-m-Y", strtotime($week_first_day)). " - " . date("d-m-Y", strtotime($week_last_day));

try {
	$sql = "SELECT distinct tl_events.runner_id, tl_runners.member_name as name FROM tl_events,tl_runners WHERE tl_runners.id=tl_events.runner_id AND date(tl_events.run_date) >= '" . $week_first_day . "' AND date(tl_events.run_date) <= '" . $week_last_day . "' order by tl_runners.member_name";
	$stmt = $conn->query($sql);
	$result = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    if (count($result) == 0){
		$data [] = "";
	}else{
		$data [] = Array ("Name", $title);
	}
	foreach ($result as $row)
	{
	   $sql = "SELECT ( SUM(run_distance) + SUM(warmup_distance) + SUM(cooldown_distance)) as weekly FROM tl_events,tl_runners WHERE (tl_runners.id = '" . $row["runner_id"] . "' AND tl_events.runner_id = '" . $row["runner_id"] . "' AND date(run_date) >= '" . $week_first_day . "' AND date(run_date) <= '" . $week_last_day ."')";	
	   $stmt = $conn->query($sql);
	   $result1 = $stmt->fetchAll(PDO :: FETCH_ASSOC);
	   foreach ($result1 as $row1)
	   {	
			$data[] = array ($row["name"], intval($row1['weekly']));
	   }
	}
}
catch(PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}
$conn = null;
echo returnJSONsuccess($data);

?>