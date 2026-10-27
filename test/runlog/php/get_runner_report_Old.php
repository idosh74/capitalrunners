<?php
require_once 'ajax_page_init.php';

if(!isset( $_GET['runner_id'])) {
	die(getErrorStatusWithDummyData("runner id is not set"));
}

$conn = getConnection();

$runner_id = $_GET['runner_id'];
$validationResult = validatePositiveInt($runner_id);
if (!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid runner id: " . $validationResult->getMessage()));
}

try {
    $sql = "SELECT tl_runners.member_name FROM tl_runners WHERE tl_runners.id = '" . $runner_id . "'";
    $stmt = $conn->query($sql);
    $result = $stmt->fetchAll(PDO :: FETCH_ASSOC);
}
catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}


//$data [] = Array ("Name", $result[0]['member_name'], "Weekly sessions");

$sql =

"SELECT notes, run_date, run_type_id, (SELECT tl_run_types.type FROM tl_run_types WHERE tl_run_types.id=tl_events.run_type_id) AS type, run_distance + warmup_distance + cooldown_distance AS sumk, warmup_time + run_time + cooldown_time AS timet, run_time/run_distance AS pacet, pulse AS pulse, max_pulse AS max_pulse, elevation AS elevation, weight AS weight
    FROM tl_events
    WHERE (runner_id = '" . $runner_id . "'
        AND date(run_date) >= '" . $_GET['start_date'] . "'
        AND date(run_date) <= '" . $_GET['end_date'] . "')
    ORDER BY run_date DESC, id DESC";

$sth = $conn->prepare($sql, array(
    PDO :: ATTR_CURSOR => PDO :: CURSOR_FWDONLY
));

$start_date = mktime(0, 0, 0, date("m", strtotime($_GET['start_date'])), date("d", strtotime($_GET['start_date'])), date("Y", strtotime($_GET['start_date'])));
$end_date = mktime(0, 0, 0, date("m", strtotime($_GET['end_date'])), date("d", strtotime($_GET['end_date'])), date("Y", strtotime($_GET['end_date'])));

$time_between = $end_date - $start_date;
//find the days
$day_count = ceil($time_between / 24 / 60 / 60);
$newtime = $start_date;

$ok = $sth->execute();
if (!$ok) {
    die(getErrorStatusWithDummyData("Failed to execute prepared statment"));
} else {
    foreach ($sth->fetchAll(PDO :: FETCH_ASSOC) as $row) {
        //$data[] = array(date('d.m.y', strtotime($row['run_date'])), $row['notes'], $row['type'], floatval($row['sumk']), date("H:i:s", $row['timet']), date("i:s", $row['pacet']), $row['pulse'], $row['max_pulse'], $row['elevation'], floatval($row['weight']) );    
        $data[] = array(date('d.m.y', strtotime($row['run_date'])), $row['notes'], $row['type'], floatval($row['sumk']), date("H:i:s", $row['timet']), date("i:s", $row['pacet']), strval($row['pulse']), strval($row['max_pulse']), strval($row['elevation']), floatval($row['weight']) );    
    }
}

//find the names/dates of the days
for ($i = 0; $i <= $day_count; $i++) {
//	$data[] = array ($result[0]['run_date'], $result[0]['notes']);
    if ($i == 0 && date("l", $newtime) != "Sunday") {
        //we're starting in the middle of a week.... show 1 earlier week than the code that follows
        for ($s = 1; $s <= 6; $s++) {
            $newtime = $start_date - ($s * 60 * 60 * 24);
            if (date("l", $newtime) == "Sunday") {
                $end_of_week = $newtime + (6 * 60 * 60 * 24);
                /*$ok = $sth->execute(array (
                        ':runner_id' => $runner_id,
                        ':week_start' => date("Y-m-d",$newtime),
                        ':week_end' => date("Y-m-d",$end_of_week)
                    ));
                    if (!$ok) {
                        die(getErrorStatusWithDummyData("Failed to execute prepared statment"));
                    } else {
                        $result = $sth->fetchAll(PDO :: FETCH_ASSOC);
                        //$data[] = array (date("Y-m-d",$end_of_week), intval($result[0]['weekly']), intval($result[0]['a']));
                        //
                    }*/
                //echo ." through ".date("Y-m-d",$end_of_week)." is a week.<br />";
            }
        }
    } else {
        $newtime = $start_date + ($i * 60 * 60 * 24);
        if (date("l", $newtime) == "Sunday") {
//Beginning of a week... show it
            $end_of_week = $newtime + (6 * 60 * 60 * 24);
            /*$ok = $sth->execute(array (
                       ':runner_id' => $runner_id,
                       ':week_start' => date("Y-m-d",$newtime),
                       ':week_end' => date("Y-m-d",$end_of_week)
               ));
               if (!$ok) {
                   die(getErrorStatusWithDummyData("Failed to execute prepared statment"));
               } else {
                   $result = $sth->fetchAll(PDO :: FETCH_ASSOC);
                   //$data[] = array (date("Y-m-d",$end_of_week), intval($result[0]['weekly']), intval($result[0]['a']));

               }*/
            //echo date("Y-m-d",$newtime)." through ".date("Y-m-d",$end_of_week)." is a week.<br />";
        }
    }
}
$conn = null;
if (empty($data)) {
    $data = '';
}
echo returnJSONsuccess($data);