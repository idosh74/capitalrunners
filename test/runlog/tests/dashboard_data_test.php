<?php
require_once __DIR__ . '/../php/dashboard_data_common.php';

$currentDate = new DateTimeImmutable('2026-03-10');
foreach (array('2026-03-11', '2026-02-29', '2026-3-1', '999-12-31', '', array('2026-03-10')) as $invalidDate) {
    if (parseDashboardDate($invalidDate, $currentDate) !== null) {
        throw new RuntimeException('Invalid or future reference date accepted.');
    }
}
foreach (array('2026-03-10', '2024-02-29', '1000-01-01') as $validDate) {
    $parsed = parseDashboardDate($validDate, $currentDate);
    if ($parsed === null || $parsed->format('Y-m-d') !== $validDate) {
        throw new RuntimeException('Valid reference date rejected: ' . $validDate);
    }
}

$conn = new PDO('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE tl_runners (id INTEGER, member_name TEXT, m_show_profile INTEGER)');
$conn->exec('CREATE TABLE tl_run_types (id INTEGER, type TEXT)');
$conn->exec('CREATE TABLE tl_events (id INTEGER, runner_id INTEGER, run_type_id INTEGER, run_date TEXT, run_distance REAL, warmup_distance REAL, cooldown_distance REAL, run_time INTEGER, notes TEXT)');
$conn->exec("INSERT INTO tl_runners VALUES (1, 'א', 1), (2, 'ב', 1), (3, 'ג', 0)");
$conn->exec("INSERT INTO tl_run_types VALUES (1, 'ריצה')");
$conn->exec("INSERT INTO tl_events (id, runner_id, run_type_id, run_date, run_distance, warmup_distance, cooldown_distance, run_time) VALUES
    (1, 1, 1, '2026-03-10 08:00:00', 5, 1, 0, 1500),
    (2, 2, 1, '2026-03-10 09:00:00', 4, 0, 0, 1200),
    (3, 3, 1, '2026-03-10 09:00:00', 3, 0, 0, 900),
    (4, 1, 1, '2026-03-08 10:00:00', 2, 0, 0, 600),
    (5, 1, 1, '2026-03-02 10:00:00', 7, 0, 0, 2100),
    (6, 1, 1, '2026-03-11 00:00:00', 99, 0, 0, 100),
    (7, 2, 1, '2026-03-10 10:00:00', 0, 0, 0, 0),
    (8, 2, 1, '2025-03-11 10:00:00', 8, 0, 0, 2400),
    (9, 2, 1, '2025-03-10 10:00:00', 50, 0, 0, 100)");
$conn->exec("UPDATE tl_events SET notes = 'תיאור אימון' WHERE id = 1");

$data = getDashboardData($conn, new DateTimeImmutable('2026-03-10'));
if ($data['week_start'] !== '2026-03-08' || $data['month_start'] !== '2026-03-01' || $data['active_runners'] !== 2) {
    throw new RuntimeException('Unexpected date boundaries or active runner count.');
}
$days = array_column($data['days'], null, 'day');
if (count($days) !== 4 || isset($days['2026-03-11']) || isset($days['2025-03-10']) ||
    (float)$days['2026-03-10']['distance'] !== 13.0 ||
    (int)$days['2026-03-10']['sessions'] !== 3 ||
    (int)$days['2026-03-10']['active_runners'] !== 2 ||
    (float)$days['2026-03-10']['pace_distance'] !== 12.0) {
    throw new RuntimeException('Unexpected daily metrics: ' . json_encode($days));
}
$leaders = array_column($data['leaders'], null, 'id');
if (count($leaders) !== 2 || isset($leaders[3]) ||
    (float)$leaders[1]['today'] !== 6.0 || (float)$leaders[1]['week'] !== 8.0 ||
    (float)$leaders[1]['month'] !== 15.0 || (float)$leaders[2]['today'] !== 4.0) {
    throw new RuntimeException('Unexpected leaderboard: ' . json_encode($leaders));
}
if (count($data['activities']) !== 3 || (int)$data['activities'][0]['id'] !== 3) {
    throw new RuntimeException('Unexpected daily activity feed.');
}
$activityNotes = array_column($data['activities'], 'notes', 'id');
if ($activityNotes[1] !== 'תיאור אימון' || $activityNotes[2] !== '') {
    throw new RuntimeException('Activity notes must be returned, including empty notes.');
}

$historical = getDashboardData($conn, new DateTimeImmutable('2025-03-11'));
if ($historical['today'] !== '2025-03-11' || $historical['week_start'] !== '2025-03-09' ||
    $historical['month_start'] !== '2025-03-01' || count($historical['days']) !== 2 ||
    (float)$historical['leaders'][0]['today'] !== 8.0 || count($historical['activities']) !== 1) {
    throw new RuntimeException('Historical date must filter all dashboard queries consistently.');
}

$empty = getDashboardData($conn, new DateTimeImmutable('2029-01-07'));
if ($empty['week_start'] !== '2029-01-07' || $empty['days'] || $empty['leaders'] || $empty['activities']) {
    throw new RuntimeException('Empty and Sunday boundary handling failed.');
}

$measures = getDashboardData($conn, new DateTimeImmutable('2026-03-10'), 30, 1);
if ($measures['measure_range'] !== 30 || (int)$measures['member_id'] !== 1 ||
    (float)$measures['measure_averages']['workouts'] !== 2.0 ||
    (float)$measures['measure_averages']['km_per_workout'] !== 4.5) {
    throw new RuntimeException('Unexpected runner measure averages: ' . json_encode($measures['measure_averages']));
}
$measureRunners = array_column($measures['measure_runners'], null, 'id');
if (!isset($measureRunners[1]) || !isset($measureRunners[2]) || isset($measureRunners[3]) ||
    (int)$measureRunners[1]['workouts'] !== 3 ||
    (float)$measureRunners[1]['persistence'] !== 150.0 ||
    (float)$measureRunners[1]['intensity'] !== 111.1 ||
    (float)$measureRunners[1]['commitment'] !== 129.1 ||
    (float)$measureRunners[2]['persistence'] !== 50.0 ||
    (float)$measureRunners[2]['intensity'] !== 88.9 ||
    (float)$measureRunners[2]['commitment'] !== 66.7) {
    throw new RuntimeException('Unexpected runner radar measures: ' . json_encode($measureRunners));
}
$conn->exec('DROP TABLE tl_events');
try {
    getDashboardData($conn, new DateTimeImmutable('2026-03-10'));
    throw new RuntimeException('Database errors must not be hidden.');
} catch (PDOException $e) {
}
echo "Dashboard data tests passed.\n";
