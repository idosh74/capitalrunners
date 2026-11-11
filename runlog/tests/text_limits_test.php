<?php

if (isset($argv[1])) {
    define('MAX_TEXT_BYTES', 60000);
    function getErrorStatusWithDummyData($message) {
        return $message;
    }
    require_once __DIR__ . '/../php/comment_common.php';
    $_POST['event_comment'] = json_encode(str_repeat('a', 60001));
    getEventComment();
    exit(1);
}

require_once __DIR__ . '/../php/event_common.php';
require_once __DIR__ . '/../php/comment_common.php';

$fields = (object) array('run_distance' => 1, 'extra_run_distance' => 0, 'run_time' => 100, 'notes' => str_repeat('a', 60000));
if (!validateEventFields($fields)->isValid()) {
    throw new RuntimeException('Notes at the byte limit must be accepted.');
}
$fields->notes .= 'a';
if (validateEventFields($fields)->isValid()) {
    throw new RuntimeException('Notes above the byte limit must be rejected.');
}

$_POST['event_comment'] = json_encode((object) array('comment' => 'new'));
$_GET['event_comment'] = json_encode('old');
if (getEventComment()->comment !== 'new') {
    throw new RuntimeException('POST comment must take precedence.');
}
unset($_POST['event_comment']);
if (getEventComment() !== 'old') {
    throw new RuntimeException('Legacy GET comments must remain supported.');
}
$_POST['event_comment'] = json_encode((object) array('comment' => str_repeat('a', 60000)));
if (strlen(getEventComment()->comment) !== 60000) {
    throw new RuntimeException('A comment at the byte limit must be accepted.');
}

$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' oversized';
exec($command, $output, $status);
if ($status !== 0 || implode("\n", $output) !== 'הטקסט ארוך מדי') {
    throw new RuntimeException('Oversized comments must be rejected.');
}

echo "Text limit server tests passed.\n";
