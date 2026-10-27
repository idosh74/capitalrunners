<?php
require_once 'php/html_page_init.php';
?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<html dir="RTL">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>רצי הבירה - ניהול</title>
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

<script type='text/javascript'>

var users = new Array();
function populateUsersRecords() {
    $.ajax({
        url : 'php/users_all.php',
        dataType : 'text',
        success : function(txt) {
            var doc = Utils.parseJSON(txt);
			if (doc.status.ecode == STATUS_ERR) {
				alert("Failed to get users data - " + doc.status.emessage);
        	} else {
	            var html = "";
                users = new Array();
                
	            $.each(doc.data, function(index, item) {
	                if (item.birthdate != '0000-00-00 00:00:00')
						item.birthdate = Time.jsDateToHebDate(Time.sqlDateToJsDate(item.birthdate));
					else
						item.birthdate = '00/00/0000';
					
					
                    users[item.user_id] = item;
					html += '<tr id='+item.user_id+'>';
	                html += '<td>' + item.member_name + '</td>';
                    html += '<td>' + item.member_num + '</td>';
	                html += '<td>' + item.email + '</td>';
					html += '<td>' + item.birthdate + '</td>';
	                html += '</tr>';
	            });
	            $('#records').html(html);
                $('#users_table tr').each(function(){
                    $(this).mouseenter(function(){$(this).addClass('hover');});
                    $(this).mouseleave(function(){$(this).removeClass('hover');});
                    $(this).click(function(){openUpdateUserDialog($(this).attr('id'))});
                });
                $('#users_table tr').each(function(){
                    $(this).click(function(event){
                        //var itemId = $(this).closest('tr').attr('id');
                        event.stopPropagation();
                    });
                });
        	}
         }
    });
}

function openCreateUserDialog() {
    openUserDialog('', '', '', '','');
}

function openUpdateUserDialog(userId)
{
    var user = users[userId];
	openUserDialog(user.member_name, user.member_num, user.email, user.user_id, user.birthdate, user.active_runner);
}

function openUserDialog(userName, userMemberNum, userEmail, userId, birthDate, activeRunner)
{
	
    if (isNaN(parseInt(userId)))
    {
        var dialogTitle = 'הוסף משתמש';
    }
    else
    {
        var dialogTitle = 'ערוך משתמש';
    }
	$('#user_name').val(userName);
    $('#password').val(userMemberNum);
    $('#email').val(userEmail);
	$('#datepicker').val(birthDate);
	if (activeRunner == 1){
		$('#active_runner').prop('checked', true);
	}
	else {
		$('#active_runner').prop('checked', false);
	}
	
    $('#user_id').val(userId);
    $('#create_user_dialog').css('background-color', '#feffe5');
    $("#create_user_dialog").dialog({ title: dialogTitle });
    $("#create_user_dialog").dialog("open");
}

function getUser() {
	var active = $('#active_runner').is(':checked') ? 1 : 0;
	var user = {
	    user_name : $('#user_name').val(),
		member_num : $('#password').val(),
        email : $('#email').val(),
		user_id : $('#user_id').val(),
		birthdate : Time.hebDateToSqlDate($('#datepicker').val()),
		active_runner : active
    };
	return user;
}

$(document).ready(function() {
    $('#create_user_dialog').dialog(
       {
        height : 300,
        width : 380,
        show : {
            effect : 'drop',
            direction : 'rtl'
        },
        autoOpen: false,
        buttons : {
            "אשר" : function() {
                var user = getUser();
				
                if (user != null)
                {
                    var user_str = JSON.stringify(user);
					if (user.user_id == '')
                    {
						var url = 'php/create_user.php';
                    }
                    else
                    {
                        var url = 'php/update_user.php';
                    }
                    $.ajax({
                        url : url,
                        dataType : 'text',
                        data : {
                            userStr : user_str
                        },
                        success : function(txt) {
							var doc = Utils.parseJSON(txt);
                            if (doc.status.ecode == STATUS_ERR) {
                                alert(doc.status.emessage);
                            }
							$(this).dialog("close");
                        }
                    });
                    $(this).dialog("close");
					location.reload();
                }
            },
            "סגור" : function() {
                $(this).dialog("close");
            }
        }
       });
	$("#datepicker").datepicker({ changeYear: true, changeMonth: true });
    $.datepicker.regional['he'];

    populateUsersRecords();
});

</script>

<style>
#create_user_dialog {
    padding-left: 25px;
    padding-right: 25px;
}
#create_user_dialog .label {
    width: 85px;
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
}
#create_user_dialog input {
    width: 150px;
}
#create_user_dialog select {
    width: 158px;
}
</style>
</head>

<body>
    <?php require 'widgets/header.php'; ?>

    <div class="RunLog" class="ui-widget" style="width:920px; margin-left:auto; margin-right:auto;">
        <!--<iframe id="ifrm"  width=920px height=700px src="http://www.swrunners.com/strava/"></iframe>-->
        <div id="create_user_dialog">
            <form>
                <div style="margin-top:5px;">
                    <span class="label">שם:</span>
                    <input type="text" name="user_name" id="user_name">
                </div>

                <div style="margin-top:5px;">
                    <span class="label">סיסמא:</span>
                    <input type="text" name="password" id="password">
                </div>

                <div style="margin-top:5px;">
                    <span class="label">אימייל:</span>
                    <input type="text" name="email" id="email">
                </div>
				
				<div style="margin-top:5px;">
                    <span class="label">תאריך לידה:</span>
                    <input type="text" name="birthdate" id="datepicker">
                </div>
				
                <div style="margin-top:5px;">
                    <span class="active_col">אקטיבי:</span>
                    <input type="checkbox" id="active_runner">
                </div>
				<input type="hidden" id="user_id" name="user_id">
            </form>
        </div>

        <h2 class="page_header">ניהול משתמשים</h2>
        <input type="button" onclick="openCreateUserDialog()" value="הוסף משתמש" style="margin-top:20px;">
        <div id="data" style="margin-top:20px;">
            <table id="users_table" class="tablesorter">
                <thead>
                    <th>שם</th>
                    <th>סיסמא</th>
                    <th>אימייל</th>
					<th>תאריך לידה</th>
                </thead>
                <tbody id="records"></tbody>
            </table>
        </div>
        <div><a href="http://cp-runners.com/myangularapp/">CookBook<a></div>

    </div>
</body>
</html>