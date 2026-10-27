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
    // loop over the result and get the shoe distance  for each shoe
    for($i = 0; $i < sizeof($result); ++$i) {
        $shoeId = $result[$i]['id'];
        $distance = getShoeDistance($shoeId);
        $result[$i]['distance'] = $distance;
    }
    return returnJSONsuccess($result);
}

/**
 * Get the shoe 'distance' - the mileage that a runner did on a specific shoe
 */
function getShoeDistance($shoeId) {
    try {
        $conn = getConnection();
        $sql = "SELECT
                        ROUND((run_distance), 1) as run_distance,
                        ROUND((warmup_distance) + (cooldown_distance), 1) as extra_run_distance,
                        shoe_id,
                        extra_shoe_id
                    FROM  tl_events
                        WHERE shoe_id = :shoe_id OR extra_shoe_id = :shoe_id";
        $sth = $conn->prepare($sql);
        $ok = $sth->execute(array (
            ':shoe_id' => $shoeId
        ));
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
        $conn = null;

        $distance = 0;
        foreach ($results as $result) {
            if ($result['shoe_id'] == $shoeId) {
                $distance += $result['run_distance'];
            }
            if ($result['extra_shoe_id'] == $shoeId) {
                $distance += $result['extra_run_distance'];
            }
            if ($result['shoe_id'] == $shoeId && $result['extra_shoe_id'] == null) {
                $distance += $result['extra_run_distance'];
            }
        }

        if (empty($distance)) {
            $distance = '0.0';
        }

        return ROUND($distance, 1);
    } catch (PDOException $e) {
        die(getErrorStatusWithDummyData($e->getMessage()));
    }
}