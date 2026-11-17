<?php
require_once 'ajax_page_init.php';
require_once 'dashboard_metrics_common.php';

$today = date('Y-m-d');
$todayTimestamp = strtotime($today);
$weekStart = date('Y-m-d', date('w', $todayTimestamp) == 0 ? $todayTimestamp : strtotime('last sunday', $todayTimestamp));
$weekEnd = date('Y-m-d', strtotime('next saturday', strtotime($weekStart)));

try {
    $conn = getConnection();
    $metrics = getDashboardMetrics($conn, $memberAuthentication->getMemberId(), $today, $weekStart, $weekEnd);
    echo returnJSONsuccess($metrics);
    $conn = null;
} catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}
