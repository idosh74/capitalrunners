<?php
/*
 * Created on May 21, 2012
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once 'ajax_page_init.php';
require_once 'shoe_distances.php';

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
    $distances = getShoesDistances($conn, array_column($result, 'id'));
    for($i = 0; $i < sizeof($result); ++$i) {
        $shoeId = $result[$i]['id'];
        $result[$i]['distance'] = isset($distances[$shoeId]) ? $distances[$shoeId] : 0.0;
    }
    return returnJSONsuccess($result);
}