<?php
require_once 'ajax_page_init.php';
require_once 'dashboard_data_common.php';

$currentDate = new DateTimeImmutable('today');
$date = $currentDate;
if (array_key_exists('date', $_GET)) {
    $date = parseDashboardDate($_GET['date'], $currentDate);
    if ($date === null) {
        http_response_code(400);
        die(getErrorStatusWithDummyData('Invalid dashboard date.'));
    }
}

try {
    $conn = getConnection();
    $range = array_key_exists('range', $_GET) && in_array((string)$_GET['range'], array('7', '30', '90', '180', '365'), true)
        ? (int)$_GET['range']
        : 30;
    $data = getDashboardData($conn, $date, $range, $memberAuthentication->getMemberId());
    $data['current_date'] = $currentDate->format('Y-m-d');
    $data['member_name'] = $memberAuthentication->getMemberName();
    echo returnJSONsuccess($data);
} catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}
