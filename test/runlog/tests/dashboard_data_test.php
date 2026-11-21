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

$conn->exec("INSERT INTO tl_runners VALUES (4, 'ד', 1), (5, 'ה', 1)");
$insert = $conn->prepare('INSERT INTO tl_events (id, runner_id, run_type_id, run_date, run_distance, warmup_distance, cooldown_distance, run_time) VALUES (?, ?, 1, ?, ?, ?, ?, 0)');
$id = 100;
foreach (array(1 => 20, 2 => 21, 3 => 20, 4 => 19) as $runnerId => $count) {
    for ($i = 0; $i < $count; $i++) {
        $date = $i === 0 ? '2027-03-04 00:00:00' : '2027-03-10 23:59:59';
        $run = $runnerId === 1 ? ($i === 0 ? 0 : 5.35) : 2.24;
        $cooldown = $runnerId === 1 ? ($i === 0 ? 0 : 0.11) : 0.03;
        $insert->execute(array($id++, $runnerId, $date, $run, 99, $cooldown));
    }
}
$insert->execute(array($id++, 1, '2027-03-03 23:59:59', 9, 0, 0));
$insert->execute(array($id++, 1, '2027-03-11 00:00:00', 9, 0, 0));
$measureDate = new DateTimeImmutable('2027-03-10');
$insert->execute(array($id++, 4, $measureDate->modify('-179 days')->format('Y-m-d') . ' 00:00:00', 1, 0, 0));
$insert->execute(array($id++, 4, $measureDate->modify('-180 days')->format('Y-m-d') . ' 23:59:59', 1, 0, 0));

// Compare the dashboard result with the supplied SQL, using a seven-day window.
$referenceSql = "WITH runner_stats AS (
    SELECT r.id, r.member_name, COUNT(*) AS workouts,
        ROUND(SUM(COALESCE(e.run_distance, 0) + COALESCE(e.cooldown_distance, 0)), 1) AS total_km,
        ROUND(1.0 * SUM(COALESCE(e.run_distance, 0) + COALESCE(e.cooldown_distance, 0)) / COUNT(*), 2) AS km_per_workout
    FROM tl_runners r INNER JOIN tl_events e ON e.runner_id = r.id
    WHERE r.m_show_profile = 1 AND e.run_date >= :start AND e.run_date < :end
    GROUP BY r.id, r.member_name HAVING COUNT(*) >= 20
), group_avg AS (
    SELECT COUNT(*) AS runners_in_group, AVG(workouts) AS avg_workouts, AVG(km_per_workout) AS avg_km_per_workout
    FROM runner_stats
), indexed AS (
    SELECT rs.*, ROUND(rs.workouts * 100.0 / NULLIF(ga.avg_workouts, 0), 0) AS persistence_index,
        ROUND(rs.km_per_workout * 100.0 / NULLIF(ga.avg_km_per_workout, 0), 0) AS intensity_index,
        ga.runners_in_group
    FROM runner_stats rs CROSS JOIN group_avg ga
)
SELECT *, ROUND(SQRT(persistence_index * intensity_index), 0) AS commitment_index FROM indexed";
$reference = $conn->prepare($referenceSql);
$reference->execute(array(':start' => '2027-03-04', ':end' => '2027-03-11'));
$expected = array_column($reference->fetchAll(PDO::FETCH_ASSOC), null, 'id');
$measures = getDashboardData($conn, new DateTimeImmutable('2027-03-10'), 7, 1);
$measureRunners = array_column($measures['measure_runners'], null, 'id');
if (array_keys($measureRunners) !== array(1, 2) || count($expected) !== 2 ||
    $measureRunners[1]['workouts'] !== 20 || $measureRunners[2]['workouts'] !== 21) {
    throw new RuntimeException('Runner eligibility or date boundaries failed: ' . json_encode($measureRunners));
}
foreach ($measureRunners as $runnerId => $runner) {
    foreach (array('workouts' => 'workouts', 'distance' => 'total_km', 'km_per_workout' => 'km_per_workout',
                   'persistence' => 'persistence_index', 'intensity' => 'intensity_index', 'commitment' => 'commitment_index') as $field => $sqlField) {
        if ((float)$runner[$field] !== (float)$expected[$runnerId][$sqlField]) {
            throw new RuntimeException("Radar $field differs from SQL for runner $runnerId.");
        }
    }
}
if ($measures['measure_range'] !== 7 || (int)$measures['member_id'] !== 1 ||
    $measures['measure_averages']['workouts'] !== 20.5 ||
    abs($measures['measure_averages']['km_per_workout'] - 3.73) > 0.000001 ||
    getDashboardData($conn, new DateTimeImmutable('2027-03-10'), 30, 1)['measure_runners'][0]['workouts'] !== 21) {
    throw new RuntimeException('Runner averages or selected period failed.');
}
$halfYear = getDashboardData($conn, $measureDate, 180, 1);
$halfYearRunners = array_column($halfYear['measure_runners'], null, 'id');
if ($halfYear['measure_range'] !== 180 || !isset($halfYearRunners[4]) || $halfYearRunners[4]['workouts'] !== 20) {
    throw new RuntimeException('The 180-day period must include its first day and exclude the previous day.');
}

for ($i = 0; $i < 20; $i++) {
    $insert->execute(array($id++, 5, '2025-01-01 10:00:00', null, 2, null));
}
$zero = getDashboardData($conn, new DateTimeImmutable('2025-01-01'), 7, 5);
$zeroRunner = $zero['measure_runners'][0];
if (count($zero['measure_runners']) !== 1 || $zeroRunner['workouts'] !== 20 ||
    $zeroRunner['persistence'] !== 100.0 || $zeroRunner['intensity'] !== null || $zeroRunner['commitment'] !== null) {
    throw new RuntimeException('Zero group distance must yield undefined intensity and commitment.');
}
$noRunners = getDashboardData($conn, new DateTimeImmutable('2024-01-01'), 7, 5);
if ($noRunners['measure_runners'] || $noRunners['measure_averages']['workouts'] !== null ||
    $noRunners['measure_averages']['km_per_workout'] !== null) {
    throw new RuntimeException('Empty comparison group must not report zero averages.');
}
$conn->exec('DROP TABLE tl_events');
try {
    getDashboardData($conn, new DateTimeImmutable('2026-03-10'));
    throw new RuntimeException('Database errors must not be hidden.');
} catch (PDOException $e) {
}
echo "Dashboard data tests passed.\n";
