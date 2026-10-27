<?php
/*
 * Created on May 21, 2012
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once 'ajax_page_init.php';

$runner_id = $memberAuthentication->getMemberId();
$validationResult = validatePositiveInt($runner_id);
if (!$validationResult->isValid()) {
    die(getErrorStatusWithDummyData("Invalid runner id: " . $validationResult->getMessage()));
}

try {
    $conn = getConnection();
    echo getUserShoesRecords($conn,$runner_id);
    $conn = null;
} catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}

function getUserShoesRecords($conn,$runner_id) {
    $sql = "SELECT tl_shoes.id as id, tl_shoes.start_using_date as start_using_date, tl_shoes.active as active, tl_shoes.shoe_name as name, tl_shoe_types.id as type_id, tl_shoe_types.type as type_name FROM  tl_shoes,tl_shoe_types WHERE runner_id = '" . $runner_id . "' and tl_shoes.type=tl_shoe_types.id order by tl_shoes.active DESC, tl_shoes.start_using_date DESC";
    $stmt = $conn->query($sql);
    $result = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    // get the distance of all the shoes with a single query
    $distances = getShoesDistances($conn, array_column($result, 'id'));
    for($i = 0; $i < sizeof($result); ++$i) {
        $shoeId = $result[$i]['id'];
        $result[$i]['distance'] = isset($distances[$shoeId]) ? $distances[$shoeId] : 0.0;
    }
    return returnJSONsuccess($result);
}

/**
 * Get the 'distance' - the mileage that a runner did on each of the given shoes.
 * A single query (and connection) for all the shoes: the main distance counts for the shoe,
 * the warmup+cooldown distance counts for the extra shoe, or for the main shoe if there is no extra shoe.
 * @return array shoe id => distance
 */
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
