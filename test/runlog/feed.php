<?php
require_once 'php/html_page_init.php';

?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<html dir="RTL">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
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

    function getJustMyNextDayEvents()
    {
        myDate.setDate(myDate.getDate() - 30);
        myFeedQueue.push(new Date(myDate));
		feedQueueLock = false;
        getJustMyDayEvents();
    }

    function jumpToFeedDate()
    {
        var selectedDate = $('#feedDatePicker').val();
        if (!selectedDate) {
            return;
        }

        var targetDate = new Date(selectedDate + 'T12:00:00');
        if (isNaN(targetDate.getTime())) {
            return;
        }

        feed.empty();
        feedQueue = [];
        myFeedQueue = [];
        feedQueueLock = false;
        date = targetDate;
        feedQueue.push(new Date(date));
        window.scrollTo(0, 0);
        getDayEvents();
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
                        if (String(event['run_date']).split(' ')[0] != String(runDate).split(' ')[0]){
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
        $('#show_just_my_comments_action').show(); 
        $('#show_week_plan').show();
		$('#show_more_my').hide();
        $('#show_more_just_my').hide();
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
        $('#show_just_my_comments_action').hide();   
		$('#show_week_plan').hide();
        $('#show_more').hide();
		$('#show_more_my').show();
        $('#show_more_just_my').hide();
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

    function showJustMyComments(){	
		
        $('#show_new_comments_action').hide();
		$('#show_my_comments_action').hide();  
        $('#show_just_my_comments_action').hide(); 
		$('#show_week_plan').hide();
        $('#show_more').hide();
		$('#show_more_my').hide();
		$('#team_events_header').show();
        $('#show_more_just_my').show();
		
        hideActionLink();

        $('#new_comments_header').hide();
        $('#show_all_events_label').show();
		
		feed.empty();
        feedQueueLock = false;
		myDate = new Date();
        myDate.setDate(myDate.getDate() - 30);
		myFeedQueue.push(new Date(myDate));
        getJustMyDayEvents();
    }

    function showWeekPlan(){	
		
        $('#show_new_comments_action').hide();
		$('#show_my_comments_action').hide(); 
        $('#show_just_my_comments_action').hide(); 
		$('#show_more').hide();
        $('#show_week_plan').hide();
		$('#show_more_my').hide();
        $('#show_more_just_my').hide();
		$('#team_events_header').show();
		
        hideActionLink();

        $('#new_comments_header').hide();
        $('#show_all_events_label').show();
		
		feed.empty();
        feedQueueLock = false;
		myDate = new Date();
        myDate.setDate(myDate.getDate() - 30);
		myFeedQueue.push(new Date(myDate));
        getWeekPlan();
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
                        if (String(event['run_date']).split(' ')[0] != String(runDate).split(' ')[0]){
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


	function getJustMyDayEvents(){
      if (feedQueueLock) {
            return;
        }
        feedQueueLock = true;
  
        var day =   myFeedQueue.shift();
		untilDate = new Date(day);
        untilDate.setDate(untilDate.getDate() + 30);
        $.ajax({
            url: 'php/get_just_my_comments.php',
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
                        if (String(event['run_date']).split(' ')[0] != String(runDate).split(' ')[0]){
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
                    getJustMyNextDayEvents();
                }
                
            }
        });
       
    }


    function getWeekPlan(){
      if (feedQueueLock) {
            return;
        }
        feedQueueLock = true;
  
        var day =   myFeedQueue.shift();
		untilDate = new Date(day);
        untilDate.setDate(untilDate.getDate() + 30);
        $.ajax({
            url: 'php/get_week_plan.php',
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
                        if (String(event['run_date']).split(' ')[0] != String(runDate).split(' ')[0]){
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
        $('#show_more_just_my').hide();
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

    #feed .day_events .event .event_body {
        display: block;
    }

    #feed .day_events .event .event_avatar {
        display: none;
    }

    @media screen and (max-width: 767px) {
        .feed_actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin: 10px 0 18px;
            padding: 4px 0 10px;
            border-bottom: 1px solid #dfe4e6;
        }

        .feed_actions a,
        .feed_actions span {
            display: inline-block;
            padding: 0 14px;
            border: 1px solid #c8d1d5;
            border-radius: 999px;
            background: #f4f7f7;
            color: #455a64;
            font-size: 12px;
            line-height: 30px;
            text-decoration: none;
        }

        .feed_actions a:hover {
            text-decoration: none;
            border-color: #1f6f78;
            background: #e4f3f2;
            color: #1f6f78;
        }

        .feed_actions a:focus-visible {
            outline: 2px solid #8bd3d0;
            outline-offset: 2px;
        }

        .feed_jump {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 10px 0 8px;
            padding: 4px 6px;
            border: 1px solid #dfe4e6;
            border-radius: 999px;
            background: #f8fbfb;
        }

        .feed_jump label {
            color: #718087;
            font-size: 11px;
        }

        .feed_jump input,
        .feed_jump button {
            min-height: 30px;
            box-sizing: border-box;
            border: 1px solid #c8d1d5;
            border-radius: 999px;
            font: inherit;
            font-size: 12px;
        }

        .feed_jump input {
            padding: 0 8px;
            background: #ffffff;
            color: #455a64;
        }

        .feed_jump button {
            padding: 0 12px;
            border-color: #1f6f78;
            background: #1f6f78;
            color: #ffffff;
            font-weight: bold;
            cursor: pointer;
        }

        .feed_jump button:hover {
            background: #174f55;
        }

        #feed .event_new_comment,
        #feed .event_edit_comment {
            border: 1px solid #c8d1d5;
            border-radius: 10px;
            background: #f8fbfb;
            color: #263238;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        #feed .event_new_comment::placeholder,
        #feed .event_edit_comment::placeholder {
            color: #8a989d;
        }

        #feed .event_new_comment:focus,
        #feed .event_edit_comment:focus {
            outline: none;
            border-color: #1f6f78;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(139, 211, 208, 0.28);
        }

        #feed .day_events .event {
            display: block;
        }

        #feed .day_events .event .event_body {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        #feed .day_events .event .event_avatar {
            position: relative;
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            overflow: hidden;
            border-radius: 50%;
            background: #e2eaed;
        }

        #feed .day_events .event .event_avatar::before {
            position: absolute;
            top: 8px;
            left: 14px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #81919a;
            content: "";
        }

        #feed .day_events .event .event_avatar::after {
            position: absolute;
            right: 7px;
            bottom: -3px;
            left: 7px;
            height: 23px;
            border-radius: 50% 50% 0 0;
            background: #81919a;
            content: "";
        }

        #feed .day_events .event .event_type {
            display: block;
            flex: 0 0 10px;
            width: 10px;
            min-height: 38px;
        }

        #feed .day_events .event .event_title {
            display: block;
            min-width: 0;
            padding: 0;
        }

        #feed .day_events .event .comments {
            width: 353px;
            margin-top: 8px;
            padding-top: 5px;
        }

        .content {
            width: auto;
            margin: 0 14px;
        }

        .page_header {
            display: block;
            margin: 18px 0 10px;
            color: #263238;
            font-size: 21px;
        }

        .feed_actions {
            gap: 6px;
            margin: 10px 0 18px;
            padding: 0 0 10px;
            border-bottom-color: #dfe4e6;
        }

        .feed_jump {
            display: flex;
            width: 100%;
            box-sizing: border-box;
            margin: 8px 0 12px;
            padding: 6px;
        }

        .feed_jump label {
            flex: 0 0 auto;
            padding: 0 4px;
        }

        .feed_jump input {
            width: 120px;
            min-width: 120px;
            flex: 0 1 120px;
        }

        .feed_jump button {
            flex: 0 0 auto;
            min-width: 58px;
        }

        .feed_actions a,
        .feed_actions span,
        #show_new_comments_action,
        #no_new_comments_action,
        #show_all_events_label {
            display: inline-block;
            flex: 1 1 calc(50% - 6px);
            box-sizing: border-box;
            margin: 0;
            padding: 0 8px;
            border: 1px solid #c8d1d5;
            border-radius: 999px;
            background: #f4f7f7;
            color: #455a64;
            font-size: 11px;
            line-height: 32px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .feed_actions a:hover {
            border-color: #1f6f78;
            background: #e4f3f2;
            color: #1f6f78;
        }

        #feed {
            margin: 18px 0;
        }

        #feed .day_header {
            margin: 22px 0 8px;
            padding: 8px 10px;
            border-bottom: 1px solid #c8d1d5;
            border-radius: 0;
            background: transparent;
            color: #455a64;
            font-size: 15px;
            font-weight: bold;
        }

        #feed .day_events .event,
        #feed .day_events .event:last-child {
            display: block;
            width: auto;
            margin: 10px 0;
            padding: 12px;
            border: 1px solid #d9e0e2;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(38, 50, 56, 0.08);
        }

        #feed .day_events .event > div:first-child {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
            box-sizing: border-box;
            min-width: 0;
            padding: 10px;
            border-radius: 6px;
            background: #cfe7e7;
        }

        #feed .day_events .event .event_type {
            display: block;
            flex: 0 0 5px;
            width: 5px;
            min-height: 38px;
            border-radius: 3px;
        }

        #feed .day_events .event .event_title {
            display: block;
            min-width: 0;
            padding: 0;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        #feed .day_events .event .comments {
            width: 100% !important;
            box-sizing: border-box;
            margin-top: 12px;
            padding: 10px 0 0;
            border-top: 1px solid #eef1f2;
            font-size: 12px;
        }

        #feed .day_events .event .comments > div {
            width: 100%;
        }

        #feed .day_events .event .comments_on {
            margin: 0 0 10px;
            padding: 8px;
            border-radius: 4px;
        }

        #feed .day_events .event .comments .comment {
            width: auto;
            padding: 7px 4px;
            line-height: 1.4;
        }

        #feed .day_events .event .comments .comment .comment_html {
            width: auto;
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
        }

        #feed .day_events .event .comments .comment .menu_btn {
            position: static;
            display: inline-block;
            margin-right: 4px;
        }

        #feed .day_events .event .comments .comment .menu_btn {
            min-width: 28px;
            min-height: 28px;
            margin: 0 4px 0 0;
            padding: 0 8px;
            border: 1px solid #c8d1d5;
            border-radius: 4px;
            background: #f1f5f6;
            color: #1f6f78;
            font-size: 16px;
            line-height: 26px;
            text-align: center;
        }

        #feed .day_events .event .comments .comment .menu_btn:hover {
            border-color: #1f6f78;
            background: #e4f3f2;
        }

        .content #comment_menu {
            top: 32px;
            right: 4px;
            min-width: 116px;
            padding: 4px;
            border: 1px solid #c8d1d5;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: 0 5px 16px rgba(38, 50, 56, 0.18);
            z-index: 10;
        }

        .content #comment_menu ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .content #comment_menu li {
            margin: 0;
            padding: 0;
        }

        .content #comment_menu a {
            display: block;
            padding: 9px 10px;
            border-radius: 4px;
            color: #455a64;
            font-size: 13px;
            line-height: 1.2;
            text-decoration: none;
        }

        .content #comment_menu a:hover {
            background: #eef9f8;
            color: #1f6f78;
        }

        #feed .day_events .event .comments .event_new_comment,
        #feed .day_events .event .comments .event_edit_comment {
            display: block;
            box-sizing: border-box;
            width: 100%;
            min-height: 40px;
            height: 40px;
            padding: 8px;
            font-size: 14px;
        }

        #feed .comments {
            width: 100% !important;
            box-sizing: border-box;
        }

        #feed .comments > div {
            width: 100%;
        }

        #feed .event_new_comment,
        #feed .event_edit_comment {
            display: block !important;
            width: 100% !important;
            min-height: 40px !important;
            height: 40px !important;
            padding: 8px !important;
            box-sizing: border-box;
        }

        #feed .event_new_comment,
        #feed .event_edit_comment {
            border: 1px solid #c8d1d5;
            border-radius: 10px;
            background: #d5dfe2;
            color: #263238;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        #feed .event_new_comment::placeholder,
        #feed .event_edit_comment::placeholder {
            color: #8a989d;
        }

        #feed .event_new_comment:focus,
        #feed .event_edit_comment:focus {
            outline: none;
            border-color: #1f6f78;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(139, 211, 208, 0.28);
        }

        #show_more,
        #show_more_my,
        #show_more_just_my {
            margin: 18px 0 30px;
            text-align: center;
        }

        #show_more a,
        #show_more_my a,
        #show_more_just_my a {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 999px;
            background: #1f6f78;
            color: #ffffff;
            font-weight: bold;
        }
    }
</style>
</head>
<body>
<?php require 'widgets/header.php'; ?>
<div class="content">
    <h2 id="team_events_header" class="page_header">מה עשו החבר'ה</h2>
    <div class="feed_jump">
        <label for="feedDatePicker">קפיצה לתאריך</label>
        <input type="date" id="feedDatePicker">
        <button type="button" onclick="jumpToFeedDate();">הצג</button>
    </div>
    <h2 id="new_comments_header" class="page_header" style="display:none;">פירגונים חדשים</h2>
	<div id="feed_actions" class="feed_actions">
        <a id="show_new_comments_action" href="#" onclick="showNewComments(); return false;" style="display:none;">הצג פירגונים חדשים</a>
        <span id="no_new_comments_action" style="display:none;">אין פירגונים חדשים</span>
        <a id="show_all_events_label" href="#" onclick="showAllComments(); return false;" style="display:none;">הצג את כל האימונים</a>
        <a id="show_my_comments_action" href="#" onclick="showMyComments(); return false;" title="הצג אימונים שלי עם הערות מאמנים בלבד">הצג אימונים שלי עם הערות מאמנים</a>
        <a id="show_just_my_comments_action" href="#" onclick="showJustMyComments(); return false;" title="הצג רק אימונים שלי עם הערות">הצג רק אימונים שלי</a>
        <a id="show_week_plan" href="#" onclick="showWeekPlan(); return false;" title="הצג תוכנית אימונים לשבוע זה">הצג תוכנית אימונים לשבוע זה</a>
    </div>
	<div id="comment_menu"></div>
    <div id="feed"></div>
    <div id="show_more">
        <a href="#" onclick="getNextDayEvents(); return false;">הצג עוד</a>
    </div>
	<div id="show_more_my">
        <a href="#" onclick="getMyNextDayEvents(); return false;"> הצג עוד אימונים שלי עם הערות מאמנים</a>
    </div>
    <div id="show_more_just_my">
        <a href="#" onclick="getJustMyNextDayEvents(); return false;"> הצג עוד אימונים רק שלי</a>
    </div>
</div>
</body>
</html>