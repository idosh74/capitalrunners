<?php

require_once 'ajax_page_init.php';

$member_id = $_GET['member_id'];
$validationResult = validatePositiveInt($member_id);
// we have invalid member_id - lets quit
if(!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid member id: " .$validationResult->getMessage()));
}

$start = $_GET['start'];
$validationResult = validatePositiveInt($start);
if(!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid start date: " .$validationResult->getMessage()));
}
$start_date = date('Y-m-d',$start);


$end = $_GET['end'];
$validationResult = validatePositiveInt($end);
if(!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid end date: " .$validationResult->getMessage()));
}
$end_date = date('Y-m-d', $end);

try {
    $conn = getConnection();
    echo getEventsAsJSON($conn,$member_id,$start_date,$end_date);
    $conn = null;
}
catch(PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}

// Fetch the events info from DB as JSON
function getEventsAsJSON($conn,$member_id, $start_date, $end_date) {
    $sql =

"SELECT tl_events.id as id, tl_run_types.type, run_distance, run_time, warmup_distance, cooldown_distance, COALESCE(notes, '') AS notes, run_date AS start, runner_id, tl_run_types.id as run_type_id
    FROM tl_events, tl_run_types
    WHERE tl_run_types.id = tl_events.run_type_id
        AND runner_id = '" . $member_id . "'
        AND date(run_date) >= '" . $start_date . "'
        AND date(run_date) <= '" . $end_date . "'
    ORDER BY run_date";

    $stmt = $conn->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return returnJSONsuccess($result);
}