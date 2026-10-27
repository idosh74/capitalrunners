<?php
require_once 'php/html_page_init.php';
?>
<!DOCTYPE HTML>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<html dir="RTL">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>רצי הבירה - המסלולים שלי</title>
<link rel="stylesheet" type="text/css" href="https://yui-s.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link href='./css/runlog.css?v=<?php echo CSS_VERSION;?>' rel='stylesheet' type='text/css'/>

<script src="./js/jquery.min.js" type="text/javascript"></script>
<script src="./js/jquery-ui.min.js" type="text/javascript"></script>
<script src="./js/jquery.maskedinput.js" type="text/javascript"></script>
<script src="./js/jquery.elastic.js" type="text/javascript"></script>
<script src="./js/json2.js" type="text/javascript"></script>
<script src="./js/constants.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/json_sans_eval.js"></script>
<script src="./js/functions.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/jquery.ui.datepicker-he.js" type="text/javascript"></script>

<script type='text/javascript'>

var courses = new Array();
function populateUserCoursesRecords() {
    var the_runner_id = <?php echo $memberId?>;
    $.ajax({
        url : 'php/user_courses.php',
        dataType : 'text',
        success : function(txt) {
            var doc = Utils.parseJSON(txt);
            if (doc.status.ecode == STATUS_ERR) {
   				alert("Failed to get courses data - " + doc.status.emessage);
           	} else {
                var html = "";
                courses = new Array();
                $.each(doc.data, function(index, item) {
                    courses[item.id] = item;

                    html += '<tr id="'+item.id+'" class="'+(item.active == 1 ? 'active' : 'inactive')+'">';
                    html += '<td class="active_col"><input type="checkbox" ' + (item.active == 0 ? '' : 'checked') + '></td>';
                    html += '<td>' + item.course_name + '</td>';
                    html += '<td>' + item.length + '</td>';
                    html += '<td>' + item.description + '</td>';
                    html += '</tr>';
                });
                $('#records').html(html);
                $('#user_courses_table tr.active').each(function(){
                    $(this).mouseenter(function(){$(this).addClass('hover');});
                    $(this).mouseleave(function(){$(this).removeClass('hover');});
                    $(this).click(function(){openUpdateCourseDialog($(this).attr('id'))});
                });
                $('#user_courses_table td.active_col input[type="checkbox"]').each(function(){
                    $(this).click(function(event){
                        var itemId = $(this).closest('tr').attr('id');
                        var isChecked = $(this).attr('checked') == 'checked';
                        updateCourseIsActive(itemId, isChecked);
                        event.stopPropagation();
                    });
                });
            }
        }
    });
}

function openCreateCourseDialog() {
    openCourseDialog('', '00.0', '', '');
}

function openUpdateCourseDialog(courseId)
{
    var course = courses[courseId];
    openCourseDialog(course.course_name, course.length, course.description, course.id);
}

function openCourseDialog(courseName, courseLength, courseDescription, courseId) {
    if (isNaN(parseInt(courseId)))
    {
        var dialogTitle = 'הוסף מסלול';
    }
    else
    {
        var dialogTitle = 'ערוך פרטי מסלול';
    }

    $('#course_name').val(courseName);
    $('#length').mask('99.9', {placeholder:"0"});
    $('#length').val(courseLength);
    $('#descriptionContainer').empty().append('<textarea id="description" maxlength="255"></textarea>');
    $('#description').val(courseDescription).elastic();
    $('#course_id').val(courseId);
    $("#create_course_dialog").dialog({ title: dialogTitle });
    $("#create_course_dialog").dialog("open");
}

function updateCourseIsActive(courseId, isActive)
{
    $.ajax({
        url : 'php/update_course_activation.php',
        dataType : 'text',
        data : {
            course_id : courseId,
            active : isActive ? 1 : 0
        },
        success : function(txt) {
            var doc = Utils.parseJSON(txt);
            if (doc.status.ecode == STATUS_ERR) {
                alert(doc.status.emessage);
            } else {
                populateUserCoursesRecords();
            }
        }
    });
}

function getCourse() {
    var course = {
        course_name : $('#course_name').val(),
        length : $('#length').val(),
        description : $('#description').val(),
        course_id : $('#course_id').val()
    }

    return course;
}

$(document).ready(function() {

    $('#create_course_dialog').dialog(
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
                var course = getCourse();
                if (course != null)
                {
                    var course_str = JSON.stringify(course);
                    if (course.course_id == '')
                    {
                        var url = 'php/create_course.php';
                    }
                    else
                    {
                        var url = 'php/update_course.php';
                    }
                    $.ajax({
                        url : url,
                        dataType : 'text',
                        data : {
                            courseStr : course_str
                        },
                        success : function(txt) {
                            var doc = Utils.parseJSON(txt);
                            if (doc.status.ecode == STATUS_ERR) {
                                alert("Create failed: "
                                        + doc.status.emessage);
                            } else {
                                //$('#calendar').fullCalendar('render');

                            }
                        }
                    });
                    $(this).dialog("close");
                    populateUserCoursesRecords();
                }
            },
            "סגור" : function() {
                $(this).dialog("close");
            }
        }
    });

    populateUserCoursesRecords();
});

</script>

<style>
#create_course_dialog {
    padding-left: 25px;
    padding-right: 25px;
}
#create_course_dialog .label {
    width: 85px;
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
}
#create_course_dialog input {
    width: 150px;
}
#create_course_dialog #description
{
    width: 233px;
    height: 52px;
    padding: 0 3px;
    max-height: 86px;
}

@media screen and (max-width: 767px) {
    .courses_page {
        width: auto !important;
        margin: 0 14px !important;
    }

    .courses_page .page_header {
        display: block;
        margin: 18px 0 12px;
        color: #263238;
        font-size: 22px;
    }

    .courses_page #data {
        margin-top: 18px !important;
        overflow-x: auto;
        direction: ltr;
    }

    .courses_page #user_courses_table {
        width: 100%;
        min-width: 520px;
        table-layout: fixed;
        direction: rtl;
    }

    .courses_page #user_courses_table th,
    .courses_page #user_courses_table td {
        padding: 10px 8px;
        font-size: 12px;
        white-space: normal;
        word-break: break-word;
    }
}
</style>
</head>
<body>
    <?php require 'widgets/header.php'; ?>

    <div class="RunLog courses_page ui-widget" style="width:920px; margin-left:auto; margin-right:auto;">
        <div id="create_course_dialog">
            <form>
                <div style="margin-top:15px;">
                    <span class="label">שם המסלול:</span>
                    <input type="text" id="course_name">
                </div>

                <div style="margin-top:5px;">
                    <span class="label">מרחק:</span>
                    <input type="text" id="length">
                </div>

                <div style="margin-top:5px;">
                    <span class="label" style="width:89px; float:right;">פרטים:</span>
                    <div id="descriptionContainer" style="float:right;"></div>
                </div>

                <input type="hidden" id="course_id" name="course_id">
            </form>
        </div>

        <h2 class="page_header">המסלולים שלי</h2>
        <input class="courses_add_button" type="button" onclick="openCreateCourseDialog()" value="הוסף מסלול" style="margin-top:20px;">

        <div id="data" style="margin-top:20px;">
            <table id="user_courses_table" class="tablesorter">
                <thead>
                    <th>בשימוש</th>
                    <th>שם המסלול</th>
                    <th>מרחק</th>
                    <th>תיאור</th>
                </thead>
                <tbody id="records"></tbody>
            </table>
        </div>

    </div>
</body>
</html>