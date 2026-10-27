<?php

function getEventComment() {
    $eventCommentStr = isset($_POST['event_comment']) ? $_POST['event_comment'] : (isset($_GET['event_comment']) ? $_GET['event_comment'] : null);
    if ($eventCommentStr === null) {
        die(getErrorStatusWithDummyData("Mandatory input - event_comment was not found."));
    }

    $eventComment = json_decode($eventCommentStr);

    $json_decode_error = json_last_error();
    if ($json_decode_error != JSON_ERROR_NONE) {
        die(getErrorStatusWithDummyData("Failed to decode JSON - " . getJSONDEcodeErrDesc($json_decode_error)));
    }

    $commentText = is_object($eventComment) ? (isset($eventComment->comment) ? $eventComment->comment : '') : $eventComment;
    if (is_string($commentText) && strlen($commentText) > MAX_TEXT_BYTES) {
        die(getErrorStatusWithDummyData("הטקסט ארוך מדי"));
    }

    return $eventComment;
}