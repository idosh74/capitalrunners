<?php

// -----------------------------------------------------------------------------------------
// This script is responsible for creating a new event for a user.
// It expects 2 input parameters:
// 1)  The member ID - in order to see if the caller is logged in
// 2)  The Date - for setting the event's date
// 3)  The event type ID
// 4)  The warmup time
// 5)  The main excersize time
// 6)  The cooldown time
// 7)  The warmup distance
// 8)  The main excersize distance
// 9)  The cooldown distance
// 10) The event description
// 12) The shoe ID
// 13) The course ID
// The output of the script is a JSON with 2 fields:
// 1) ecode [ERR || OK]
// 2) emessage - free text
// NOTE: since the client is expecting a JSON result - we should always return a valid JSON
// -----------------------------------------------------------------------------------------
//header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

//error_reporting(E_ALL ^ E_NOTICE);
require_once 'member_authentication.php';
require_once 'constants.php';
require_once 'utils.php';
//require_once 'html_page_init.php';
$json = file_get_contents('php://input');
$data = json_decode($json);
$memberAuthentication = new memberAuthentication();
if (!$memberAuthentication->isMemberAuthenticated())
{
    //echo $data->email;
    //echo $data->password;
    $memberAuthentication->login($data->email,$data->password,true);
    if (!$memberAuthentication->isMemberAuthenticated()){
        return;
    }
}
$runner_id = $memberAuthentication->getMemberId();
//echo "--->  $runner_id";
try {
    $conn = getConnection();
    //$sql = "SELECT * from tl_runners where tl_runners.id = '" . $runner_id . "';" ; 
    $sql = "SELECT id,member_name,member_num,s_client_id,s_client_secret,s_refresh_token,member_log_date,city,street,zipcode,birthday,email,phone,mobile,m_pic,m_bigpic,m_top800,m_top1500,m_top3000,m_top5000,m_top10000,m_top10k,m_tophm,m_topm,m_ocup,m_text,m_show_profile from tl_runners where tl_runners.id = '" . $runner_id . "';" ; 
    //id,member_name,member_num,s_client_id,s_client_secret,s_refresh_token,member_log_date,city,street,zipcode,birthday,email,phone,mobile,m_pic,m_bigpic,m_top800,m_top1500,m_top3000,m_top5000,m_top10000,m_top10k,m_tophm,m_topm,m_ocup,m_text,m_show_profile
    $stmt = $conn->query($sql);
    $result = $stmt->fetchAll(PDO :: FETCH_ASSOC);
    echo returnJSONsuccess($result);
    $conn = null;
} catch (PDOException $e) {
    die(getErrorStatusWithDummyData($e->getMessage()));
}
