<?php
require_once 'php/html_page_init.php';
?>
<!DOCTYPE HTML>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1">

<Cache-Control: no-cache, no-store, must-revalidate
Pragma: no-cache
Expires: 0>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>

<title>הרוח השניה - סטטיסטיקה</title>
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

function statisticsChartWidth() {
    return window.innerWidth <= 767 ? Math.max(300, window.innerWidth - 36) : 920;
}

function renderMobileTeamChart(rows) {
    var chart = $('#mobileTeamChart').empty();
    var values = rows.slice(1).map(function (row) {
        return { name: row[0], distance: Number(row[1]) || 0 };
    }).filter(function (row) {
        return row.name;
    }).sort(function (a, b) {
        return b.distance - a.distance;
    });
    var maximum = values.length ? Math.max(values[0].distance, 1) : 1;

    if (!values.length) {
        chart.append('<p class="statistics_mobile_empty">אין נתונים להצגה</p>');
        return;
    }

    values.forEach(function (row) {
        var percentage = Math.max(2, Math.round(row.distance / maximum * 100));
        var item = $('<div class="mobile_team_row"></div>');
        item.append($('<span class="mobile_team_name"></span>').text(row.name));
        var track = $('<span class="mobile_team_track"></span>');
        track.append($('<span class="mobile_team_bar"></span>').css('width', percentage + '%'));
        item.append(track);
        item.append($('<strong class="mobile_team_value"></strong>').text(row.distance + ' ק״מ'));
        chart.append(item);
    });
}

function showStatisticsSection(target) {
    $('.statistics_mobile_tab').removeClass('active');
    $('.statistics_mobile_tab[data-target="' + target + '"]').addClass('active');
    $('.statistics_section').removeClass('mobile_active');
    $('#' + target).addClass('mobile_active');
}

function renderMobileRunnerWeeks(rows) {
    var chart = $('#mobileRunnerWeeks').empty();
    var values = (rows || []).slice(1).map(function (row) {
        return { date: row[0], distance: Number(row[1]) || 0, sessions: Number(row[2]) || 0 };
    }).filter(function (row) {
        return row.date;
    });
    var maximum = values.reduce(function (max, row) {
        return Math.max(max, row.distance);
    }, 1);

    if (!values.length) {
        chart.append('<p class="statistics_mobile_empty">אין נתונים להצגה</p>');
        return;
    }

    values.forEach(function (row) {
        var percentage = Math.max(2, Math.round(row.distance / maximum * 100));
        var item = $('<div class="mobile_runner_week"></div>');
        item.append($('<span class="mobile_runner_week_date"></span>').text(row.date));
        var track = $('<span class="mobile_team_track"></span>');
        track.append($('<span class="mobile_team_bar"></span>').css('width', percentage + '%'));
        track.append($('<span class="mobile_runner_sessions"></span>').text(row.sessions));
        item.append(track);
        item.append($('<strong class="mobile_runner_week_value"></strong>').text(row.distance + ' ק״מ'));
        chart.append(item);
    });
}

function renderMobileRunnerReport(rows) {
    var report = $('#mobileRunnerReport').empty();
    var typeFilter = $('#mobileRunnerTypeFilter').val() || '';
    var minimumDistance = parseFloat($('#mobileRunnerMinDistance').val());
    var maximumDistance = parseFloat($('#mobileRunnerMaxDistance').val());
    (rows || []).filter(function (row) {
        var distance = Number(row[3]) || 0;
        return row && row[0] && (!typeFilter || row[2] === typeFilter) &&
            (isNaN(minimumDistance) || distance >= minimumDistance) &&
            (isNaN(maximumDistance) || distance <= maximumDistance);
    }).forEach(function (row) {
        if (!row || !row[0]) {
            return;
        }
        var card = $('<article class="mobile_runner_card"></article>');
        var header = $('<div class="mobile_runner_card_header"></div>');
        header.append($('<strong></strong>').text(row[2] || 'אימון'));
        header.append($('<span></span>').text(row[0]));
        card.append(header);
        card.append($('<div class="mobile_runner_card_main"></div>').text((row[3] || 0) + ' ק״מ  ·  ' + (row[5] || '--')));
        var details = [];
        if (row[6] && row[6] !== '0') details.push('דופק ' + row[6]);
        if (row[8] && row[8] !== '0') details.push('טיפוס ' + row[8]);
        if (row[1]) details.push(row[1]);
        if (details.length) {
            card.append($('<div class="mobile_runner_card_details"></div>').text(details.join(' · ')));
        }
        report.append(card);
    });
    if (!report.children().length) {
        report.append('<p class="statistics_mobile_empty">אין אימונים להצגה</p>');
    }
}

function setupMobileRunnerFilters(rows) {
    var select = $('#mobileRunnerTypeFilter');
    var currentValue = select.val() || '';
    var types = {};
    (rows || []).forEach(function (row) {
        if (row && row[2]) types[row[2]] = true;
    });
    select.empty().append('<option value="">כל סוגי האימונים</option>');
    Object.keys(types).sort().forEach(function (type) {
        select.append($('<option></option>').val(type).text(type));
    });
    select.val(types[currentValue] ? currentValue : '');
}

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
    renderMobileTeamChart(jsonData.data || []);
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
            width:statisticsChartWidth(), height:320,
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

    renderMobileRunnerWeeks(jsonData.data || []);

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
            width:statisticsChartWidth(), height:320,
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
    window.mobileRunnerReportRows = jsonData.data || [];
    setupMobileRunnerFilters(window.mobileRunnerReportRows);
    renderMobileRunnerReport(jsonData.data || []);
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
    data.addColumn('number', 'מאמץ');
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
            'width': window.innerWidth <= 767 ? '100%' : '920px'
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

    .statistics_mobile_nav {
        display: none;
    }

    #mobileTeamChart {
        display: none;
    }

    #mobileRunnerWeeks,
    #mobileRunnerReport {
        display: none;
    }

    @media screen and (max-width: 767px) {
        .statistics_page {
            width: auto !important;
            margin: 0 14px !important;
            padding-bottom: 24px !important;
        }

        .statistics_mobile_nav {
            display: flex;
            gap: 6px;
            margin: 18px 0 22px;
            direction: rtl;
        }

        .statistics_mobile_tab {
            flex: 1;
            min-height: 38px;
            padding: 0 12px;
            border: 1px solid #c8d1d5;
            border-radius: 999px;
            background: #f4f7f7;
            color: #455a64;
            font: inherit;
            font-weight: bold;
        }

        .statistics_mobile_tab.active {
            border-color: #1f6f78;
            background: #1f6f78;
            color: #ffffff;
        }

        .statistics_section {
            display: none;
        }

        .statistics_section.mobile_active {
            display: block;
        }

        .statistics_page .page_header {
            display: block;
            margin: 18px 0 12px !important;
            color: #263238;
            font-size: 21px;
        }

        .statistics_page .label {
            display: block;
            margin-bottom: 6px;
        }

        .statistics_page #datepicker,
        .statistics_page #datepicker1,
        .statistics_page #datepicker2,
        .statistics_page #users {
            width: 100%;
            min-height: 38px;
            box-sizing: border-box;
            border-radius: 999px;
            padding: 0 10px;
        }

        .statistics_page #runner_statistics_section > div[style*="margin-top:15px"] {
            display: grid;
            gap: 10px;
            margin-top: 15px !important;
        }

        .statistics_page #runnersweek,
        .statistics_page #runnerweeks {
            width: 100% !important;
            height: 320px !important;
            margin-top: 18px !important;
            overflow: hidden;
        }

        .statistics_page #runnersweek {
            display: none !important;
        }

        .statistics_page #runnerweeks,
        .statistics_page #dashboard {
            display: none !important;
        }

        .statistics_page #mobileRunnerWeeks,
        .statistics_page #mobileRunnerReport {
            display: block;
        }

        .statistics_page #mobileRunnerWeeks {
            margin-top: 18px;
            padding: 12px 10px;
            border: 1px solid #dfe4e6;
            border-radius: 10px;
            background: #ffffff;
        }

        .mobile_runner_week {
            display: grid;
            grid-template-columns: minmax(76px, 30%) 1fr auto;
            gap: 8px;
            align-items: center;
            min-height: 32px;
            direction: ltr;
        }

        .mobile_runner_week_date {
            color: #455a64;
            font-size: 11px;
            text-align: right;
            white-space: nowrap;
        }

        .mobile_runner_week .mobile_team_track {
            position: relative;
            margin: 0;
        }

        .mobile_runner_week_value {
            min-width: 48px;
            color: #1f6f78;
            font-size: 10px;
            text-align: left;
            white-space: nowrap;
        }

        .mobile_runner_sessions {
            position: absolute;
            top: 50%;
            left: 8px;
            transform: translateY(-50%);
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            line-height: 1;
        }

        .statistics_page #mobileRunnerReport {
            margin-top: 22px;
        }

        .mobile_runner_filters {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .mobile_runner_distance_row {
            display: flex;
            gap: 8px;
            direction: rtl;
        }

        .mobile_runner_distance_row input {
            flex: 1;
            min-width: 0;
        }

        .mobile_runner_filters select,
        .mobile_runner_filters input {
            width: 100%;
            min-height: 38px;
            box-sizing: border-box;
            padding: 0 10px;
            border: 1px solid #c8d1d5;
            border-radius: 999px;
            background: #ffffff;
            color: #455a64;
            font: inherit;
        }

        .mobile_runner_card {
            margin: 0 0 10px;
            padding: 12px;
            border: 1px solid #dfe4e6;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(38, 50, 56, 0.06);
        }

        .mobile_runner_card_header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: #455a64;
            font-size: 12px;
        }

        .mobile_runner_card_header span {
            color: #718087;
            font-size: 11px;
        }

        .mobile_runner_card_main {
            margin-top: 8px;
            color: #1f6f78;
            font-size: 16px;
            font-weight: bold;
        }

        .mobile_runner_card_details {
            margin-top: 6px;
            color: #718087;
            font-size: 11px;
            line-height: 1.5;
        }

        .statistics_page #mobileTeamChart {
            display: block;
            margin-top: 18px;
            padding: 12px 10px;
            border: 1px solid #dfe4e6;
            border-radius: 10px;
            background: #ffffff;
        }

        .mobile_team_row {
            display: grid;
            grid-template-columns: minmax(76px, 30%) 1fr auto;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            direction: ltr;
        }

        .mobile_team_name {
            overflow: hidden;
            color: #455a64;
            font-size: 11px;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mobile_team_track {
            display: block;
            height: 18px;
            overflow: hidden;
            border-radius: 999px;
            background: #edf2f3;
        }

        .mobile_team_bar {
            display: block;
            height: 100%;
            min-width: 5px;
            border-radius: 999px;
            background: #267ab0;
        }

        .mobile_team_value {
            min-width: 52px;
            color: #1f6f78;
            font-size: 11px;
            text-align: left;
            white-space: nowrap;
        }

        .statistics_mobile_empty {
            margin: 12px 0;
            color: #718087;
            text-align: center;
        }

        .statistics_page #dashboard {
            width: 100% !important;
            min-height: 0 !important;
            margin-top: 28px !important;
            overflow-x: auto;
        }

        .statistics_page #control1,
        .statistics_page #control2 {
            float: none !important;
            margin: 0 0 10px !important;
        }

        .statistics_page #report {
            min-width: 760px;
        }
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

<div class="RunLog statistics_page" class="ui-widget" style="width:920px; margin-left:auto; margin-right:auto; padding-bottom:40px;">

    <div class="statistics_mobile_nav" role="tablist" aria-label="תצוגת סטטיסטיקה">
        <button type="button" class="statistics_mobile_tab active" data-target="team_statistics_section" onclick="showStatisticsSection('team_statistics_section');">קבוצה</button>
        <button type="button" class="statistics_mobile_tab" data-target="runner_statistics_section" onclick="showStatisticsSection('runner_statistics_section');">רץ</button>
    </div>

    <section id="team_statistics_section" class="statistics_section mobile_active">
    <h2 class="page_header">קילומטרז' שבועי קבוצתי</h2>

    <div style="margin-top:15px;">
        <span class="label">תאריך:&nbsp;</span>
        <input type="text" name="weekly_date" id="datepicker">
    </div>
    <div id="errMsg" style="margin-top:15px;"></div>

    <div id="runnersweek" style="width:920px; height:400px; margin-top:15px;"></div>
    <div id="mobileTeamChart" aria-label="סיכום קילומטרז׳ קבוצתי"></div>
    </section>

    <section id="runner_statistics_section" class="statistics_section">
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
    <div id="mobileRunnerWeeks" aria-label="סיכום שבועי לרץ"></div>

    <div class="mobile_runner_filters">
        <select id="mobileRunnerTypeFilter" aria-label="סוג אימון"></select>
        <div class="mobile_runner_distance_row">
            <input type="number" id="mobileRunnerMinDistance" min="0" step="0.1" placeholder="מרחק מינימלי" aria-label="מרחק מינימלי">
            <input type="number" id="mobileRunnerMaxDistance" min="0" step="0.1" placeholder="מרחק מרבי" aria-label="מרחק מרבי">
        </div>
    </div>

    <div id="dashboard" style="width:1020px; min-height:400px; margin-top:80px;">
        <div>
            <div id="control1" style="float:right;"></div>
            <div></div>
            <div id="control2" style="float:right; margin-right: 40px"></div>
        </div>
        <div id="report" style="width:100%"></div>
    </div>
    <div id="mobileRunnerReport" aria-label="אימוני הרץ"></div>
    
    <div style="clear:both;"></div>

    </section>

</div>
<script>
$(function () {
    $('#mobileRunnerTypeFilter, #mobileRunnerMinDistance, #mobileRunnerMaxDistance').on('input change', function () {
        renderMobileRunnerReport(window.mobileRunnerReportRows || []);
    });
});
</script>
</body>

