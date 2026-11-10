<?php

function getShoesDistances($conn, $shoeIds) {
    $distances = array();
    $ids = array_map('intval', $shoeIds);
    if (empty($ids)) {
        return $distances;
    }
    $idList = implode(',', $ids);

    $sql = "SELECT sid, SUM(d) AS distance FROM (
                SELECT shoe_id AS sid,
                       COALESCE(ROUND(run_distance, 1), 0)
                       + CASE WHEN extra_shoe_id IS NULL OR extra_shoe_id = 0 THEN COALESCE(ROUND(warmup_distance + cooldown_distance, 1), 0) ELSE 0 END AS d
                FROM tl_events
                WHERE shoe_id IN ($idList)
                UNION ALL
                SELECT extra_shoe_id AS sid,
                       COALESCE(ROUND(warmup_distance + cooldown_distance, 1), 0) AS d
                FROM tl_events
                WHERE extra_shoe_id IN ($idList)
            ) AS shoe_distances
            GROUP BY sid";
    foreach ($conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $distances[$row['sid']] = round($row['distance'], 1);
    }
    return $distances;
}
