<?php

require_once __DIR__ . '/../php/event_common.php';

$_POST = array('event_fields' => '{"date":"2026-11-10","notes":"test"}');
$_GET = array('event_fields' => '{"notes":"old"}');
$fields = getEventFields();
if ($fields->date !== '2026-11-10' || $fields->notes !== 'test') {
    throw new RuntimeException('POST event fields must take precedence.');
}

$_POST = array();
$fields = getEventFields();
if ($fields->notes !== 'old') {
    throw new RuntimeException('Legacy GET event fields must remain supported.');
}

echo "Event fields tests passed.\n";
