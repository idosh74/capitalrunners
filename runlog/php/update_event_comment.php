<?php

require_once 'ajax_page_init.php';
require_once 'comment_common.php';

$eventCommentId = isset($_POST['event_comment_id']) ? $_POST['event_comment_id'] : (isset($_GET['event_comment_id']) ? $_GET['event_comment_id'] : null);
$validationResult = validatePositiveInt($eventCommentId);
if(!$validationResult->isValid()) {
    die(getErrorStatus("Invalid event comment id: " .$validationResult->getMessage()));
}

$runner_id = $memberAuthentication->getMemberId();
$eventComment = getEventComment();

try {
    $conn = getConnection();
    $sql = 'UPDATE tl_comments SET comment=:comment WHERE id=:comment_id AND runner_id=:runner_id';
    $sth = $conn->prepare($sql);
    $ok = $sth->execute(array(
        ':comment' => $eventComment,
        ':comment_id' => $eventCommentId,
        ':runner_id' => $runner_id
    ));
    if (!$ok) {
        die(getErrorStatusWithDummyData("Failed to update event comment."));
    }
    else {
        echo returnJSONsuccess("");
    }

    $conn = null;
}
catch(PDOException $e) {
    die(getErrorStatus($e->getMessage()));
}