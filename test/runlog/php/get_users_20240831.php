<?php
require_once 'ajax_page_init.php';

try {
	$conn = getConnection();
	$the_date = strtotime('-1 month');
	if(isset($_GET['search_term'])) {
		$search_term = $_GET['search_term'];
		$sqlPhrase = "SELECT id AS value ,member_name AS label FROM  tl_runners WHERE member_name like '%" . $search_term  . "%' ORDER BY member_name";
	} else {
		$sqlPhrase = "SELECT distinct tl_runners.id,tl_runners.member_name FROM tl_runners WHERE (tl_runners.m_show_profile = '1' ) order by tl_runners.member_name"; 
		//$sqlPhrase = "SELECT distinct tl_runners.id,tl_runners.member_name FROM tl_runners,tl_events WHERE ((run_date > '" . date('Y-m-d', $the_date) . "' and tl_runners.id=tl_events.runner_id) or tl_runners.id = '" . $memberAuthentication->getMemberId() . "') order by tl_runners.member_name"; 
	}
	echo getUsersAsJSON($conn,$sqlPhrase);
	$conn = null;
} catch (PDOException $e) {
	die(getErrorStatus("SQL: " .$sqlPhrase . " -- " . $e->getMessage()));
}	

// Fetch the users info from DB as JSON
function getUsersAsJSON($conn,$sqlPhrase) {
	$stmt = $conn->query($sqlPhrase);
	$result = $stmt->fetchAll(PDO :: FETCH_ASSOC);
	
	return returnJSONsuccess($result);
}