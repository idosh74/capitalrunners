<?php
require_once 'php/html_page_init.php';
?>
<!DOCTYPE HTML>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>

<Cache-Control: no-cache, no-store, must-revalidate
Pragma: no-cache
Expires: 0>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>

<title>רצי הבירה - סטטיסטיקה</title>
<link rel="stylesheet" type="text/css" href="https://yui-s.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet"
      type="text/css"/>
<link href='./css/runlog.css?v=<?php echo CSS_VERSION;?>' rel='stylesheet' type='text/css'/>

<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script src="./js/jquery.min.js" type="text/javascript"></script>
<script src="./js/functions.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/jquery-ui.min.js" type="text/javascript"></script>
<script src="./js/constants.js?v=<?php echo JS_VERSION;?>" type="text/javascript"></script>
<script src="./js/json_sans_eval.js"></script>
<script src="./js/jquery.ui.datepicker-he.js" type="text/javascript"></script>
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">

var memberId = <?php echo $memberId; ?>;

function drawRunnersWeek() {

    var jsonData = null;
    var jsonDataResult = $.ajax({
        url:'./php/get_team_week.php',
        dataType:"json",
        data:{
            weekly_date:Time.hebDateToSqlDate($('#datepicker').val())
        },
        success:(
            function (data) {
                drawRunnersWeekCallback(data);
            })
    });
}

function drawRunnersWeekCallback(jsonData) {
    if (jsonData.data == "") {
        if (jsonData.status.ecode == STATUS_ERR) {
            $("div#errMsg").css("color", "red");
            $("div#errMsg").html(jsonData.status.emessage);
        } else {
            $("div#errMsg").css("color", "blue");
            $("div#errMsg").html("אין נתונים, בחר תאריך אחר");
        }
        $("div#runnersweek").hide();
    } else {
        $("div#errMsg").hide();
        $("div#runnersweek").show();
    }

    var data = new google.visualization.arrayToDataTable(jsonData.data, false);

    new google.visualization.ColumnChart(document.getElementById('runnersweek')).
        draw(data,
        {
            fontSize:12,
            fontName:'Tahoma',
            backgroundColor:{fill:'#f9faf2', stroke:'#e0e0e0', strokeWidth:1},
            colors:['#accee3'],
            legend:{position:'none'},
            width:920, height:400,
            hAxis:{textStyle:{fontSize:8, color:'#45423c'}, showTextEvery:1, slantedTextAngle:45, direction:-1, slantedText:true},
            vAxis:{title:"'קילומטרז", viewWindow:{min:0, max:200}, minValue:0, maxValue:200, gridlines:{count:11}, minorGridlines:{count:1}, textStyle:{color:'#45423c'}},
            seriesType:"bars"
        }
    );
}

function drawRunnerCharts() {
    drawRunnerData();
    drawRunnerTable();
}

function drawRunnerData() {

    var runner_id = $('#users').val();
    var jsonData = null;
    var jsonDataResult = $.ajax({
        url:'./php/get_runner_weeks.php',
        dataType:"json",
        data:{
            runner_id:runner_id,
            start_date:Time.hebDateToSqlDate($('#datepicker1').val()),
            end_date:Time.hebDateToSqlDate($('#datepicker2').val())

        },
        success:(
            function(data) {
                drawRunnerDataCallback(data);
            })
    });
}

function drawRunnerDataCallback(jsonData) {

    if (jsonData.data == "") {
        if (jsonData.status.ecode == STATUS_ERR) {
            $("#errMsg1").css("color", "red");
            $("#errMsg1").html(jsonData.status.emessage);
        } else {
            $("#errMsg1").css("color", "blue");
            $("#errMsg1").html("אין נתונים, נסה שוב");
        }
        $("#errMsg1").show();
        $("#runnerweeks").hide();

        return;
    } else {
        $("#errMsg1").hide();
        $("#runnerweeks").show();
    }

    var data = new google.visualization.arrayToDataTable(jsonData.data, false);

    // Create and draw the visualization.
    new google.visualization.ComboChart(document.getElementById('runnerweeks')).
        draw(data,
        {
            fontSize:12,
            fontName:'Tahoma',
            backgroundColor:{fill:'#f9faf2', stroke:'#e0e0e0', strokeWidth:1},
            colors:['#accee3', 'orange'],
            legend:{position:'none'},
            width:920, height:400,
            hAxis:{textStyle:{fontSize:8, color:'#45423c'}, showTextEvery:1, slantedTextAngle:45, direction:-1, slantedText:true},
            vAxes:{
                0:{title:"'קילומטרז", viewWindow:{min:0, max:200}, minValue:0, maxValue:200, gridlines:{count:9}, minorGridlines:{count:1}, textStyle:{color:'#45423c'}},
                1:{title:'מספר אימוני ריצה בשבוע', viewWindow:{min:0, max:16}, minValue:0, maxValue:16, textStyle:{color:'orange'}}},
            seriesType:"bars",
            series:{1:{type:"line", targetAxisIndex:1}}
        }
    );
}

function drawRunnerTable() {

    var runner_id = document.getElementById('users').value;
    var jsonData = null;
    var jsonDataResult = $.ajax({
        url:'./php/get_runner_report.php',
        dataType:"json",
        data:{
            runner_id:runner_id,
            start_date:Time.hebDateToSqlDate($('#datepicker1').val()),
            end_date:Time.hebDateToSqlDate($('#datepicker2').val())
        },
        success:(
            function (data) {
                drawRunnerTableCallback(data);
            })
    });
}

function drawRunnerTableCallback(jsonData) {
    if (jsonData.data == "") {
        if (jsonData.status.ecode == STATUS_ERR) {
            $("#errMsg1").css("color", "red");
            $("#errMsg1").html(jsonData.status.emessage);
        } else {
            $("#errMsg1").css("color", "blue");
            $("#errMsg1").html("אין נתונים, נסה שוב");
        }
        $("#errMsg1").show();
        $("#dashboard").hide();

        return;
    } else {
        $("#errMsg1").hide();
        $("#dashboard").show();
    }

    var data = new google.visualization.DataTable();

    data.addColumn('string', 'תאריך');
    data.addColumn('string', 'תיאור');
    data.addColumn('string', 'סוג');
    data.addColumn('number', 'מרחק');
    data.addColumn('string', 'זמן');
    data.addColumn('string', 'קצב');
    data.addColumn('string', 'דופק');
    data.addColumn('string', 'דופק מקס');
    data.addColumn('string', 'טיפוס');
    data.addColumn('number', 'משקל');
    data.addRows(jsonData.data);
    
    
    var slider = new google.visualization.ControlWrapper({
        'controlType':'NumberRangeFilter',
        'containerId':'control1',
        'options':{
            'filterColumnLabel':'מרחק',
            'ui':{
                'labelStacking':'vertical',
                'label':'סנן לפי מרחק',
                'cssClass':'bc'
            }
        }
    });

    var table = new google.visualization.ChartWrapper({
        'chartType':'Table',
        'containerId':'report',
        'options':{
            'width':'920px'
        }
    });

    var categoryPicker = new google.visualization.ControlWrapper({
        'controlType':'CategoryFilter',
        'containerId':'control2',
        'options':{
            'filterColumnLabel':'סוג',
            'ui':{
                'labelStacking':'vertical',
                'allowTyping':false,
                'allowMultiple':false,
                'label':'סנן לפי סוג אימון',
                'caption':'כל הסוגים',
                'cssClass':'bc'
            }
        }
    });
    new google.visualization.Dashboard(document.getElementById('dashboard')).
        // Establish bindings, declaring the both the slider and the category
        // picker will drive both charts.
        bind([slider, categoryPicker], [table, table]).
        // Draw the entire dashboard.
        draw(data);
}

$(document).ready(function () {
    $(function () {
        $("#datepicker").datepicker({
            changeYear:true,
            changeMonth:true,
            onSelect:function (date) {
                drawRunnersWeek()
            }
        });
        $("#datepicker1").datepicker({
            changeYear:true,
            changeMonth:true,
            onSelect:function (date) {
                drawRunnerCharts();
            }
        });
        $("#datepicker2").datepicker({
            changeYear:true,
            changeMonth:true,
            onSelect:function (date) {
                drawRunnerCharts();
            }
        });
        $.datepicker.regional['he'];

        var currentDate = new Date();
        var currentDateVal = currentDate.getDate() + '/' + (currentDate.getMonth() + 1) + '/' + currentDate.getFullYear();

        var prevQuarterDate = new Date();
        prevQuarterDate.setMonth(currentDate.getMonth() - 3);
        var prevQuarterDateVal = prevQuarterDate.getDate() + '/' + (prevQuarterDate.getMonth() + 1) + '/' + prevQuarterDate.getFullYear();

        $("#datepicker").val(currentDateVal);
        $("#datepicker2").val(currentDateVal);
        $("#datepicker1").val(prevQuarterDateVal);

        drawRunnersWeek();
        Functions.populateUsersSelect(drawRunnerCharts);
    });
});
</script>

<style>
    .label {
        font-weight: bold;
    }
    #control1 .bc .goog-inline-block
    {
        margin-top: 3px;
        direction: ltr;
    }
    #report .google-visualization-table-th
    {
        text-align: right;
    }
    .bc .goog-menu-button-caption
    {
        padding: 0 0 0 4px;
    }
    .goog-menu .goog-menuitem
    {
        padding: 4px 28px 4px 7em;
    }
    .goog-menu .goog-menuitem-highlight
    {
        padding-top: 3px;
        padding-bottom: 3px;
    }
    .goog-menu .goog-menuitem-checkbox
    {
        left: auto;
        right: 6px;
    }
</style>
</head>

<html dir="RTL">
<body>

<?php require 'widgets/header.php'; ?>

<script type="text/javascript">
      google.charts.load('current', {
        packages: ['controls', 'corechart', 'table'],
        callback: drawRunnersWeek
	});
    //google.load('visualization', '1.0', {packages:['corechart']});
    //google.load('visualization', '1.0', {packages:['controls']});
    //google.load('visualization', '1.0', {packages:['table']});
</script>

<div class="RunLog" class="ui-widget" style="width:920px; margin-left:auto; margin-right:auto; padding-bottom:40px;">

    <h2 class="page_header">קילומטרז' שבועי קבוצתי</h2>

    <div style="margin-top:15px;">
        <span class="label">תאריך:&nbsp;</span>
        <input type="text" name="weekly_date" id="datepicker">
    </div>
    <div id="errMsg" style="margin-top:15px;"></div>

    <div id="runnersweek" style="width:920px; height:400px; margin-top:15px;"></div>

    <h2 class="page_header" style="margin-top:60px;">נתונים תקופתיים לרץ</h2>

    <div style="margin-top:15px;">
        <span class="label">רץ:&nbsp;</span>
        <select id="users" onchange="drawRunnerCharts();"></select>
        &nbsp;&nbsp;&nbsp;
        <span class="label">תאריך התחלה:&nbsp;</span>
        <input type="text" name="start_date" id="datepicker1">
        &nbsp;&nbsp;&nbsp;
        <span class="label">תאריך סיום:&nbsp;</span>
        <input type="text" name="end_date" id="datepicker2">
    </div>
    <div id="errMsg1" style="margin-top:15px;"></div>

    <div id="runnerweeks" style="width:920px; height:400px; margin-top:40px;"></div>

    <div id="dashboard" style="width:1020px; min-height:400px; margin-top:80px;">
        <div>
            <div id="control1" style="float:right;"></div>
            <div></div>
            <div id="control2" style="float:right; margin-right: 40px"></div>
        </div>
        <div id="report" style="width:100%"></div>
    </div>
    
    <div style="clear:both;"></div>

</div>
</body>
</html>

