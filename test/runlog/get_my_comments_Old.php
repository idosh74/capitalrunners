<?php

require_once 'ajax_page_init.php';

$date = $_GET['date'];
$runnerId = $memberAuthentication->getMemberId();
try {
    $conn = getConnection();
    echo getDayEventsAsJSON($conn,$date);
    $conn = null;
}
catch(PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}

// Fetch all the events of a given date from DB as JSON
function getDayEventsAsJSON($conn,$date) {
	echo " $runnerId \n";
	$runnerId  = '18600112';
    $sql =

"SELECT tl_events.id, tl_runners.member_name AS 'name', tl_run_types.type, run_distance, run_time, warmup_distance, cooldown_distance, COALESCE(tl_events.notes, '') AS 'notes', tl_events.run_type_id
    FROM tl_events
    JOIN tl_runners ON tl_events.runner_id = tl_runners.id
    JOIN tl_run_types ON tl_events.run_type_id = tl_run_types.id
    WHERE  tl_events.runner_id ='" . $runnerId . "'
	and tl_events.run_date >= '" . $date . "'
    ORDER BY tl_events.id DESC";

    $stmt = $conn->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql =

    // show the comments of the trainer
"SELECT events.id AS event_id, comments.id AS comment_id, comments.runner_id, runners.member_name as 'commenter_name', COALESCE(comments.comment, '') AS comment
     FROM tl_events events
     JOIN tl_comments comments ON events.id = comments.event_id
     JOIN tl_runners runners ON comments.runner_id = runners.id
     WHERE events.run_date >= '" . $date . "'  and comments.runner_id IN ('" . $runnerId . "', 18600082)
     ORDER BY events.id DESC, comments.id ASC";

    $stmt = $conn->query($sql);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array(
        'events' => $events,
        'comments' => $comments,
    );

    return returnJSONsuccess($result);
}