<?php

require_once 'ajax_page_init.php';

$start_date = $_GET['start_date'];
$end_date = date('Y-m-d', strtotime('+6 day', strtotime($start_date)));

try {
    $conn = getConnection();
    echo getTeamEventsAsJSON($conn,$start_date,$end_date);
    $conn = null;
}
catch(PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}

// Fetch the events info from DB as JSON
function getTeamEventsAsJSON($conn, $start_date, $end_date) {
    $sql =

"SELECT tl_events.id AS id, tl_run_types.type, run_distance, run_time, warmup_distance, cooldown_distance, COALESCE(notes, '') AS notes, run_date AS start, tl_run_types.id AS run_type_id, runner_id, tl_runners.member_name AS runner_name
    FROM tl_events, tl_run_types, tl_runners
    WHERE tl_run_types.id = tl_events.run_type_id
        AND date(run_date) >= '".$start_date."'
        AND date(run_date) <= '".$end_date."'
        AND runner_id = tl_runners.id
    ORDER BY runner_name, run_date";

    $stmt = $conn->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return returnJSONsuccess($result);
}