<?php

require_once 'ajax_page_init.php';
$runnerId = $memberAuthentication->getMemberId();
// an invalid/missing timestamp must not turn into "all comments ever"
$timestamp = date("Y-m-d H:i:s", strtotime('-2 day'));
if (isset($_GET['timestamp']) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['timestamp'])){
    $timestamp = $_GET['timestamp'];
}

try {
    $conn = getConnection();

    $sql =

"SELECT DISTINCT tl_events.id, tl_events.run_date, tl_runners.member_name AS 'name', tl_run_types.type, run_distance, run_time, warmup_distance, cooldown_distance, COALESCE(tl_events.notes, '') AS 'notes', tl_events.run_type_id
    FROM tl_runners, tl_events, tl_run_types, tl_comments
    WHERE tl_runners.id = tl_events.runner_id
        AND tl_events.run_type_id = tl_run_types.id
        AND tl_comments.runner_id != '" . $runnerId . "'
        AND tl_comments.timestamp >= '" . $timestamp . "'
        AND tl_events.id = tl_comments.event_id
    ORDER BY date(tl_events.run_date) DESC, tl_events.id DESC
    LIMIT 100";

    $stmt = $conn->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventIds = array();
    foreach ($events as $event){
        $eventIds[] = $event['id'];
    }

    if (count($eventIds) == 0) {
        $conn = null;
        echo returnJSONsuccess(array('events' => array(), 'comments' => array()));
        exit;
    }

    $sql =

"SELECT events.id AS event_id, comments.id AS comment_id, comments.runner_id, runners.member_name as 'commenter_name', runners.m_is_coach AS is_coach, COALESCE(comments.comment, '') AS comment, comments.timestamp AS timestamp
     FROM tl_events events
        JOIN tl_comments comments ON events.id=comments.event_id
        JOIN tl_runners runners ON comments.runner_id = runners.id
     WHERE events.id IN (".implode(',', array_map('intval', $eventIds)).")
     ORDER BY events.run_date DESC, events.id DESC, comments.id ASC";

    $stmt = $conn->query($sql);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn = null;

    $result = array(
        'events' => $events,
        'comments' => $comments,
    );

    echo returnJSONsuccess($result);
}
catch(PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}