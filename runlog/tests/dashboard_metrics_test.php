<?php
require_once __DIR__ . '/../php/dashboard_metrics_common.php';

$conn = new PDO('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE tl_events (
    run_date TEXT,
    runner_id INTEGER,
    run_distance REAL,
    warmup_distance REAL,
    cooldown_distance REAL
)');
$conn->exec("INSERT INTO tl_events VALUES
    ('2026-11-17 08:00:00', 1, 5, 1, 0.5),
    ('2026-11-16 08:00:00', 1, 3, 0, 0),
    ('2026-11-15 08:00:00', 2, 8, 0.5, 0),
    ('2026-11-10 08:00:00', 1, 20, 0, 0),
    ('2026-11-17 08:00:00', 2, 7, 0, 0)");

$metrics = getDashboardMetrics($conn, 1, '2026-11-17', '2026-11-15', '2026-11-21');
$expected = array('daily' => 13.5, 'weekly' => 25.0, 'runner_weekly' => 9.5);
if ($metrics !== $expected) {
    throw new RuntimeException('Unexpected dashboard metrics: ' . json_encode($metrics));
}

echo "Dashboard metrics tests passed.\n";
