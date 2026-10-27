<?php

/*
 * Common validation module for event create and update common fields
 * The following fields are validated: warmup_distance,run_distance,cooldown_distance,warmup_time,cooldown_time,run_time
 * The following fields are not going to be validated: run_type_id,shoe_id,course_id [validation will be provided by DB constraints]
 */

require_once 'utils.php';
require_once 'ValidationResult.php';
require_once 'NumberRange.php';
require_once 'constants.php';

/**
 *  $eventFields - event fields
 */
function validateEventFields($eventFields) {

    //
    // Distance
    //
    $distances = array (
        "run_distance"=> new NumberRange(MIN_RUN_DISTANCE, MAX_RUN_DISTANCE),
        "extra_run_distance" => new NumberRange(MIN_EXTRA_DISTANCE, MAX_EXTRA_DISTANCE)
    );
    $keys = array_keys($distances);
    foreach ($keys as $key) {
        $validationResult = validateRealInRange($eventFields->{$key},$distances[$key]);
        if (!$validationResult->isValid()) {
            return $validationResult;
        }
    }
    //
    // Time
    //
    $times = array (
        "run_time"=> new NumberRange(MIN_RUN_TIME, MAX_RUN_TIME),
    );

    $keys = array_keys($times);
    foreach ($keys as $key) {
        $validationResult = validateIntInRange($eventFields->{$key}, $times[$key]);
        if (!$validationResult->isValid()) {
            return $validationResult;
        }
    }

    return new ValidationResult(true, "no problem");

}

function getEventFields() {
    //
    // Make sure we have the input and it is a valid JSON
    //
    if (!isset ($_GET['event_fields'])) {
        die(getErrorStatusWithDummyData("Mandatory input - event_fields was not found."));
    }
    $eventFieldsStr = $_GET['event_fields'];
    $eventFields = json_decode($eventFieldsStr);
    $json_decode_error = json_last_error();
    if ($json_decode_error != JSON_ERROR_NONE) {
        die(getErrorStatusWithDummyData("Failed to decode JSON - " . getJSONDEcodeErrDesc($json_decode_error)));
    }
    return $eventFields;

}
/**
 * Get the user shoes
 */
function getUserShoes($conn,$member_id) {
    $sql = "SELECT shoe_name AS label, id AS value, CONCAT(EXTRACT(MONTH FROM start_using_date), '.', EXTRACT(YEAR FROM start_using_date)) AS title FROM  tl_shoes  WHERE  runner_id = '" . $member_id . "' and active = true";
    $stmt = $conn->query($sql);
    $resultShoes = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    return $resultShoes;
}
/**
 * Get the user courses
 */
function getUserCourses($conn,$member_id) {
    $sql = "SELECT course_name as label, id as value , description as title, length from tl_courses where runner_id = '" . $member_id . "'";
    $stmt = $conn->query($sql);
    $resultCourses = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    return $resultCourses;
}

/**
 * Get latest races
 */
function getLastRaces($conn) {
    $sql = "SELECT concat(tl_races.race_name,' - ',tl_race_type.type) as label, tl_races.id as value  FROM `tl_races` 
    left outer join tl_race_type on tl_races.type_id=tl_race_type.id
    where tl_races.race_date > DATE_SUB(CURDATE(),INTERVAL 365 DAY) 
    and tl_races.race_date <= DATE_SUB(CURDATE(),INTERVAL -1 DAY)
    order by tl_races.race_date desc";
    $stmt = $conn->query($sql);
    $resultRaces = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    return $resultRaces;
}