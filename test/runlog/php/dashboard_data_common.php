<?php

function parseDashboardDate($value, $currentDate) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || $value < '1000-01-01') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value || $date > $currentDate) {
        return null;
    }
    return $date;
}

function getDashboardData($conn, $today, $range = 30, $memberId = null) {
    $end = $today->modify('+1 day')->format('Y-m-d');
    $start = $today->modify('-364 days')->format('Y-m-d');
    $range = max(1, (int)$range);
    $measuresStart = $today->modify('-' . ($range - 1) . ' days')->format('Y-m-d');
    $weekStart = $today->modify('-' . $today->format('w') . ' days')->format('Y-m-d');
    $monthStart = $today->modify('first day of this month')->format('Y-m-d');
    $day = $today->format('Y-m-d');
    $distance = '(COALESCE(e.run_distance, 0) + COALESCE(e.warmup_distance, 0) + COALESCE(e.cooldown_distance, 0))';

    $sql = "SELECT DATE(e.run_date) AS day, COUNT(*) AS sessions,
        SUM($distance) AS distance,
        COUNT(DISTINCT CASE WHEN r.m_show_profile = 1 THEN e.runner_id END) AS active_runners,
        SUM(CASE WHEN e.run_distance > 0 AND e.run_time > 0 THEN e.run_distance ELSE 0 END) AS pace_distance,
        SUM(CASE WHEN e.run_distance > 0 AND e.run_time > 0 THEN e.run_time ELSE 0 END) AS pace_seconds
        FROM tl_events e JOIN tl_runners r ON r.id = e.runner_id
        WHERE e.run_date >= :start AND e.run_date < :end AND $distance > 0
        GROUP BY DATE(e.run_date) ORDER BY day";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':start' => $start, ':end' => $end));
    $days = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT r.id, r.member_name AS name,
        SUM(CASE WHEN e.run_date >= :today THEN $distance ELSE 0 END) AS today,
        SUM(CASE WHEN e.run_date >= :week_start THEN $distance ELSE 0 END) AS week,
        SUM(CASE WHEN e.run_date >= :month_start THEN $distance ELSE 0 END) AS month
        FROM tl_events e JOIN tl_runners r ON r.id = e.runner_id
        WHERE r.m_show_profile = 1 AND e.run_date >= :start AND e.run_date < :end AND $distance > 0
        GROUP BY r.id, r.member_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':today' => $day, ':week_start' => $weekStart, ':month_start' => $monthStart,
        ':start' => min($weekStart, $monthStart), ':end' => $end
    ));
    $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT e.id, r.member_name AS name, t.type, e.run_date, $distance AS distance,
        e.run_distance, e.run_time, COALESCE(e.notes, '') AS notes
        FROM tl_events e JOIN tl_runners r ON r.id = e.runner_id
        JOIN tl_run_types t ON t.id = e.run_type_id
        WHERE e.run_date >= :today AND e.run_date < :end AND $distance > 0
        ORDER BY e.run_date DESC, e.id DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':today' => $day, ':end' => $end));
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT r.id, r.member_name AS name,
        COUNT(e.id) AS workouts,
        COALESCE(SUM($distance), 0) AS distance
        FROM tl_runners r
        LEFT JOIN tl_events e ON e.runner_id = r.id
            AND e.run_date >= :start AND e.run_date < :end
            AND $distance > 0
        WHERE r.m_show_profile = 1
        GROUP BY r.id, r.member_name
        ORDER BY r.member_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':start' => $measuresStart, ':end' => $end));
    $runners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $persistenceTotal = 0.0;
    $intensityTotal = 0.0;
    $intensityRunners = 0;
    foreach ($runners as &$runner) {
        $workouts = (int)$runner['workouts'];
        $distanceTotal = (float)$runner['distance'];
        $runner['workouts'] = $workouts;
        $runner['distance'] = $distanceTotal;
        $runner['km_per_workout'] = $workouts > 0 ? $distanceTotal / $workouts : 0.0;
        $persistenceTotal += $workouts;
        if ($runner['km_per_workout'] > 0) {
            $intensityTotal += $runner['km_per_workout'];
            $intensityRunners++;
        }
    }
    unset($runner);

    $runnerCount = count($runners);
    $averageWorkouts = $runnerCount > 0 ? $persistenceTotal / $runnerCount : 0.0;
    $averageKmPerWorkout = $intensityRunners > 0 ? $intensityTotal / $intensityRunners : 0.0;
    foreach ($runners as &$runner) {
        $runner['persistence'] = $averageWorkouts > 0
            ? round($runner['workouts'] / $averageWorkouts * 100, 1)
            : 0.0;
        $runner['intensity'] = $averageKmPerWorkout > 0
            ? round($runner['km_per_workout'] / $averageKmPerWorkout * 100, 1)
            : 0.0;
        $runner['commitment'] = round(sqrt($runner['persistence'] * $runner['intensity']), 1);
    }
    unset($runner);

    $activeRunners = (int)$conn->query('SELECT COUNT(*) FROM tl_runners WHERE m_show_profile = 1')->fetchColumn();

    return array(
        'today' => $day,
        'week_start' => $weekStart,
        'month_start' => $monthStart,
        'member_id' => $memberId,
        'measure_range' => $range,
        'measure_averages' => array(
            'workouts' => round($averageWorkouts, 1),
            'km_per_workout' => round($averageKmPerWorkout, 1)
        ),
        'measure_runners' => $runners,
        'active_runners' => $activeRunners,
        'days' => $days,
        'leaders' => $leaders,
        'activities' => $activities
    );
}
