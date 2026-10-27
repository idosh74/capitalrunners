<?php
require_once 'php/html_page_init.php';
?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<html dir="RTL">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>רצי הבירה - מה עשו החבר'ה</title>
<link rel="stylesheet" type="text/css" href="https://yui-s.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link href='./css/runlog.css?v=<?php echo CSS_VERSION;?>' rel='stylesheet' type='text/css'/>

<script src="./js/jquery.min.js" type="text/javascript"></script>
<script src="./js/jquery-ui.min.js" type="text/javascript"></script>
<script src="./js/jquery.elastic.js" type="text/javascript"></script>
<script src="./js/jquery.maskedinput.js" type="text/javascript"></script>
<script src="./js/json2.js" type="text/javascript"></script>
<script src="./js/constants.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/json_sans_eval.js"></script>
<script src="./js/functions.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>

<script type="text/javascript">

    var gMemberName = "<?php echo $memberAuthentication->getMemberName(); ?>";
    var gTeamCommentsLastFetched = null;
    var gCountNewTeamComments = 0;
	var runnerId = "<?php echo $memberAuthentication->getMemberId(); ?>"; 

    if (jQuery.browser.msie && jQuery.browser.version.indexOf("8.") == 0) {
        if (typeof JSON !== 'undefined') {
            JSON.parse = null;
        }
    }

    function getDayEvents()
    {
        if (feedQueueLock) {
            return;
        }
        feedQueueLock = true;

        var day = feedQueue.shift();
        $.ajax({
            url: 'php/get_daily_feed.php',
            dataType: 'text',
            data: {
                date: Time.hebDateToSqlDate(Time.jsDateToHebDate(day))
            },
            success: function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Fetch events failed - " + doc.status.emessage);
                }
                else {
                    feed.append('<div class="day_header">'+Time.jsDateToHebString(day)+'</div>');
                    var $dayEvents = $('<div class="day_events"></div>');
                    feed.append($dayEvents);
                    if (doc.data['events'].length == 0) {
                        $dayEvents.append('<div class="event">אין נתונים ליום זה</div>');
                    }
                    else {
                        for (var i in doc.data['events']) {
                            var event = doc.data['events'][i];
                            Comments.appendEvent($dayEvents, event);
                        }

                        for (var i in doc.data['comments']){
                            var comment = doc.data['comments'][i];
                            Comments.appendComment(comment);
                        }
                    }
                }

                feedQueueLock = false;
                if (feedQueue.length > 0) {
                    getDayEvents();
                }
                else {
                    onFeedScroll();
                }
            }
        });
    }

    function getNextDayEvents()
    {
        date.setDate(date.getDate() -1);
        feedQueue.push(new Date(date));
        getDayEvents();
    }

    function onFeedScroll()
    {
        if (feedQueueLock) {
            return;
        }

        if ($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
            getNextDayEvents();
        }
    }
	
	function getMyNextDayEvents()
    {
        myDate.setDate(myDate.getDate() - 30);
        myFeedQueue.push(new Date(myDate));
		feedQueueLock = false;
        getMyDayEvents();
    }

   

    function getTeamCommentsStatus(){
        $.ajax({
            url: 'php/get_team_comments_status.php',
            dataType: 'text',
            success: function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("get team comments status - " + doc.status.emessage);
                }
                else {
                    gTeamCommentsLastFetched = doc.data['last_fetched'];
                    gCountNewTeamComments = doc.data['count_new_comments'];
                    showActionLink();
                }
            }
        });
    }

    function showActionLink(){
        if (gCountNewTeamComments > 0){
            $('#show_new_comments_action').show();
        }
        else {
            $('#no_new_comments_action').show();
        }
    }

    function hideActionLink(){
        $('#show_new_comments_action').hide();
        $('#no_new_comments_action').hide();
    }

    function showNewComments(){
        $('#team_events_header').hide();
        $('#show_new_comments_action').hide();
        hideActionLink();

        $('#new_comments_header').show();
        $('#show_all_events_label').show();
        $(window).unbind('scroll');

        $('#show_more').hide();

        feed.empty();
        $.ajax({
            url: 'php/get_team_new_comments.php',
            dataType: 'text',
            data: {
                timestamp: gTeamCommentsLastFetched
            },
            success: function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("get team new comments - " + doc.status.emessage);
                }
                else {
                    var runDate = null;
                    for (var i in doc.data['events']){
                        var event = doc.data['events'][i];
                        if (event['run_date'] != runDate){
                            runDate = event['run_date'];
                            feed.append('<div class="day_header">'+Time.jsDateToHebString(Time.sqlDateToJsDate(runDate))+'</div>');
                            var $dayEvents = $('<div class="day_events"></div>');
                            feed.append($dayEvents);
                        }
                        Comments.appendEvent($dayEvents, event);
                    }

                    for (var i in doc.data['comments']){
                        var comment = doc.data['comments'][i];
                        Comments.appendComment(comment, gTeamCommentsLastFetched);
                    }

                    $.ajax({
                        url: 'php/update_team_comments_last_fetched.php',
                        dataType: 'text'
                    });
                }
            }
        });
    }
	
	function showAllComments(){
        $('#team_events_header').show();
        $('#show_new_comments_action').hide();
		$('#show_my_comments_action').show();
		$('#show_more_my').hide();
		$('#no_new_comments_action').hide();
	
        showActionLink();

        $('#new_comments_header').hide();
        $('#show_all_events_label').hide();
        $(window).scroll(onFeedScroll);

        $('#show_more').show();

        feed.empty();
        feedQueue = [];
        feedQueueLock = false;
        date = new Date();
        feedQueue.push(new Date(date));
        getDayEvents();
    }
	
	function showMyComments(){	
		
        $('#show_new_comments_action').hide();
		$('#show_my_comments_action').hide();  
		$('#show_more').hide();
		$('#show_more_my').show();
		  $('#team_events_header').show();
		
        hideActionLink();

        $('#new_comments_header').hide();
        $('#show_all_events_label').show();
		
		feed.empty();
        feedQueueLock = false;
		myDate = new Date();
        myDate.setDate(myDate.getDate() - 30);
		myFeedQueue.push(new Date(myDate));
        getMyDayEvents();
    }
	
	

	function getMyDayEvents(){
      if (feedQueueLock) {
            return;
        }
        feedQueueLock = true;
  
        var day =   myFeedQueue.shift();
		untilDate = new Date(day);
        untilDate.setDate(untilDate.getDate() + 30);
        $.ajax({
            url: 'php/get_my_comments.php',
            dataType: 'text',
            data: {
                date: Time.hebDateToSqlDate(Time.jsDateToHebDate(day)),
				untilDate: Time.hebDateToSqlDate(Time.jsDateToHebDate(untilDate)),
				runnerId: runnerId
            },
            success: function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Fetch events failed - " + doc.status.emessage);
                }
                else {
					
                  
						var runDate = null;
                       for (var i in doc.data['events']){
                        var event = doc.data['events'][i];
                        if (event['run_date'] != runDate){
                            runDate = event['run_date'];
                            feed.append('<div class="day_header">'+Time.jsDateToHebString(Time.sqlDateToJsDate(runDate))+'</div>');
                            var $dayEvents = $('<div class="day_events"></div>');
                            feed.append($dayEvents);
                        }
                            Comments.appendEvent($dayEvents, event);
                        }

                        for (var i in doc.data['comments']){
                            var comment = doc.data['comments'][i];
                            Comments.appendComment(comment);
                        }
                    
                }

                feedQueueLock = true;
				if (feedQueue.length > 0) {
                    getMyNextDayEvents();
                }
                
            }
        });
       
    }
   

    function init()
    {
        $(window).scroll(onFeedScroll);
        feedQueue = [];
		myFeedQueue = [];
        feedQueueLock = false;
        feed = $('#feed');
		$('#show_more_my').hide();
        date = new Date();
		
        feedQueue.push(new Date(date));
		
        getDayEvents();
        getTeamCommentsStatus();
        $('#comment_menu').menu();
    }

    $(document).ready(function () {
        init();
    });
</script>

<style>
    .content {
        width: 920px;
        margin: 0 auto;
    }

    .page_header {
        margin-top: 20px;
        margin-left: 10px;
        font-size: 18px;
        display: inline-block;
    }

    #feed {
        margin: 20px 0;
    }

    #feed .day_header {
        margin: 20px 0;
        padding: 4px 5px;
        font-size: 14px;
        background-color: #deecf5;
        border-radius: 2px;
    }

    #show_more {
        margin: 20px 0;
    }
	
	#show_more_my {
        margin: 20px 0;
    }
</style>
</head>
<body>
<?php require 'widgets/header.php'; ?>
<div class="content">
    <h2 id="team_events_header" class="page_header">מה עשו החבר'ה</h2>
    <a id="show_new_comments_action" href="#" onclick="showNewComments(); return false;" style="display:none;">הצג פירגונים חדשים</a>
    <span id="no_new_comments_action" style="display:none;">אין פירגונים חדשים</span>
    <h2 id="new_comments_header" class="page_header" style="display:none;">פירגונים חדשים</h2>
    <a id="show_all_events_label" href="#" onclick="showAllComments(); return false;" style="display:none;">הצג את כל האימונים</a>
	<h2 id="test" class="page_header">      </h2>
	<a id="show_my_comments_action" href="#" onclick="showMyComments(); return false;"  title="הצג אימונים שלי עם הערות מאמנים בלבד" >הצג הערות מאמנים    </a>
	<div id="comment_menu"></div>
    <div id="feed"></div>
    <div id="show_more">
        <a href="#" onclick="getNextDayEvents(); return false;">הצג עוד</a>
    </div>
	<div id="show_more_my">
        <a href="#" onclick="getMyNextDayEvents(); return false;"> הצג עוד אימונים שלי</a>
    </div>
</div>
</body>
</html>