<?php

function getDashboardMetrics($conn, $runnerId, $today, $weekStart, $weekEnd) {
    $sql = "SELECT
        COALESCE(SUM(CASE WHEN DATE(run_date) = :today THEN run_distance + warmup_distance + cooldown_distance ELSE 0 END), 0) AS daily,
        COALESCE(SUM(CASE WHEN DATE(run_date) >= :week_start AND DATE(run_date) <= :week_end THEN run_distance + warmup_distance + cooldown_distance ELSE 0 END), 0) AS weekly,
        COALESCE(SUM(CASE WHEN runner_id = :runner_id AND DATE(run_date) >= :runner_week_start AND DATE(run_date) <= :runner_week_end THEN run_distance + warmup_distance + cooldown_distance ELSE 0 END), 0) AS runner_weekly
        FROM tl_events";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        ':today' => $today,
        ':week_start' => $weekStart,
        ':week_end' => $weekEnd,
        ':runner_id' => $runnerId,
        ':runner_week_start' => $weekStart,
        ':runner_week_end' => $weekEnd
    ));
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

    return array(
        'daily' => (float)$metrics['daily'],
        'weekly' => (float)$metrics['weekly'],
        'runner_weekly' => (float)$metrics['runner_weekly']
    );
}
