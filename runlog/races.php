<?php
require_once 'php/html_page_init.php';
$admin = $memberAuthentication->isAdmin();
$coach = $memberAuthentication->isCoach();
if (!$admin){
	die("You have no permission to view the page!");
}
?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<html dir="RTL">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>רצי הבירה - מרוצים</title>
<link rel="stylesheet" type="text/css" href="https://yui-s.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link href='./css/runlog.css?v=<?php echo CSS_VERSION;?>' rel='stylesheet' type='text/css'/>


<script src="./js/jquery.min.js" type="text/javascript"></script>
<script src="./js/jquery-ui.min.js" type="text/javascript"></script>
<script src="./js/jquery.maskedinput.js" type="text/javascript"></script>
<script src="./js/json2.js" type="text/javascript"></script>
<script src="./js/fullcalendar.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/constants.js" type="text/javascript"></script>
<script src="./js/json_sans_eval.js"></script>
<script src="./js/functions.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/jquery.ui.datepicker-he.js" type="text/javascript"></script>

<script type='text/javascript'>

var races = new Array();
function populateRacesRecords() {
    $.ajax({
        url : 'php/races_all.php',
        dataType : 'text',
        success : function(txt) {
            var doc = Utils.parseJSON(txt);
        	if (doc.status.ecode == STATUS_ERR) {
				alert("Failed to get races data - " + doc.status.emessage);
        	} else {
	            var html = "";
                races = new Array();
	            $.each(doc.data, function(index, item) {
                    item.race_date = Time.jsDateToHebDate(Time.sqlDateToJsDate(item.race_date));
					 races[item.id] = item;
					
					html += '<tr id='+item.id+'>';
					 html += '<td>' + item.race_date + '</td>';
	                html += '<td class="race_name" id="' + item.race_name + '">' + item.race_name + '</td>';
                    html += '<td>' + item.race_type + '</td>';
					html += '<td>' + item.race_notes + '</td>';
                        html += '<td class="add_res"><input class="race_action_button" type="button" value="הוסף תוצאות" id='+item.id+' n="'+encodeURIComponent(item.race_name)+'" d="'+item.race_date+'"></td>';
                        html += '<td class="show_res"><input class="race_action_button" type="button" value="הצג תוצאות" id='+item.id+' n="'+encodeURIComponent(item.race_name)+'" d="'+item.race_date+'"></td>';
	                html += '</tr>';
	            });
				
	            $('#records').html(html);
                $('#races_table tbody tr').each(function(){
                    $(this).mouseenter(function(){$(this).addClass('hover');});
                    $(this).mouseleave(function(){$(this).removeClass('hover');});
                    $(this).click(function(){
						openUpdateRaceDialog($(this).attr('id'));
					});
                });
				
				$('#races_table td.add_res input[type="button"]').each(function(){
					$(this).click(function(event){
						var r_name = decodeURIComponent($(this).attr('n'));
						var r_date = $(this).attr('d');
						openResultsDialog(r_name,r_date,$(this).attr('id'));
						event.stopPropagation();
					});
                });

				$('#races_table td.show_res input[type="button"]').each(function(){
					$(this).click(function(event){
						var r_name = decodeURIComponent($(this).attr('n'));
						var r_date = $(this).attr('d');
						openRaceResultsDialog($(this).attr('id'),r_name,r_date);
						event.stopPropagation();
					});
                });
                
        	}
         }
    });
}

function openCreateRaceDialog() {
    openRaceDialog('', '', '', '', '', '' );
}

function openUpdateRaceDialog(raceId)
{
    var race = races[raceId];
    openRaceDialog(race.race_name, race.race_date, race.race_type, race.type_id, race.race_notes, race.id);
}

function openRaceDialog(raceName, raceDate, raceType, raceTypeId, raceNotes, raceId)
{
    if (isNaN(parseInt(raceId)))
    {
        var dialogTitle = 'הוסף מרוץ';
    }
    else
    {
        var dialogTitle = 'ערוך פרטי מרוץ';
    }

    if (raceDate == null || raceDate == ''){
        raceDate = Time.jsDateToHebDate(new Date());
    }

    $('#race_name').val(raceName);
    $('#datepicker').val(raceDate);
	populateRaceTypesSelect(raceTypeId);
    $('#race_type_id').val(raceTypeId);
	$('#descriptionContainer').empty().append('<textarea id="description" maxlength="255"></textarea>');
	$('#description').val(raceNotes);
    $('#race_id').val(raceId);
    $("#create_race_dialog").dialog({ title: dialogTitle });
    $("#create_race_dialog").dialog("open");
}

function openResultsDialog(raceName,raceDate, raceId)
{
    var dialogTitle = 'הוסף תוצאה';
	
    $('#results_race_name').text(raceName);
    $('#results_race_date').text(raceDate);
	$.mask.definitions['5'] = "[0-5]";
	$('#run_time').val('00:00:00');
    $('#run_time').mask('9:59:59', {placeholder:"0"});
	
	$('#result_race_id').val(raceId);
	$('#create_results_dialog .label').css({
		'width': '185px',
		'font-weight': 'bold'
	});
	$('#create_results_dialog .label1').css({
		'color': 'purple',
		'font-weight': 'bold'
	});
    $("#create_results_dialog").dialog({ title: dialogTitle });
	
	$("#create_results_dialog").dialog("open");
	drawRunnerTable(raceId) ;
	
}



function openRaceResultsDialog(raceId,raceName,raceDate )
{
	populateRacesResults(raceId);
	var dialogTitle = 'תוצאות '.concat(raceName);
	dialogTitle = dialogTitle.concat(' - ');
	dialogTitle = dialogTitle.concat(raceDate);
	
	$('#race_results_dialog').dialog({
		height: 300,
		width: 300,
		modal: true,
		resizable: true,
        dialogClass: 'modernDialog no-close success-dialog',
		
		buttons: {
					סגור: function() { //ok
						$( this ).dialog( "close" );
					}
                }
	});
//	$('#race_results_dialog').css({
//		'width': '500px',
//		'height': '500px'
//	});
//	$('#create_results_dialog .label1').css({
//		'color': 'purple',
//		'font-weight': 'bold'
//	});
	$("#race_results_dialog").dialog({ title: dialogTitle });
	$("#race_results_dialog").dialog("open");

}



function drawRunnerTable (raceId) {
	$.ajax({
		url:'php/get_user_results.php',
		dataType:'text',
		data:{
			race_id:raceId
		},
		success:function (txt) {
			var doc = Utils.parseJSON(txt);
			if (doc.status.ecode == STATUS_ERR) {
				alert("Deletion failed: "
					+ doc.status.emessage);
			} else {
				$('#race_users').empty();
				$.each(doc.data, function (index, item) {
					$('#race_users')
						.append($("<option></option>")
							.attr("value", item.id)
							.text(item.member_name));
				});
				$('#race_users').val(memberId);
				//$('#users').show();       
			 
				if (typeof onSuccessCallback == 'function') {
					onSuccessCallback();
				}
			}
		}
	});
}

function populateRaceTypesSelect(raceTypeId) {
	
	$.ajax({
		url:'php/get_race_types.php',
		dataType:'text',
		data:{
		},
		success:function (txt) {
			
			var doc = Utils.parseJSON(txt);
			if (doc.status.ecode == STATUS_ERR) {
				alert("Deletion failed: "
					+ doc.status.emessage);
			} else {
				$("#race_type").empty()
				$.each(doc.data, function (index, item) {
					$('#race_type')
						.append($("<option></option>")
							.attr("value", item.id)
							.text(item.type));
				});

	            $('#race_type ').val(raceTypeId);
				//$('#race_type').show();

				
			}
		}
	});
}

function getRace() {
    var raceDate = Time.hebDateToSqlDate($('#datepicker').val());
    if (raceDate == null || raceDate == '') {
        raceDate = Time.hebDateToSqlDate(Time.jsDateToHebDate(new Date()));
    }
	var race = {
	    race_name : $('#race_name').val(),
        race_date : raceDate,
        race_type : $('#race_type').val(),
		race_type_id : $('#race_type_id').val(),
		race_notes : $('#description').val(),
        race_id : $('#race_id').val()
    };

	return race;
}

function getResult() {
	var result = {
        race_id : $('#result_race_id').val(),
		runner_id : $('#race_users').val(),
		run_time : Time.convertHMMSSToSeconds($('#run_time').val()),
		notes : $('#comments').val()
    };

	return result;
}


function populateRacesResults(raceId) {
    $.ajax({
        url : 'php/get_race_results.php',
        dataType : 'text',
		data:{
			race_id: raceId
		},

        success : function(txt) {
            var doc = Utils.parseJSON(txt);
        	if (doc.status.ecode == STATUS_ERR) {
				alert("Failed to get races data - " + doc.status.emessage);
        	} else {
	            var html = "";
				//html += '<span>' + raceName + '</span>';
                race_results = new Array();
	            $.each(doc.data, function(index, item) {
					race_results[item.runner_id] = item;		
					html += '<tr id='+item.runner_id+'>';
//						html += '<td>' + item.race_date + '</td>';
					html += '<td>' + item.runner_name + '</td>';
					html += '<td>' + Time.convertSecondsToHMMSS(item.run_time) + '</td>';
					html += '<td>' + item.notes + '</td>';
					html += '</tr>';	            
				});
                		
	            $('#records1').html(html);
                
        	}
         }
    });
}



$(document).ready(function() {

    $('#create_race_dialog').dialog(
       {
        height : 300,
        width : 380,
        show : {
            effect : 'drop',
            direction : 'rtl'
        },
        autoOpen: false,
        dialogClass: 'modernDialog',
        buttons : {
            "אשר" : function() {
                var race = getRace();
                if (race != null)
                {
                    var race_str = JSON.stringify(race);
					if (race.race_id == '')
                    {
                        var url = 'php/create_race.php';
                    }
                    else
                    {
                        var url = 'php/update_race.php';
                    }
                    $.ajax({
                        url : url,
                        dataType : 'text',
                        data : {
                            raceStr : race_str
                        },
                        success : function(txt) {
                            var doc = Utils.parseJSON(txt);
                            if (doc.status.ecode == STATUS_ERR) {
                                alert(doc.status.emessage);
                            }
                        }
                    });
                    $(this).dialog("close");
                    populateRacesRecords();
                }
            },
			"סגור" : function() {
                $(this).dialog("close");
            },
            "מחק" : function() {
                var race = getRace();
                if (race != null)
                {
                    var race_str = JSON.stringify(race);
                    $.ajax({
                        url : 'php/delete_race.php',
                        dataType : 'text',
                        data : {
                            raceStr : race_str
                        },
                        success : function(txt) {
                            var doc = Utils.parseJSON(txt);
							
                            if (doc.status.ecode == STATUS_ERR) {
                                alert(doc.status.emessage);
                            }
							else{
								location.reload();
							}	
                        }
                    });
                    $(this).dialog("close");
                }
            }
        }
    });

	$('#create_results_dialog').dialog(
       {
        height : 300,
        width : 380,
        show : {
            effect : 'drop',
            direction : 'rtl'
        },
        autoOpen: false,
        dialogClass: 'modernDialog',
        buttons : {
            "אשר" : function() {
                var result = getResult();
				var race_id = $('#result_race_id').val();
                if (result != null)
                {
                    var result_str = JSON.stringify(result);
					var url = 'php/create_result.php';
                    $.ajax({
                        url : url,
                        dataType : 'text',
                        data : {
                            resultStr : result_str
                        },
                        success : function(txt) {
							var doc = Utils.parseJSON(txt);
                            if (doc.status.ecode == STATUS_ERR) {
                                alert(doc.status.emessage);
                            }
							location.reload();
							
							//$(this).dialog("close");
                        }
                    });
			    }
            },
            "סגור" : function() {
				location.reload();
		        
                //$(this).dialog("close");
            }
			
        }
		
    });

    $(function() {
        $("#datepicker").datepicker({ changeYear: true, changeMonth: true });
        $.datepicker.regional['he'];
		populateRacesRecords();

		
    });
   
});

</script>

<style>
#create_race_dialog {
    padding-left: 25px;
    padding-right: 25px;
}
#create_race_dialog .label {
    width: 85px;
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
}
#create_race_dialog input {
    width: 150px;
}
#create_race_dialog select {
    width: 158px;
}
#create_race_dialog #description
{
    width: 233px;
    height: 52px;
    padding: 0 3px;
    max-height: 86px;
}	

.races_add_button,
.race_action_button {
    min-height: 38px;
    padding: 0 12px;
    box-sizing: border-box;
    border: 1px solid #1f6f78 !important;
    border-radius: 999px !important;
    background: #1f6f78 !important;
    color: #ffffff !important;
    font: inherit;
    font-weight: bold;
    cursor: pointer;
}

.races_add_button:hover,
.race_action_button:hover {
    background: #174f55 !important;
}

@media screen and (max-width: 767px) {
    .races_page {
        width: auto !important;
        margin: 0 14px !important;
    }

    .races_page .page_header {
        display: block;
        margin: 18px 0 12px;
        color: #263238;
        font-size: 22px;
    }

    #data {
        margin-top: 18px !important;
        overflow-x: auto;
        direction: ltr;
    }

    #races_table {
        width: 100%;
        min-width: 720px;
        table-layout: fixed;
        direction: rtl;
    }

    #races_table th,
    #races_table td {
        padding: 10px 8px;
        font-size: 12px;
        white-space: normal;
        word-break: break-word;
    }

    #races_table th:nth-child(1),
    #races_table td:nth-child(1) {
        width: 90px;
    }

    #races_table th:nth-child(5),
    #races_table td:nth-child(5) {
        width: 110px;
    }

    #races_table th:nth-child(6),
    #races_table td:nth-child(6) {
        width: 110px;
    }

    #create_race_dialog,
    #create_results_dialog {
        box-sizing: border-box;
        padding: 14px !important;
    }

    #create_race_dialog .label,
    #create_results_dialog .label {
        display: block;
        width: auto !important;
        margin-bottom: 5px;
        font-size: 12px;
    }

    #create_race_dialog input:not([type="hidden"]),
    #create_race_dialog select,
    #create_race_dialog textarea,
    #create_results_dialog input:not([type="hidden"]),
    #create_results_dialog select {
        width: 100% !important;
        min-height: 38px;
        box-sizing: border-box;
        font-size: 15px;
    }

    #create_race_dialog textarea {
        min-height: 70px;
    }

    .ui-dialog {
        max-width: 92vw !important;
    }
}


</style>
</head>

<body>
    <?php require 'widgets/header.php'; ?>

    <div class="RunLog ui-widget races_page" style="width:920px; margin-left:auto; margin-right:auto;">
        <div id="create_race_dialog">
            <form>
                <div style="margin-top:15px;">
                    <span class="label">מרוץ:</span>
                    <input id="race_name">
                </div>

                <div style="margin-top:5px;">
                    <span class="label">תאריך:</span>
                    <input type="text" name="race_date" id="datepicker">
                </div>

                <div style="margin-top:5px;">
                    <label class="label">מרחק:</label>
                    <select id="race_type"></select>
                </div>

                <div style="margin-top:5px;">
                    <span class="label" style="width:89px; float:right;">פרטים:</span>
                    <div id="descriptionContainer" style="float:right;"></div>
                </div>
				
				<input type="hidden" id="race_type_id" name="race_type_id">
                <input type="hidden" id="race_id" name="race_id">
            </form>
        </div>

        <div id="create_results_dialog" >
            <form>
                <div style="margin-top:15px;">
                    <span id="results_race_name" class="label1"></span>
                </div>
				
                <div style="margin-top:5px;">
                    <span id="results_race_date" class="label1"></span>
                </div>
				<div style="margin-top:15px;">
					<span class="label">רץ:</span>
					<select id="race_users" ></select>
				</div>
				<div style="margin-top:15px;">
                    <span class="label">תוצאה:</span>
                    <input id="run_time" >
                </div>
                <div style="margin-top:5px;">
                    <span class="label">פרטים:</span>
                    <input id="comments">
                </div>
				
				<input type="hidden" id="result_race_id" >
            </form>
        </div>

		<div id="race_results_dialog" style="margin-top:20px; display:none;">
			<table id="results_table" class="tablesorter">
				<thead>
					<th>רץ</th>
					<th>תוצאה</th>
					<th>הערות</th>
				</thead>
				<tbody id="records1"></tbody>
			</table>
		</div>	



        <h2 class="page_header">מרוצים</h2>
        <input class="races_add_button" type="button" onclick="openCreateRaceDialog()" value="הוסף מרוץ" style="margin-top:20px;">
        <div id="data" style="margin-top:20px;">
            <table id="races_table" class="tablesorter">
                <thead>
					<th>תאריך</th>
                    <th>מרוץ</th>
                    <th>סוג</th>
                    <th>הערות</th>
                    <th>הוספת תוצאות</th>
                    <th>הצגת תוצאות</th>
                </thead>
                <tbody id="records"></tbody>
            </table>
        </div>

    </div>
</body>
</html>