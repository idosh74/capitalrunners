<?php

require_once 'ajax_page_init.php';

$date = $_GET['date'];
$runnerId = $_GET['runnerId'];
$untilDate = $_GET['untilDate'];

try {
    $conn = getConnection();
    echo getDayEventsAsJSON($conn,$date,$untilDate,$runnerId);
    $conn = null;
}
catch(PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}

// Fetch all the events of a given date from DB as JSON
function getDayEventsAsJSON($conn,$date,$untilDate,$runnerId) {
    $sql =

"SELECT tl_events.run_date ,tl_events.id, tl_runners.member_name AS 'name', tl_run_types.type, run_distance, run_time, warmup_distance, cooldown_distance, COALESCE(tl_events.notes, '') AS 'notes', tl_events.run_type_id, pulse, elevation, date_format(tl_events.run_date,'%H:%i') as HourAndMinutes 
FROM tl_events 
JOIN tl_runners ON tl_events.runner_id = tl_runners.id 
JOIN tl_run_types ON tl_events.run_type_id = tl_run_types.id 
WHERE tl_events.runner_id =18600162 
-- and year(tl_events.run_date) = year(CURRENT_DATE()) 
-- and week(tl_events.run_date) = week(CURRENT_DATE()) 
and YEARWEEK(tl_events.run_date, 0) = YEARWEEK(CURDATE(), 0)
ORDER BY tl_events.run_date ASC";

    $stmt = $conn->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

   

    $result = array(
        'events' => $events,
    );

    return returnJSONsuccess($result);
}