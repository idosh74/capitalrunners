<?php

require_once 'config.php';
require_once 'constants.php';
require_once 'DBLogger.php';
require_once 'ValidationResult.php';


/**
 * Print error msg to DB and return a JSON with error msg and dummy data
 * $msg - a free text describing the error
 * $backtrace - array that is returned from the call to the function 'debug_backtrace()'. no need to send when used in top level code.
 * See: http://php.net/manual/en/function.debug-backtrace.php
 * Use this method when the client expects the JSON to have 2 fields: 'status' and 'data
 */
function logAndDie($msg, $backtrace) {
    if (empty($backtrace))
    {
        $backtrace = getTopLevelBacktrace(debug_backtrace());
    }
    DBLogger :: log(LOGGER_ERR, $backtrace, $msg);
    die(getErrorStatusWithDummyData($msg));
}

/**
 * Populate an array with error status and serialize it into JSON string
 * $errMsg - a free text describing the error
 * $backtrace - array that is returned from the call to the function 'debug_backtrace()'. no need to send when used in top level code.
 */
function getErrorStatus($errMsg, $backtrace=array()) {
    if (empty($backtrace))
    {
        $backtrace = getTopLevelBacktrace(debug_backtrace());
    }
    DBLogger :: log(LOGGER_ERR, $backtrace, $errMsg);

    $response = array (
        "status" => array (
            "ecode" => STATUS_ERR,
            "emessage" => $errMsg
        ));

    return json_encode($response);
}

/**
 * Populate an array with error status and serialize it into JSON string
 * $errMsg - a free text describing the error
 * $backtrace - array that is returned from the call to the function 'debug_backtrace()'. no need to send when used in top level code.
 */
function getErrorStatusWithDummyData($errMsg, $backtrace=array()) {
    if (empty($backtrace))
    {
        $backtrace = getTopLevelBacktrace(debug_backtrace());
    }
    DBLogger :: log(LOGGER_ERR, $backtrace, $errMsg);

    $response = array (
        "status" => array (
            "ecode" => STATUS_ERR,
            "emessage" => $errMsg
        ),
        "data" => ""
    );
    return json_encode($response);
}

/**
 * Writes a debug message to the log with the query string or post parameters
 */
function debugLogRequest($backtrace=array())
{
    if (DBLogger::isDebugEnabled())
    {
        if (empty($backtrace))
        {
            $backtrace = getTopLevelBacktrace(debug_backtrace());
        }
        $msg = '';

        DBLogger::log(LOGGER_DBG, $backtrace, $msg);
    }
}

function getRequestParameters()
{
    $requestParams = array_merge($_GET, $_POST);
    $requestArray = array();

    foreach ($requestParams as $key => $value)
    {
        $requestArray[] = "$key => $value";
    }

    $request = implode('; ', $requestArray);

    return $request;
}


/**
 * When logging issues, sometimes the top level code is doing the logging. In this case,
 * 'debug_backtrace()' returns an empty array so, we create a pseudo backtrace from within
 * the loggin function that would give the file name and line number of the top level call
 */
function getTopLevelBacktrace($backtrace)
{
    $topLevelBacktrace = array(
        '0' => array(
            'file' => $backtrace[0]['file'],
            'line' => $backtrace[0]['line'],
            'function' => ''
        )
    );

    return $topLevelBacktrace;
}

/**
 * Get a DB connection
 */
function getConnection() {
    global $HOSTNAME, $DBNAME, $USERNAME, $PASSWORD;
    $conn = new PDO("mysql:host=$HOSTNAME;dbname=$DBNAME;charset=utf8mb4", "$USERNAME", "$PASSWORD");
    $conn->setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
    return $conn;
}

/**
 * Get a JSON string that contains 2 elements:
 * 1) status - OK status object
 * 2) data - the actual data returned by the script
 */
function returnJSONsuccess($result) {
    $response = array (
        "status" => (array (
            "ecode" => STATUS_OK,
            "emessage" => "Operation successful."
        )),
        "data" => $result
    );

    if (DBLogger::isDebugEnabled()) {

        if (empty($result)){
            $result = "Operation successful.";
        } else if (is_array($result)){
            $result = print_r($result, true);
        }

        DBLogger::log(LOGGER_DBG, debug_backtrace(), $result);
    }

    return json_encode($response);
}



/**
 * Validate that the input is an int > 0
 */
function validatePositiveInt($val) {
    // is it null
    if (!isset ($val)) {
        return new ValidationResult(false, "input is null and not a valid integer.");
    }
    // is it int
    if (!intval($val)) {
        return new ValidationResult(false, $val . " is not a valid integer.");
    }
    // is it positive
    $tmp = intval($val);
    if ($tmp < 1) {
        return new ValidationResult(false, $val . " is not a positive integer.");
    } else {
        return new ValidationResult(true, "no problem");
    }
}

/**
 * Validate decimal nymbers
 */
function validateDecimalNumbers($val) {

    // is the number valid according to the following regular expression
    $regex = "/^([+-]{1})?[0-9]+(\.[0-9]+)?$/";

    if (!preg_match($regex, $val)) {
        return new ValidationResult(false, $val . " is invalid decimal number.");
    } else {
        return new ValidationResult(true, "no problem");
    }

}

/*
 * Validate the $val is a real in the range $range
 */
function validateRealInRange($val,$range) {
    // is it null
    if (!isset ($val)) {
        return new ValidationResult(false, "input is null and not a valid number.");
    }
    // is it a real number
    if (!is_numeric($val)) {
        return new ValidationResult(false, $val . " is not a valid number.");
    }
    return _validateNumberInRange($val,$range);
}

/*
 * Validate the $val is an integer in the range $range
 */
function validateIntInRange($val,$range) {
    // is it null
    if (!isset ($val)) {
        return new ValidationResult(false, "input is null and not a valid integer.");
    }
    return _validateNumberInRange((int)$val,$range);
}

function _validateNumberInRange($val,$range) {

    if($val < $range->getMin()) {
        return new ValidationResult(false, $val . " is smaller than " . $range->getMin() );
    }
    if($val > $range->getMax()) {
        return new ValidationResult(false, $val . " is bigger than " . $range->getMax() );
    }
    return new ValidationResult(true, "no problem");

}
/**
 * Get json_decode error description
 */
function getJSONDEcodeErrDesc($errCode) {
    switch ($errCode) {
           case JSON_ERROR_NONE:
               return ' - No errors';
           break;
           case JSON_ERROR_DEPTH:
               return ' - Maximum stack depth exceeded';
           break;
           case JSON_ERROR_STATE_MISMATCH:
               return' - Underflow or the modes mismatch';
           break;
           case JSON_ERROR_CTRL_CHAR:
               return ' - Unexpected control character found';
           break;
           case JSON_ERROR_SYNTAX:
               return' - Syntax error, malformed JSON';
           break;
           case JSON_ERROR_UTF8:
               return' - Malformed UTF-8 characters, possibly incorrectly encoded';
           break;
           default:
               return ' - Unknown error';
    }
}