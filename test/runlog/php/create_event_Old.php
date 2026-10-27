<?php

// -----------------------------------------------------------------------------------------
// This script is responsible for creating a new event for a user.
// It expects 2 input parameters:
// 1)  The member ID - in order to see if the caller is logged in
// 2)  The Date - for setting the event's date
// 3)  The event type ID
// 4)  The warmup time
// 5)  The main excersize time
// 6)  The cooldown time
// 7)  The warmup distance
// 8)  The main excersize distance
// 9)  The cooldown distance
// 10) The event description
// 12) The shoe ID
// 13) The course ID
// The output of the script is a JSON with 2 fields:
// 1) ecode [ERR || OK]
// 2) emessage - free text
// NOTE: since the client is expecting a JSON result - we should always return a valid JSON
// -----------------------------------------------------------------------------------------

require_once 'ajax_page_init.php';
require_once 'ValidationResult.php';
require_once 'event_common.php';

$eventFields = getEventFields();

//
// Validate common (common for Create/Update event) fields
//
$validationResult = validateEventFields($eventFields);
if (!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid field value: " . $validationResult->getMessage()));
}

$validationResult = validatePositiveInt($eventFields-> {"date"});

if (!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid date: " . $validationResult->getMessage()));
}
$the_date = $eventFields-> {"date"};


// OK - now we have a valid input - lets try to create the event from DB
try {
    $conn = getConnection();
    $sql = 'INSERT INTO tl_events (run_date,warmup_time,run_time,cooldown_time,warmup_distance,run_distance,cooldown_distance,notes,runner_id,shoe_id,extra_shoe_id,course_id,run_type_id,pulse,max_pulse,elevation,weight) VALUES (:run_date,:warmup_time,:run_time,:cooldown_time,:warmup_distance,:run_distance,:cooldown_distance,:notes,:runner_id,:shoe_id,:extra_shoe_id,:course_id,:run_type_id,:pulse,:max_pulse,:elevation,:weight)';
    $sth = $conn->prepare($sql);

    $ok = $sth->execute(array (
        ':run_date' => $the_date,
        ':warmup_time' => 0,
        ':run_time' => $eventFields->{"run_time"},
        ':cooldown_time' => 0,
        ':warmup_distance' => $eventFields->{"extra_run_distance"},
        ':run_distance' => $eventFields->{"run_distance"},
        ':cooldown_distance' => 0,
        ':notes' => $eventFields->{"notes"},
        ':runner_id' => $eventFields->{"runner_id"},
        ':shoe_id' => $eventFields->{"shoe_id"},
        ':extra_shoe_id' => $eventFields->{"extra_shoe_id"},
        ':course_id' => $eventFields->{"course_id"},
        ':run_type_id' => $eventFields->{"run_type_id"},
        ':pulse' => $eventFields->{"pulse"},
		':max_pulse' => $eventFields->{"max_pulse"},
		':elevation' => $eventFields->{"elevation"},
		':weight' => $eventFields->{"weight"},
    ));

    if (!$ok) {
        die(getErrorStatusWithDummyData("Failed to Create Event."));
    } else {
        echo returnJSONsuccess("");
    }
    $conn = null;
} catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}