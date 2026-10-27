<?php
require_once 'php/html_page_init.php';
?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<html dir="RTL">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>רצי הבירה - Strava</title>
<link rel="stylesheet" type="text/css" href="https://yui-s.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link href='./css/runlog.css?v=<?php echo CSS_VERSION;?>' rel='stylesheet' type='text/css'/>

<script src="./js/jquery.min.js" type="text/javascript"></script>
<script src="./js/jquery-ui.min.js" type="text/javascript"></script>
<script src="./js/jquery.maskedinput.js" type="text/javascript"></script>
<script src="./js/json2.js" type="text/javascript"></script>
<script src="./js/constants.js" type="text/javascript"></script>
<script src="./js/json_sans_eval.js"></script>
<script src="./js/functions.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/jquery.ui.datepicker-he.js" type="text/javascript"></script>




</head>

<body>
    <?php require 'widgets/header.php'; header('Access-Control-Allow-Origin: *'); ?>

    <div class="RunLog" class="ui-widget" style="width:920px; margin-left:auto; margin-right:auto;">
        <iframe id="ifrm"  width=920px height=700px src="https://www.cp-runners.com/strava/"></iframe>
    </div>
</body>
</html>