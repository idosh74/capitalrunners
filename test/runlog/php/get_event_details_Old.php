<?php

/*
 * Created on Apr 4, 2012
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once 'ajax_page_init.php';
require_once 'event_common.php';

$member_id = $_GET['member_id'];
$validationResult = validatePositiveInt($member_id);
// we have invalid member_id - lets quit
if (!$validationResult->isValid()) {
    logAndDie("Invalid member id: " . $validationResult->getMessage(),debug_backtrace());
}

$isUserLoggedIn = scriptCallerIsLoggedIn($member_id);

if (!$isUserLoggedIn) {
    //TODO - move the user to the login page
}

$event_id = $_GET['event_id'];
$validationResult = validatePositiveInt($event_id);
if (!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid event id: " . $validationResult->getMessage()));
}

// OK - now we have a valid input - lets try to remove the event from DB
try {
    $conn = getConnection();
    $eventDetails = getEvnetVariousDetails($conn,$event_id);
    $resultShoeSelected = getShoeSelected($conn, $eventDetails[0]['shoe_id']);
    if ($eventDetails[0]['extra_distance'] > 0 && $eventDetails[0]['extra_shoe_id'] == null) {
        $eventDetails[0]['extra_shoe_id'] = $eventDetails[0]['shoe_id'];
    }
    $resultExtraShoeSelected = getShoeSelected($conn, $eventDetails[0]['extra_shoe_id']);
    $resultCourseSelected = getCourseSelected($conn, $eventDetails[0]['course_id']);
    $resultRunTypeSelected = getRunTypeSelected($conn, $eventDetails[0]['run_type_id']);
    echo returnEventDataJSONsuccess($eventDetails, $resultShoeSelected, $resultExtraShoeSelected, $resultRunTypeSelected, $resultCourseSelected);
    $conn = null;
} catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}
/**
 * Get Run Type selected value
 */
function getRunTypeSelected($conn, $run_type_selected) {
    $sql = "SELECT id,type as name from tl_run_types where id = '" . $run_type_selected . "'";
    $stmt = $conn->query($sql);
    $resultRunTypeSelected = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    if ($resultRunTypeSelected[0] == null ){
        $resultRunTypeSelected[0]=0;
    }
    return $resultRunTypeSelected;
}

/**
 * Get shoes selected value
 */
function getCourseSelected($conn, $course_selected) {
    $sql = "SELECT id,course_name from tl_courses where id = '" . $course_selected . "'";
    $stmt = $conn->query($sql);
    $resultCourseSelected = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    if ($resultCourseSelected[0] == null ){
        $resultCoursSelected[0]=0;
    }
    return $resultCourseSelected;
}

/**
 * Get the event details (distance,time,etc)
 */
function getEvnetVariousDetails($conn,$event_id) {
    $sql = "SELECT *, (warmup_distance + cooldown_distance) as extra_distance from tl_events where id = '" . $event_id . "'";
    $stmt = $conn->query($sql);
    $result = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    return $result;

}

/**
 * Get shoes selected value
 */
function getShoeSelected($conn, $shoe_selected) {
    $sql = "SELECT id,CONCAT(shoe_name, ' - ', EXTRACT(MONTH FROM start_using_date), '.', EXTRACT(YEAR FROM start_using_date)) as name from tl_shoes where id = '" . $shoe_selected . "'";
    $stmt = $conn->query($sql);
    $resultShoeSelected = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    if ($resultShoeSelected[0] == null ){
        $resultShoeSelected[0]=0;
    }

    return $resultShoeSelected;
}

function returnEventDataJSONsuccess($result, $resultShoeSelected, $resultExtraShoeSelected, $resultRunTypeSelected, $resultCourseSelected) {
    $result = array (
        "event_fields" => $result[0],
        "selected_shoe" => $resultShoeSelected[0],
        "selected_extra_shoe" => $resultExtraShoeSelected[0],
        "selected_run_type" => $resultRunTypeSelected[0],
        "selected_course" => $resultCourseSelected[0]
    );

    return returnJSONsuccess($result);
}

//TODO: check if the user with $member_id is logged in
function scriptCallerIsLoggedIn($member_id) {
    return true;
}