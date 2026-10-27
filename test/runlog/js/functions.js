/* General functions.js */

// Global - Ajax error handler
$.ajaxSetup({
    "error":function (jqXHR, textStatus, errorThrown) {
        console.log("error " + textStatus);
        console.log("errorThrown " + errorThrown);
        console.log("incoming Text " + jqXHR.responseText);
    }
});

var SaveHelper = {
    failMessage:function (prefix, xhr) {
        var reason = 'שגיאת תקשורת עם השרת';
        if (xhr && (xhr.status == 413 || xhr.status == 414)) {
            reason = 'הטקסט ארוך מדי';
        } else if (xhr && xhr.status) {
            reason = 'שגיאת שרת (' + xhr.status + ')';
        }
        return prefix + ': ' + reason + '. הנתונים לא נשמרו.';
    }
};

var Calendar = {

    // initialize the calendar
    init:function () {
        $('#calendar').fullCalendar({
            header:{
                left:'next,prev today customDate',
                center:'',
                right:'title'
            },
            editable:false,
            lazyFetching:false,
            events:function (start, end, callback) {
                Calendar.fetchEvents(start, end, callback);
            },
            eventClick:function (calEvent, jsEvent, view) {
                Calendar.handleClickEvent(calEvent, jsEvent, view);
            },
            dayClick:function (date, allDay, jsEvent, view) {
                Calendar.handleClickDay(date, allDay, jsEvent, view);
            }
        });

        EventDialog.init();
        Calendar.initMobileAgenda();
    },

    initMobileAgenda:function () {
        if (!window.matchMedia || !window.matchMedia('(max-width: 767px)').matches) {
            return;
        }

        var today = new Date();
        $('#mobileAgendaDatePicker').val(Calendar.mobileDateValue(today));

        $('#mobileAgendaAdd').click(function () {
            var selectedDate = $('#mobileAgendaDatePicker').val();
            var date = selectedDate ? Calendar.mobileDateToDate(selectedDate) : today;
            Calendar.handleClickDay(date, true, null, null);
        });

        Calendar.mobileMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        $('#mobileAgendaPrev').click(function () {
            Calendar.shiftMobileMonth(-1);
        });
        $('#mobileAgendaNext').click(function () {
            Calendar.shiftMobileMonth(1);
        });
        $('#mobileAgendaMonth').change(function () {
            var parts = $(this).val().split('-');
            if (parts.length != 2) {
                return;
            }
            Calendar.mobileMonth = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, 1);
            Calendar.refreshMobileAgenda();
        });

        Calendar.refreshMobileAgenda();
    },

    shiftMobileMonth:function (delta) {
        var month = Calendar.mobileMonth;
        Calendar.mobileMonth = new Date(month.getFullYear(), month.getMonth() + delta, 1);
        Calendar.refreshMobileAgenda();
    },

    refreshMobileAgenda:function () {
        if (!window.matchMedia || !window.matchMedia('(max-width: 767px)').matches || !Calendar.mobileMonth) {
            return;
        }

        var start = new Date(Calendar.mobileMonth.getTime());
        var end = new Date(start.getFullYear(), start.getMonth() + 1, 1);
        var monthValue = start.getFullYear() + '-' + (start.getMonth() < 9 ? '0' : '') + (start.getMonth() + 1);
        $('#mobileAgendaMonth').val(monthValue);

        Calendar.fetchMobileEvents(start, end);
    },

    fetchMobileEvents:function (start, end) {
        if (!Calendar.hasValidDateRange(start, end)) {
            $('#mobileAgendaList').html('<p class="mobileAgendaState">טעינת האימונים נכשלה</p>');
            return;
        }

        var memberIdForAgenda = $('#users').val();
        if (!memberIdForAgenda) {
            $('#mobileAgendaList').html('<p class="mobileAgendaState">טוען את האימונים...</p>');
            return;
        }

        $('#mobileAgendaList').html('<p class="mobileAgendaState">טוען את האימונים...</p>');
        $.ajax({
            url:'php/get_events.php',
            dataType:'text',
            data:{
                start:Math.round(start.getTime() / 1000),
                end:Math.round(end.getTime() / 1000),
                member_id:memberIdForAgenda
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    $('#mobileAgendaList').html('<p class="mobileAgendaState">טעינת האימונים נכשלה</p>');
                    return;
                }
                Calendar.renderMobileAgenda(doc.data || []);
            },
            error:function () {
                $('#mobileAgendaList').html('<p class="mobileAgendaState">טעינת האימונים נכשלה</p>');
            }
        });
    },

    renderMobileAgenda:function (events) {
        var agenda = $('#mobileAgendaList');
        agenda.empty();

        if (!events.length) {
            agenda.html('<p class="mobileAgendaEmpty">אין אימונים להצגה</p>');
            return;
        }

        var currentDate = '';
        var renderedEvents = 0;
        $.each(events, function (index, item) {
            if (!item.start) {
                return;
            }
            var eventDate = new Date(item.start.replace(' ', 'T'));
            if (isNaN(eventDate.getTime())) {
                return;
            }
            var dateKey = eventDate.getFullYear() + '-' + eventDate.getMonth() + '-' + eventDate.getDate();
            if (dateKey != currentDate) {
                currentDate = dateKey;
                var dateHeader = $('<div class="mobileAgendaDate"></div>');
                dateHeader.append($('<h3></h3>').text(Calendar.formatMobileDate(eventDate)));
                var addButton = $('<button type="button" class="mobileAgendaDateAdd">הוספה</button>');
                addButton.click(function () {
                    Calendar.handleClickDay(eventDate, true, null, null);
                });
                dateHeader.append(addButton);
                agenda.append(dateHeader);
            }

            var mobileEvent = {
                event_id:item.id,
                title:item.type,
                start:item.start,
                member_id:$('#users').val(),
                color:'#ffffff',
                borderColor:'#1f6f78'
            };
            if (EVENT_TYPES_ATTRIBUTES[item.run_type_id]) {
                mobileEvent.color = Calendar.getEventColor(item.run_type_id);
                mobileEvent.borderColor = Calendar.getEventBorderColor(item.run_type_id);
            }
            var mobileSummary = Calendar.getMobileEventSummary(item);
            var eventButton = $('<button type="button" class="mobileAgendaEvent"></button>');
            eventButton.css({'background-color': mobileEvent.color, 'border-right-color': mobileEvent.borderColor});
            eventButton.append($('<span class="mobileAgendaEventTitle"></span>').text(mobileSummary.title));
            if (mobileSummary.meta) {
                eventButton.append($('<span class="mobileAgendaEventMeta"></span>').text(mobileSummary.meta));
            }
            if (mobileSummary.notes) {
                eventButton.append($('<span class="mobileAgendaEventNotes"></span>').text(mobileSummary.notes));
            }
            eventButton.click(function () {
                Calendar.handleClickEvent(mobileEvent, null, null);
            });
            agenda.append(eventButton);
            renderedEvents++;
        });

        if (!renderedEvents) {
            agenda.html('<p class="mobileAgendaEmpty">אין אימונים להצגה</p>');
        }
    },

    formatMobileDate:function (date) {
        var days = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
        var day = date.getDate();
        var month = date.getMonth() + 1;
        return days[date.getDay()] + ', ' + day + '/' + month;
    },

    mobileDateToDate:function (value) {
        var parts = value.split('-');
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    },

    mobileDateValue:function (date) {
        var month = date.getMonth() + 1;
        var day = date.getDate();
        return date.getFullYear() + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
    },

    getMobileEventSummary:function (event) {
        var distance = EventFormatter.getTotalDistance(event.warmup_distance, event.run_distance, event.cooldown_distance, event.run_type_id);
        var distanceAndPace = EventFormatter.getRunDistanceAndPace(event.warmup_distance, event.run_distance, event.cooldown_distance, event.run_time, event.run_type_id);
        var metaParts = [];

        if (distance != null) {
            metaParts.push(distance);
        }
        if (distanceAndPace != null && distanceAndPace !== '') {
            metaParts.push(distanceAndPace);
        }

        return {
            title:event.type,
            meta:metaParts.join(' '),
            notes:event.notes || ''
        };
    },

    init2:function () {
        $('#calendar').fullCalendar({
            header:{
                left:'next,prev today customDate',
                center:'',
                right:'title'
            },
            defaultView: 'basicWeek',
            editable:false,
            lazyFetching:false,
            events:function (start, end, callback) {
                Calendar.fetchEvents2(start, end, callback);
            }
        });
    },

    hasValidDateRange:function (start, end) {
        return !!start && !!end && start instanceof Date && end instanceof Date && !isNaN(start.getTime()) && !isNaN(end.getTime());
    },

    // Fetch events from the server
    fetchEvents:function (start, end, callback) {
        if (!Calendar.hasValidDateRange(start, end)) {
            if (callback) {
                callback([]);
            }
            return;
        }

        var the_member_id = $("#users").val();
        $.ajax({
            url:'php/get_events.php',
            dataType:'text',
            data:{
                start:Math.round(start.getTime() / 1000),
                end:Math.round(end.getTime() / 1000),
                member_id:the_member_id
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Fetch event failed - " + doc.status.emessage);
                } else {
                    var events = [];
                    $.each(doc.data, function (index, item) {
                        events.push({
                            event_id:item.id,
                            title:Calendar.getEventTitle(item),
                            start:item.start,
                            member_id:the_member_id,
                            color:Calendar.getEventColor(item.run_type_id),
                            borderColor:Calendar.getEventBorderColor(item.run_type_id)
                        });
                    });
                    callback(events);
                }
            }
        });
    },

    // Fetch events from the server
    fetchEvents2:function (start, end, callback) {
        if (!Calendar.hasValidDateRange(start, end)) {
            if (callback) {
                callback([]);
            }
            return;
        }

        $.ajax({
            url:'php/get_team_events.php',
            dataType:'text',
            data:{
                start:Math.round(start.getTime() / 1000),
                end:Math.round(end.getTime() / 1000)
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Fetch event failed - " + doc.status.emessage);
                } else {
                    var events = [];
                    $.each(doc.data, function (index, item) {
                        events.push({
                            event_id:item.id,
                            title:Calendar.getEventTitle(item),
                            start:item.start,
                            member_id:the_member_id,
                            color:Calendar.getEventColor(item.run_type_id),
                            borderColor:Calendar.getEventBorderColor(item.run_type_id)
                        });
                    });
                    callback(events);
                }
            }
        });
    },

    // handle clicking on 'event'
    handleClickEvent:function (calEvent, jsEvent, view) {

        if ($('#users').val() != memberId) {
            // member is looking at another runner's log - no permission to edit or delete an event
            return;
        }

        EventDialog.reset();
        $('#create_update_event_dialog').dialog({
            height:550,
            width:500,
            dialogClass:'mobileWorkoutDialog',
            show:{
                effect:'drop',
                direction:'rtl'
            },
            buttons:{
                "אשר":function () {
                    var event_fields_data = EventDialog.getData();
                    if (event_fields_data != null) {
                        // passed validation
                        var $eventDialog = $(this);
                        event_fields_data = Calendar.appendIdentityFields(event_fields_data, calEvent);
                        var event_fields_str = JSON.stringify(event_fields_data);
                        $.ajax({
                            url:'php/update_event.php',
                            type:'POST',
                            dataType:'text',
                            data:{
                                event_fields:event_fields_str
                            },
                            success:function (txt) {
                                var doc = Utils.parseJSON(txt);
                                if (doc.status.ecode == STATUS_ERR) {
                                    alert("Update failed: "
                                        + doc.status.emessage);
                                } else {
                                    $eventDialog.dialog("close");
                                    $('#calendar').fullCalendar('refetchEvents');
                                    Calendar.refreshMobileAgenda();
                                }
                            },
                            error:function (xhr) {
                                alert(SaveHelper.failMessage('העדכון נכשל', xhr));
                            }
                        });
                    }
                },
                "מחק":function () {
                    $("#delete_dialog").dialog({
                        buttons:{
                            "אשר":function () {
                                $.ajax({
                                    url:'php/delete_event.php',
                                    dataType:'text',
                                    data:{
                                        event_id:calEvent.event_id,
                                        member_id:calEvent.member_id
                                    },
                                    success:function (txt) {
                                        var doc = Utils.parseJSON(txt);
                                        if (doc.status.ecode == STATUS_ERR) {
                                            alert("Deletion failed: "
                                                + doc.emessage);
                                        } else {
                                            $('#calendar').fullCalendar('refetchEvents');
                                            Calendar.refreshMobileAgenda();
                                        }
                                    }
                                });
                                $('#create_update_event_dialog').dialog('close');
                                $(this).dialog("close");
                            },
                            "סגור":function () {
                                $(this).dialog("close");
                            }
                        }
                    });
                    $('#delete_dialog').css('background-color', '#feffe5');
                    $("#delete_dialog").dialog("open");
                },
                "סגור":function () {
                    $(this).dialog("close");
                }
            }
        });
        $('#create_update_event_dialog').css('background-color', '#feffe5');
        EventDialog.setData(calEvent);
        $("#create_update_event_dialog").dialog("open").parent('.ui-dialog').addClass('mobileWorkoutDialog');
    },

    // Handle clicking on the calendar cell (not on the event)
    handleClickDay:function (date, allDay, jsEvent, view) {

        if ($('#users').val() != memberId) {
            // member is looking at another runner's log - no permission to add an event
            return;
        }

        var the_date = Time.hebDateToSqlDate(Time.jsDateToHebDate(date));

        EventDialog.reset();
        $('#create_update_event_dialog').dialog(
            {
                height:550,
                width:500,
                dialogClass:'mobileWorkoutDialog',
                show:{
                    effect:'drop',
                    direction:'rtl'
                },
                buttons:{
                    "אשר":function () {
                        // assuming we passes validation ..
                        var event_fields_data = EventDialog.getData();
                        if (event_fields_data != null) {
                            // passed validation
                            var $eventDialog = $(this);
                            event_fields_data.date = the_date;
                            var event_fields_str = JSON.stringify(event_fields_data);
                            $.ajax({
                                url:'php/create_event.php',
                                type:'POST',
                                dataType:'text',
                                data:{
                                    event_fields:event_fields_str
                                },
                                success:function (txt) {
                                    var doc = Utils.parseJSON(txt);
                                    if (doc.status.ecode == STATUS_ERR) {
                                        alert("Create failed: "
                                            + doc.status.emessage);
                                    } else {
                                        $eventDialog.dialog("close");
                                        $('#calendar').fullCalendar('refetchEvents');
                                        Calendar.refreshMobileAgenda();
                                    }
                                },
                                error:function (xhr) {
                                    alert(SaveHelper.failMessage('היצירה נכשלה', xhr));
                                }
                            });
                        }
                    },
                    "סגור":function () {
                        $(this).dialog("close");
                    }
                }
            });
        $('#create_update_event_dialog').css('background-color', '#feffe5');
        $("#create_update_event_dialog").dialog("open").parent('.ui-dialog').addClass('mobileWorkoutDialog');
    },

    appendIdentityFields:function (eventFields, calEvent) {
        eventFields.event_id = calEvent.event_id;
        eventFields.member_id = calEvent.member_id;
        return eventFields;
    },

    getEventTitle:function (event) {
        var eventType = event.type;
        var eventTotalDistance = EventFormatter.getTotalDistance(event.warmup_distance, event.run_distance, event.cooldown_distance, event.run_type_id);
        var runDistanceAndPace = EventFormatter.getRunDistanceAndPace(event.warmup_distance, event.run_distance, event.cooldown_distance, event.run_time, event.run_type_id)
        var notes = event.notes;

        var eventTitle = '<span class="runlogEventTitle">' + eventType + '</span>';
        if (eventTotalDistance != null) {
            eventTitle += '<br>' + eventTotalDistance;
        }
        if (runDistanceAndPace != null) {
            eventTitle += ' ' + runDistanceAndPace;
        }
        if (notes != null && notes != '') {
            if (eventTotalDistance != null || runDistanceAndPace != null) {
                eventTitle += '<div class="runlogEventSeparator"></div>';
            }
            else {
                eventTitle += '<br>';
            }
            eventTitle += Utils.htmlEscape(notes);
        }

        return eventTitle;
    },

    /**
     * Based on the event type (recovery run,long run,etc) return a color
     * TODO: use user profile to get custom colors
     */
    getEventColor:function (eventType) {
        return EVENT_TYPES_ATTRIBUTES[eventType].getColor();
    },

    /**
     * Based on the event type (recovery run,long run,etc) return a the border color of the event box
     */
    getEventBorderColor:function (eventType) {
        return EVENT_TYPES_ATTRIBUTES[eventType].getBorderColor();
    },

    getFeedEventHtml: function(event) {

        var runnerName = event.name;
		var pulse = event.pulse;
		var max_pulse = event.max_pulse;
		var elevation = event.elevation;
        var rpe = event.rpe;
  		var weight = event.weight;
		var run_time = event.HourAndMinutes;
        var eventTypeName = event.type;
        var eventTotalDistance = EventFormatter.getTotalDistance(event.warmup_distance, event.run_distance, event.cooldown_distance, event.run_type_id);
        var runDistanceAndPace = EventFormatter.getRunDistanceAndPace(event.warmup_distance, event.run_distance, event.cooldown_distance, event.run_time, event.run_type_id)
        var notes = event.notes;

        var html = '';
        html += "<div style=\"font-weight:bold;\">";
        html += "<span>" + runnerName + ", </span>";
        html += "<span>" + eventTypeName + "</span>";
        if (eventTotalDistance != null)
        {
            html += ": <span>"+eventTotalDistance+"</span>";
        }
        
        if (pulse != 0 && pulse != null)
        {
            html += " - דופק: <span>"+ pulse+"</span>  ";
        }
        if (elevation != 0 && elevation != null)
        {
            html += " - טיפוס: <span>"+ elevation+" מ'</span>  ";
        }
        if (rpe != null)
        {
            html += " - מאמץ: <span>"+ rpe+"</span>  ";
        }
        if (weight != 0 && weight != null)
        {
             html += " - משקל: <span>"+ weight+" קג'</span>  ";
         }
        
        html += "</div>";
        
        if (runDistanceAndPace != null)
        {
            html += "<div><span>"+runDistanceAndPace+"</span></div>";
        }

		if (run_time != "00:00")
        {
    		html += "<div style=\"font-weight:bold;\">";
    		html += "<div><span> שעה: <span>"+run_time+"</span></div>";
		}

        html += "<div style=\"font-weight:normal;\">";
        if (notes != '')
        {
            html += "<div><span>"+notes+"</span></div>";
        }

        html += "</div>";
        if (run_time != "00:00")
        {
            html += "</div>";
        }
        html += "</div>";

        return html;
    }
}

var EventFormatter = {
    getTotalDistance:function (warmupDistance, runDistance, cooldownDistance, runTypeId) {

        var runDistanceNum = parseFloat(runDistance);
        if (isNaN(runDistanceNum) || runDistanceNum <= 0 || runTypeId == EventTypes.OTHER_SPORT || runTypeId == EventTypes.REST_DAY || runTypeId == EventTypes.EVENT_CANCELED || runTypeId == EventTypes.POWER) {
            return null;
        }
        else {
            return (runDistanceNum).toFixed(1) + ' ק"מ';
        }
    },

    getRunDistanceAndPace:function (warmupDistance, runDistance, cooldownDistance, runTime, runTypeId) {

        if (runTypeId == EventTypes.OTHER_SPORT || runTypeId == EventTypes.REST_DAY || runTypeId == EventTypes.EVENT_CANCELED || runTypeId == EventTypes.POWER) {
            // no meaning for run pace in these cases
            return null;
        }

        var runDistanceNum = parseFloat(runDistance);
        if (isNaN(runDistanceNum)) {
            runDistanceNum = 0;
        }

        var runTimeNum = parseInt(runTime, 10);
        if (isNaN(runTimeNum)) {
            runTimeNum = 0;
        }

        if (runDistanceNum <= 0 && runTimeNum <= 0) {
            return null;
        }

        var warmupDistanceNum = parseFloat(warmupDistance);
        if (isNaN(warmupDistanceNum)) {
            warmupDistanceNum = 0;
        }

        var cooldownDistanceNum = parseFloat(cooldownDistance);
        if (isNaN(cooldownDistanceNum)) {
            cooldownDistanceNum = 0;
        }

        var runTimeFormatted = null;
        if (runTimeNum >= 3600) {
            runTimeFormatted = Time.convertSecondsToHMMSS(runTimeNum);
        }
        else if (runTimeNum > 0) {
            runTimeFormatted = Time.convertSecondsToMMSS(runTimeNum);
        }

        var runPace = Time.calculatePace(runDistanceNum, runTimeNum);
        var warmupOrCooldown = (warmupDistanceNum > 0 || cooldownDistanceNum > 0);
        var totalRunDistanceFormatted = '';
        if (warmupOrCooldown) {
            totalRunDistanceFormatted = (warmupDistanceNum + runDistanceNum + cooldownDistanceNum).toFixed(1) + ' ק"מ';
        }

        var runDistanceAndPace = '';
        if (runTimeFormatted != null) {
            runDistanceAndPace += ' ב- ' + runTimeFormatted;
            if (runPace != null) {
                runDistanceAndPace += ' (' + runPace + ')';
            }

            if (warmupOrCooldown) {
                runDistanceAndPace += ' מתוך סה״כ ' + totalRunDistanceFormatted;
            }
        }
        else if (warmupOrCooldown) {
            runDistanceAndPace += ' מתוך סה״כ ' + totalRunDistanceFormatted;
        }

        return runDistanceAndPace;
    }
};

var EventDialog = {

    // initializes the event dialog with default values and fills the shoes and courses <select>s
    // with values appropriate to the current user
    init:function () {
        EventDialog.initRunTypes();
        EventDialog.initRPE();
        EventDialog.initShoesAndCourses();
        $('#rpe').change(EventDialog.RPEChanged);
        $('#courseSelect').change(EventDialog.courseChanged);
        $('#raceSelect').change(EventDialog.raceChanged);
        EventDialog.shoesDetached = false;
        $('#shoeSelect').change(EventDialog.shoeChanged);
        $('#extraShoeSelect').change(EventDialog.extraShoeChanged);

        // handler for distance, duration change event in order to update pace
        $('#main_run input').blur(EventDialog.calculatePace);
        $('#run_time_hours, #run_time_minutes, #run_time_seconds').blur(EventDialog.calculatePace);
        EventDialog.bindEditableNumericFields();
        $('#pulse').val('0');
		$('#max_pulse').val('0');
		$('#elevation').val('0');
    },

    // resets the event dialog to its default values
    reset:function () {
        SelectUtils.makeSelection('run_types', EventTypes.RECOVERY_RUN);
        $('#run_types').trigger('change');
        SelectUtils.makeSelection('rpe', RPE.EASY1);
        SelectUtils.resetSelect('courseSelect');
        SelectUtils.resetSelect('raceSelect');
        SelectUtils.resetSelect('shoeSelect');
        $('#shoeSelect').show();
        $('#inactive_shoe').hide();
        SelectUtils.resetSelect('extraShoeSelect');
        $('#extraShoeSelect').show();
        $('#inactive_extra_shoe').hide();
        EventDialog.shoesDetached = false;

        $('#run_distance').val('0');
        EventDialog.setRunTime(0);
        $('#run_pace').text('');
        $('#extra_run_distance').val('0');

        $('#notesContainer').empty().append('<textarea id="notes" maxlength="20000"></textarea>');
        $('#notes').val('').elastic();
		$('#pulse').val('0');
		$('#max_pulse').val('0');
		$('#elevation').val('0');
		$('#weight').val('0');
    },

    bindEditableNumericFields:function () {
        $('#run_distance, #extra_run_distance, #pulse, #max_pulse, #elevation, #weight, #run_time')
            .on('focus', function () {
                this.select();
            })
            .on('blur', function () {
                if (this.value === '') {
                    this.value = '0';
                }
                if (this.value.indexOf('.') === 0) {
                    this.value = '0' + this.value;
                }
            });

        $('#run_time_hours, #run_time_minutes, #run_time_seconds')
            .on('focus', function () {
                this.select();
            })
            .on('blur', function () {
                var value = parseInt(this.value, 10);
                if (isNaN(value)) {
                    value = 0;
                }

                if (this.id === 'run_time_hours') {
                    value = Math.max(0, Math.min(9, value));
                    this.value = value;
                } else {
                    value = Math.max(0, Math.min(59, value));
                    this.value = value < 10 ? '0' + value : String(value);
                }
            });
    },

    getRunTime:function () {
        if ($('#run_time_hours').length > 0) {
            var hours = parseInt($('#run_time_hours').val(), 10);
            var minutes = parseInt($('#run_time_minutes').val(), 10);
            var seconds = parseInt($('#run_time_seconds').val(), 10);

            if (isNaN(hours)) {
                hours = 0;
            }
            if (isNaN(minutes)) {
                minutes = 0;
            }
            if (isNaN(seconds)) {
                seconds = 0;
            }

            hours = Math.max(0, Math.min(9, hours));
            minutes = Math.max(0, Math.min(59, minutes));
            seconds = Math.max(0, Math.min(59, seconds));

            return hours + ':' + Time.paddWithZero(minutes) + ':' + Time.paddWithZero(seconds);
        } else if ($('#run_time').length > 0) {
            var raw = $.trim($('#run_time').val() || '');
            if (!raw) {
                return '0:00:00';
            }
            var parts = raw.split(':');
            if (parts.length === 1) {
                var mins = parseInt(parts[0], 10);
                if (isNaN(mins)) return '0:00:00';
                var h = Math.floor(mins / 60);
                var m = mins % 60;
                return Math.min(9, h) + ':' + Time.paddWithZero(m) + ':00';
            } else if (parts.length === 2) {
                var mins2 = parseInt(parts[0], 10) || 0;
                var secs2 = parseInt(parts[1], 10) || 0;
                var h2 = Math.floor(mins2 / 60);
                var m2 = mins2 % 60;
                return Math.min(9, h2) + ':' + Time.paddWithZero(m2) + ':' + Time.paddWithZero(Math.min(59, secs2));
            } else if (parts.length >= 3) {
                var h3 = Math.max(0, Math.min(9, parseInt(parts[0], 10) || 0));
                var m3 = Math.max(0, Math.min(59, parseInt(parts[1], 10) || 0));
                var s3 = Math.max(0, Math.min(59, parseInt(parts[2], 10) || 0));
                return h3 + ':' + Time.paddWithZero(m3) + ':' + Time.paddWithZero(s3);
            }
        }
        return '0:00:00';
    },

    setRunTime:function (totalSeconds) {
        totalSeconds = parseInt(totalSeconds, 10);
        if (isNaN(totalSeconds) || totalSeconds < 0) {
            totalSeconds = 0;
        }

        var hours = Math.floor(totalSeconds / 3600);
        var remainder = totalSeconds % 3600;
        var minutes = Math.floor(remainder / 60);
        var seconds = remainder % 60;

        if ($('#run_time_hours').length > 0) {
            $('#run_time_hours').val(hours);
            $('#run_time_minutes').val(minutes < 10 ? '0' + minutes : minutes);
            $('#run_time_seconds').val(seconds < 10 ? '0' + seconds : seconds);
        }
        if ($('#run_time').length > 0) {
            $('#run_time').val(Time.convertSecondsToHMMSS(totalSeconds));
        }
    },

    formatDecimalInput:function (value) {
        var number = parseFloat(value);
        return isNaN(number) ? '0' : String(number);
    },

    // populate the run types <select>
    initRunTypes:function () {
        var select = document.getElementById("run_types");
        for (var key in EventTypes) {
            var value = EventTypes[key];
            var label = EVENT_TYPES_ATTRIBUTES[value].getLabel();
            var option = new Option(label, value);
            select.options[select.options.length] = option;
        }

        // handler for the run types combo box change
        $('#run_types').change(EventDialog.runTypeChanged);
    },

        // populate the RPE <select>
        initRPE:function () {
            var select = document.getElementById("rpe");
            for (var key in RPE) {
                var value = RPE[key];
                var label = RPE_ATTRIBUTES[value].getLabel();
                var option = new Option(label, value);
                select.options[select.options.length] = option;
            }
    
            // handler for the run types combo box change
            $('#rpe').val();
        },

    // populate a <select> with values + a pre defined select prompt (applies for shoes and courses)
    initSelect:function (controlId, data, selectPrompt, controlContainer, extraDataFieldName) {

        SelectUtils.resetSelect(controlId, true);

        if (data.length > 0) {
            SelectUtils.addOptionAtTopAndSelectIt(controlId, selectPrompt, NOT_SELECTED, false);
            SelectUtils.populateSelect(controlId, data, extraDataFieldName);

            $(controlContainer).show();
        } else {
            $(controlContainer).hide();

        }
    },

    // populate the shoes and courses <select>
    initShoesAndCourses:function () {
        var the_member_id = $("#users").val();
        $.ajax({
            url:'php/get_shoes_and_courses.php',
            dataType:'text',
            data:{
                member_id:the_member_id
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Fetch shoes and courses failed: " + doc.status.emessage);
                } else {
                    EventDialog.initSelect('shoeSelect', doc.data.shoes, SELECT_SHOE_PROMPT, '.user_shoes');
                    EventDialog.initSelect('extraShoeSelect', doc.data.shoes, SELECT_SHOE_PROMPT, '.user_shoes');
                    EventDialog.initSelect('courseSelect', doc.data.courses, SELECT_COURSE_PROMPT, '#user_courses', 'length');
                    EventDialog.initSelect('raceSelect', doc.data.races, SELECT_RACE_PROMPT, '#races');
                }
            }
        });
    },

    // sets the given item as the selected option one in the given <select>
    // if the given item doesn't exist in the <select>, its name is displayed instead in a <span>
    setSelectedItemOrShowLabel:function (selectId, itemId, itemText, inactive) {
        SelectUtils.resetSelect(selectId);

        if (itemId == null) {
            // no shoe is selected
            return;
        }

        var itemSelected = SelectUtils.makeSelection(selectId, itemId);

        if (!itemSelected) {
            // there is no match but there is a valid itemText
            if (itemText != null) {
                $('#' + selectId).hide();
                $('#' + inactive).text(itemText).show();
            } else {
                $('#' + inactive).hide();
                $('#' + selectId).hide();
            }
        } else {
            // we have a match
            $('#' + inactive).hide();
            $('#' + selectId).show();
        }
    },

    validateDistance:function (fieldName, fieldValue, minValue, maxValue) {
        if (fieldValue == null || parseFloat(fieldValue) != fieldValue) {
            alert("מרחק " + fieldName + " לא תקין");
            return false;
        }

        if (fieldValue < minValue) {
            alert("מרחק " + fieldName + " חייב להיות לפחות " + minValue);
            return false;
        }

        if (fieldValue > maxValue) {
            alert("מרחק " + fieldName + " חייב להיות עד " + maxValue);
            return false;
        }

        return true;
    },

    validateDuration:function (fieldName, fieldValue, minValue, maxValue, conversionFunction) {
        if (fieldValue == null) {
            alert("זמן " + fieldName + " לא תקין");
            return false;
        }

        if (fieldValue < minValue) {
            alert("זמן " + fieldName + " חייב להיות לפחות " + conversionFunction(minValue));
            return false;
        }

        if (fieldValue > maxValue) {
            alert("זמן " + fieldName + " חייב להיות עד " + conversionFunction(maxValue));
            return false;
        }

        return true;
    },

    // Collect the event fields into single object
    getData:function () {
        var eventFields = new Object();
        eventFields.run_distance = $('#run_distance').val();
        if (!EventDialog.validateDistance("תרגיל", eventFields.run_distance, MIN_RUN_DISTANCE, MAX_RUN_DISTANCE)) {
            return null;
        }
        eventFields.run_time = Time.convertHMMSSToSeconds(EventDialog.getRunTime());
        if (!EventDialog.validateDuration("תרגיל", eventFields.run_time, MIN_RUN_TIME, MAX_RUN_TIME, Time.convertSecondsToHMMSS)) {
            return null;
        }
        eventFields.extra_run_distance = $('#extra_run_distance').val();
        if (!EventDialog.validateDistance("תוספת", eventFields.extra_run_distance, MIN_EXTRA_DISTANCE, MAX_EXTRA_DISTANCE)) {
            return null;
        }
        var runType = $('#run_types').val();
        if (runType == EventTypes.OTHER_SPORT || runType == EventTypes.REST_DAY || runType == EventTypes.EVENT_CANCELED || runType == EventTypes.POWER) {
            eventFields.run_distance = 0;
            eventFields.run_time = 0;
            eventFields.extra_run_distance = 0;
            eventFields.course_id = NOT_SELECTED;
            eventFields.shoe_id = NOT_SELECTED;
            eventFields.extra_shoe_id = NOT_SELECTED;
        } else {
            eventFields.course_id = $('#courseSelect').val();
            eventFields.shoe_id = $('#shoeSelect').val();
            eventFields.extra_shoe_id = $('#extraShoeSelect').val();
            if (runType == EventTypes.RECOVERY_RUN || runType == EventTypes.TRAIL_RUN) {
                eventFields.extra_run_distance = 0;
            }
            if (eventFields.extra_run_distance == 0) {
                eventFields.extra_shoe_id = NOT_SELECTED;
            }
        }
        eventFields.run_type_id = $('#run_types').val();
        eventFields.notes = $('#notes').val();
        eventFields.runner_id = $('#users').val();
		eventFields.pulse = $('#pulse').val();
		eventFields.max_pulse = $('#max_pulse').val();
		eventFields.elevation = $('#elevation').val();
 		eventFields.weight = $('#weight').val();
        eventFields.rpe = $('#rpe').val();
        if (runType == EventTypes.RACE_RUN ){
            eventFields.race_id = $('#raceSelect').val();        
        }
        else{
            eventFields.race_id = NOT_SELECTED;
        }
 		
        return eventFields;
    },

    // set the data and populates relevant fields according to the given calendar event
    setData:function (calEvent) {
        $.ajax({
            url:'php/get_event_details.php',
            dataType:'text',
            data:{
                event_id:calEvent.event_id,
                member_id:calEvent.member_id
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Get event details failed: " + doc.status.emessage);
                } else {
                    SelectUtils.makeSelection('run_types', doc.data.selected_run_type.id);
                    if (doc.data.selected_course == null) {
                        doc.data.selected_course = 0;
                    }
                    SelectUtils.makeSelection('rpe', doc.data.selected_rpe.id);
					EventDialog.setSelectedItemOrShowLabel('rpe', doc.data.selected_rpe.id, doc.data.selected_rpe.description, 'inactive_rpe');
                    EventDialog.setSelectedItemOrShowLabel('courseSelect', doc.data.selected_course.id, doc.data.selected_course.course_name, 'inactive_course');
                    EventDialog.setSelectedItemOrShowLabel('raceSelect', doc.data.selected_race.id, doc.data.selected_race.race_name, 'inactive_race');
                    EventDialog.setSelectedItemOrShowLabel('shoeSelect', doc.data.selected_shoe.id, doc.data.selected_shoe.name, 'inactive_shoe');
                    EventDialog.setSelectedItemOrShowLabel('extraShoeSelect', doc.data.selected_extra_shoe.id, doc.data.selected_extra_shoe.name, 'inactive_extra_shoe');
                    EventDialog.shoesDetached = ($('#shoeSelect').val() != $('#extraShoeSelect').val());
                    $('#run_types').trigger('change');

                    var fields = doc.data.event_fields;
                    $('#run_distance').val(EventDialog.formatDecimalInput(fields.run_distance));
                    EventDialog.setRunTime(fields.run_time);
                    $('#extra_run_distance').val(EventDialog.formatDecimalInput(fields.extra_distance));
                    $('#notesContainer').empty().append('<textarea id="notes" maxlength="20000"></textarea>');
                    $('#notes').val(fields.notes).elastic();
					$('#pulse').val(fields.pulse);
					$('#max_pulse').val(fields.max_pulse);
					$('#elevation').val(fields.elevation);
                    $('#weight').val(EventDialog.formatDecimalInput(fields.weight));
                    EventDialog.calculatePace();
                }
            }
        });
    },

    /**
     * Hides the distance-duration-pace table when run type is 'other sport', shows it otherwise
     */
    runTypeChanged:function () {
        var runType = $('#run_types').val();
        if (runType == EventTypes.OTHER_SPORT || runType == EventTypes.REST_DAY || runType == EventTypes.EVENT_CANCELED || runType == EventTypes.POWER) {
            $('#user_courses').hide();
            $('#duration_distance_pace').hide();
			$('#pulse_c').hide();
			$('#max_pulse_c').hide();
			$('#course_elev').hide();
			$('#weight_c').hide();
            $('#rpe_main').hide();
        }
        else {
            if ($('#user_courses option').length > 1) {
                $('#user_courses').show();
            }
            $('#duration_distance_pace').show();
			$('#pulse_c').show();
			$('#max_pulse_c').show();
			$('#course_elev').show();
			$('#weight_c').show();
            $('#rpe_main').show();

            if (runType == EventTypes.RECOVERY_RUN || runType == EventTypes.TRAIL_RUN) {
                $('#extra_run').hide();
            } else {
                $('#extra_run').show();
            }
            if (runType == EventTypes.RACE_RUN) {
                $('#race_main').show();
            } else {
                $('#race_main').hide();
            }
        }
    },

    courseChanged:function () {
        var courseLength = $('option:selected', $('#courseSelect')).attr('length');
        if (courseLength != null) {
            courseLength = parseFloat(courseLength);
            if (courseLength > 0 && $('#run_distance').val() == 0){
                $('#run_distance').val(EventDialog.formatDecimalInput(courseLength));
            }
        }
    },

    shoeChanged:function () {
        if (!EventDialog.shoesDetached) {
            $('#extraShoeSelect').val($('#shoeSelect').val());
        }
    },

    extraShoeChanged:function () {
        EventDialog.shoesDetached = true;
    },

    // calculates the pace values according to distance and duration and displays the values if calculation succeeds
    calculatePace:function () {
        var run_pace = Time.calculatePace($('#run_distance').val(), Time.convertHMMSSToSeconds(EventDialog.getRunTime()));
        $('#run_pace').html(run_pace);
    }
};

var Comments = {

    /**
     * appends the html for an event suitable for annotating with comments to the given container
     * @param event
     * @returns {string}
     */
    appendEvent: function ($container, event) {
        var $event = $('<div class="event"></div>');
        var $eventBody = $('<div class="event_body"></div>');
        var $eventAvatar = $('<div class="event_avatar" aria-hidden="true"></div>');
        var $eventType = $('<div class="event_type"></div>');
        var $eventTitle = $('<div class="event_title"></div>');
        var $comments = $('<div class="comments"></div>');
        var $commentList = $('<div class="list"></div>').attr('id', 'event_comments_' + event.id);
        var isMobileView = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
        var $commentInput = $('<textarea class="event_new_comment" maxlength="2000"></textarea>')
            .attr('placeholder', isMobileView ? '\u27A4' : 'הוספת פרגון...')
            .attr('id', 'event_new_comment_' + event.id);

        $eventType.css({
            'background-color':Calendar.getEventColor(event.run_type_id),
            'border':'solid 1px ' + Calendar.getEventBorderColor(event.run_type_id)
        });
        $eventTitle.html(Calendar.getFeedEventHtml(event));
        $eventBody.append($eventAvatar, $eventType, $eventTitle);
        $comments.append($commentList, $('<div></div>').append($commentInput));
        $event.append($eventBody, $comments);
        $container.append($event);

        $commentInput.elastic().keypress(Comments.onNewCommentKeyPress);
    },

    appendComment: function (comment, lastFetchedTimestamp) {
        var $eventComments = $('#event_comments_' + comment.event_id);
        if (!$eventComments.is(':empty')) {
            $eventComments.append('<div class="separator"></div>');
        }
        $eventComments.parent().addClass('comments_on');

        var commentCssClass = 'comment';
        if (lastFetchedTimestamp &&
            comment.timestamp &&
            (Time.sqlDateToJsDateTime(comment.timestamp).getTime() >=  Time.sqlDateToJsDateTime(lastFetchedTimestamp).getTime())){
            commentCssClass += ' new_comment';
        }
        else if ($eventComments.find('.comment:last').hasClass('new_comment')){
            commentCssClass += ' new_comment';
        }

        var commentHtml =
            '<div class="' + commentCssClass + '" id="event_comment_' + comment.comment_id + '">' +
                Comments.getCommentHtml(comment.event_id, comment.comment_id, comment.commenter_name, comment.comment)  +
                '</div>'
        $eventComments.append(commentHtml);
    },

    getCommentHtml: function(eventId, commentId, commenterName, comment) {
        var commentHtml =
            '<span class="comment_html"><b>' + commenterName + '</b>: <span id="event_comment_inner_' + commentId + '">' + comment + '</span>' +
                (commenterName == gMemberName ?
                    ' <a class="menu_btn" href="#" onclick="Comments.showMenu(\'' + eventId + '\', \'' + commentId + '\'); return false;">&or;</a>' :
                    '') +
                '</span>';

        return commentHtml;
    },

    onNewCommentKeyPress: function (e) {
        if (e.keyCode == 13 && !e.shiftKey) {
            Comments.createComment($(e.target));
            return false;
        }
    },

    createComment: function ($commentTextarea) {
        var eventId = $commentTextarea.attr('id').replace('event_new_comment_', '');
        var comment = $commentTextarea.val();

        if (comment) {
            var eventComment = {
                event_id: eventId,
                comment: comment
            };
            $.ajax({
                url: 'php/create_event_comment.php',
                type: 'POST',
                dataType: 'text',
                data: {
                    event_comment: JSON.stringify(eventComment)
                },
                success: function (txt) {
                    var doc = Utils.parseJSON(txt);
                    if (doc.status.ecode == STATUS_ERR) {
                        alert("Create failed: " + doc.status.emessage);
                    }
                    else {
                        Comments.appendComment({
                            event_id: eventId,
                            comment_id: doc.data.comment_id,
                            commenter_name: gMemberName,
                            comment: comment
                        });
                        $commentTextarea.val('');
                    }
                },
                error: function (xhr) {
                    alert(SaveHelper.failMessage('הוספת התגובה נכשלה', xhr));
                }
            });
        }
    },

    showMenu: function(eventId, commentId) {

        var commentMenu = $('#comment_menu');
        var $eventComments = $('#event_comments_' + eventId);
        var $comment = $eventComments.find('#event_comment_' + commentId);

        if ($comment.find(commentMenu).length == 0) {
            Comments.hideMenu();
            commentMenu.html(
                '<ul>' +
                    '	<li><a href="#" onclick="Comments.editComment(' + eventId + ', ' + commentId + '); return false;">עריכה</a></li>' +
                    '	<li><a href="#" onclick="Comments.removeComment(' + eventId + ', ' + commentId + '); return false;">מחיקה</a></li>' +
                    '</ul>');

            $comment.append(commentMenu);
            commentMenu.show();
        } else {
            Comments.hideMenu();
        }
    },

    hideMenu: function() {
        var commentMenu = $('#comment_menu');
        commentMenu.hide();
        $('.content').append(commentMenu);
    },

    onEditCommentKeyDowm: function (e) {
        if (e.keyCode == 13 && !e.shiftKey) {
            Comments.updateComment($(e.target));
            return false;
        } else if (e.keyCode == 27){
            Comments.undoUpdateComment($(e.target));
        }
    },

    removeComment: function(eventId, commentId) {
        $.ajax({
            url: 'php/delete_event_comment.php',
            dataType: 'text',
            data: {
                event_comment_id: commentId
            },
            success: function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Delete failed: " + doc.status.emessage);
                }
                else {
                    var $eventComments = $('#event_comments_' + eventId);
                    var $comment = $eventComments.find('#event_comment_' + commentId);
                    var $separator = $comment.prev('.separator');
                    if ($separator.length == 0) {
                        var separator = $comment.next('.separator');
                    }
                    Comments.hideMenu();
                    $comment.remove();
                    $separator.remove();
                    if ($eventComments.is(':empty')) {
                        $eventComments.parent().removeClass('comments_on');
                    }
                }
            }
        });
    },

    editComment: function (eventId, commentId) {
        Comments.hideMenu();

        var $eventComments = $('#event_comments_' + eventId);
        var $comment = $eventComments.find('#event_comment_' + commentId);
        var commentText = $comment.find('#event_comment_inner_' + commentId).text();

        var $editCommentTextarea = jQuery('<textarea id="event_edit_comment_' + eventId + '_' + commentId + '" class="event_edit_comment" maxlength="2000" data-original-comment="' + commentText + '">' + commentText + '</textarea>');
        $comment.html($editCommentTextarea);
        $editCommentTextarea.elastic().keydown(Comments.onEditCommentKeyDowm).blur(function(e){Comments.undoUpdateComment($(e.target));});
        var commentLength = $editCommentTextarea.text().length;
        $editCommentTextarea[0].setSelectionRange(commentLength, commentLength);
    },

    updateComment: function($commentTextarea) {
        var eventAndCommentIds = $commentTextarea.attr('id').replace('event_edit_comment_', '').split('_');
        var eventId = eventAndCommentIds[0];
        var commentId = eventAndCommentIds[1];
        var comment = $commentTextarea.val();

        if (comment) {
            $.ajax({
                url: 'php/update_event_comment.php',
                type: 'POST',
                dataType: 'text',
                data: {
                    event_comment_id: commentId,
                    event_comment: JSON.stringify(comment)
                },
                success: function (txt) {
                    var doc = Utils.parseJSON(txt);
                    if (doc.status.ecode == STATUS_ERR) {
                        alert("Update failed: " + doc.status.emessage);
                    }
                    else {
                        Comments.closeCommentEdit(eventId, commentId, comment);
                    }
                },
                error: function (xhr) {
                    alert(SaveHelper.failMessage('עדכון התגובה נכשל', xhr));
                }
            });
        } else {
            Comments.removeComment(eventId, commentId);
        }
    },

    closeCommentEdit: function(eventId, commentId, comment) {
        var commentHtml = Comments.getCommentHtml(eventId, commentId, gMemberName, comment);

        var $eventComments = $('#event_comments_' + eventId);
        var $comment = $eventComments.find('#event_comment_' + commentId);
        $comment.html(commentHtml);
    },

    undoUpdateComment: function($commentTextarea) {
        var eventAndCommentIds = $commentTextarea.attr('id').replace('event_edit_comment_', '').split('_');
        var eventId = eventAndCommentIds[0];
        var commentId = eventAndCommentIds[1];
        var comment = $commentTextarea.attr('data-original-comment');
        Comments.closeCommentEdit(eventId, commentId, comment);
    }
}

// A namespace for our functions
var Functions = {

    // init the users autocomplete
    initUsersAutoComplete:function () {
        $("#users1").autocomplete(
            {
                source:Functions.fetchUsersWithFilter,
                minLength:2,
                select:function (event, ui) {
                    //console.log(ui.item ? "Selected: " + ui.value + " aka "
                    //		+ ui.label : "Nothing selected, input was "
                    //		+ this.value);
                }
            });
    },

    // fetch the users from the server and populate the <select> of the users
    populateUsersSelect:function (onSuccessCallback) {
        $.ajax({
            url:'php/get_users.php',
            dataType:'text',
            data:{
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Deletion failed: "
                        + doc.status.emessage);
                } else {
                    $.each(doc.data, function (index, item) {
                        $('#users')
                            .append($("<option></option>")
                                .attr("value", item.id)
                                .text(item.member_name));
                    });
                    $('#users').val(memberId);
                    $('#users').show();

                    if (typeof onSuccessCallback == 'function') {
                        onSuccessCallback();
                    }
                }
            }
        });
    },

    /**
     *
     * @param term -
     *            the search term
     * @param responseCallback -
     *            a callback to handle response
     */
    fetchUsersWithFilter:function (request, responseCallback) {
        $.ajax({
            url:'php/get_users.php',
            dataType:'text',
            data:{
                search_term:request.term
            },
            success:function (txt) {
                var doc = Utils.parseJSON(txt);
                if (doc.status.ecode == STATUS_ERR) {
                    alert("Fetch users failed - " + json.status.emessage);
                    responseCallback(new Array());
                } else {
                    responseCallback(doc.data);
                }
            }
        });
    },

    userChanged:function () {
        $('#calendar').fullCalendar('render');
        Calendar.refreshMobileAgenda();
        EventDialog.initShoesAndCourses();
    }
};
/**
 * General utils
 */
var Utils = {
    /**
     * Check if the incoming argument is Array
     */
    isArray:function (a) {
        if (arguments.length != 1) {
            return false;
        }
        return Object.prototype.toString.apply(a) === '[object Array]';
    },

    parseJSON:function (jsonStr) {
        return $.browser.msie ? jsonParse($.trim(jsonStr)) : JSON.parse($.trim(jsonStr));
    },

    htmlEscape:function (s) {
        return s.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/'/g, '&#039;')
            .replace(/"/g, '&quot;')
            .replace(/\n/g, '<br />');
    }

};
/**
 * A collection of <select> utility functions
 */
var SelectUtils = {

    /**
     * Add an option to the top and select it.(according to 'selected' which is
     * boolean)
     */
    addOptionAtTopAndSelectIt:function (selectId, label, value, selected) {
        if (arguments.length != 4) {
            throw "Invalid number of arguments.";
        }
        if (typeof selectId != "string") {
            throw "Invalid input type.";
        }
        var select = document.getElementById(selectId);
        if (select == null) {
            throw "Can not find element with id " + selectId;
        }
        // make sure it is a <select>
        if (select.type.indexOf("select-") != 0) {
            throw "Invalid input type.";
        }
        select.options[0] = new Option(label, value, selected);
    },

    /**
     * Populate a <select> with JSON array:
     *      'selectId' - the id of the select - String
     *      'data' - a JSON array that looks like
     *          [{"label":"Hi","value":12},{"label":"Hello","value": 19}]
     *          The "title" attribute is supported as well but it is optional.
     * extraDataFieldName - optional - name of an extra data field that is sent with the data
     *      and should be applied to the options created
     */
    populateSelect:function (selectId, data, extraDataFieldName) {
        if (arguments.length < 2) {
            throw "Invalid number of arguments.";
        }
        if (typeof selectId != "string") {
            throw "Invalid input type.";
        }
        if (!Utils.isArray(data)) {
            throw "Invalid input type.";
        }
        var select = document.getElementById(selectId);
        if (select == null) {
            throw "Can not find element with id " + selectId;
        }
        // make sure it is a <select>
        if (select.type.indexOf("select-") != 0) {
            throw "Invalid input type.";
        }
        $.each(data, function (index, item) {
            var option = new Option(item.label, item.value);
            if (typeof item.title != 'undefined') {
                option.title = item.title;
            }
            if (typeof extraDataFieldName != 'undefined' && typeof item[extraDataFieldName] != 'undefined') {
                option.setAttribute(extraDataFieldName, item[extraDataFieldName]);
            }
            select.options[select.options.length] = option;
        });
    },

    // resets the given <select> either by setting the selected index to be the first,
    // or by clearing the entire options list
    resetSelect:function (selectId, deleteOptions) {
        if (arguments.length < 1) {
            throw "Invalid number of arguments.";
        }
        if (typeof selectId != "string") {
            throw "Invalid input type.";
        }
        var select = document.getElementById(selectId);
        if (select == null) {
            throw "Can not find element with id " + selectId;
        }
        // make sure it is a <select>
        if (select.type.indexOf("select-") != 0) {
            throw "Invalid input type.";
        }

        if (deleteOptions) {
            select.options.length = 0;
        }
        else {
            select.selectedIndex = 0;
        }

    },
    /**
     * Make a <select> entry selected according to the 'value' Return true if
     * there is a match and false if there is no match
     *
     */
    makeSelection:function (selectId, value) {
        if (arguments.length != 2) {
            throw "Invalid number of arguments.";
        }
        if (typeof selectId != "string") {
            throw "Invalid input type.";
        }
        var select = document.getElementById(selectId);
        if (select == null) {
            throw "Can not find element with id " + selectId;
        }
        // make sure it is a <select>
        if (select.type.indexOf("select-") != 0) {
            throw "Invalid input type.";
        }
        for (var i = 0; i < select.length; i++) {
            if (select.options[i].value == value) {
                select.selectedIndex = i;
                return true;
            }
        }
        return false;
    },
    /**
     * Get the selected value or null if there is no selected value
     */
    getSelectedValue:function (selectId) {
        if (arguments.length != 1) {
            throw "Invalid number of arguments.";
        }
        if (typeof selectId != "string") {
            throw "Invalid input type.";
        }
        var select = document.getElementById(selectId);
        if (select == null) {
            throw "Can not find element with id " + selectId;
        }
        // make sure it is a <select>
        if (select.type.indexOf("select-") != 0) {
            throw "Invalid input type.";
        }
        return select.options.length > 0 ? select.options[select.selectedIndex].value
            : null;
    }
};

/**
 * A collection of time related functions: validation,conversion,pace
 * calculation
 */

var Time = {

    monthNamesH: ['ינואר','פברואר','מרץ','אפריל','מאי','יוני','יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'],
    dayNamesH: ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'],

    jsDateToHebString: function(date) {
        if (typeof date == 'undefined') {
            return null;
        }

        var hebString = [];

        hebString[hebString.length] = 'יום ';
        hebString[hebString.length] = Time.dayNamesH[date.getDay()];
        hebString[hebString.length] = ', ';
//        hebString[hebString.length] = Time.jsDateToHebDate(date);
        hebString[hebString.length] = date.getDate();
        hebString[hebString.length] = ' ב';
        hebString[hebString.length] = Time.monthNamesH[date.getMonth()];

        return hebString.join('');
    },

    /**
     * Validate that a distance (as a String) is in the range 0 - 99.9
     */
    validateDistance:function (distanceStr) {
        if (arguments.length != 1) {
            return false;
        }
        if (typeof distanceStr != "string") {
            return false;
        }
        distanceStr = distanceStr.replace(/^\s+|\s+$/g, '');
        var match = /^[-+]?[0-9]+(\.[0-9]+)?$/.test(distanceStr);
        if (!match) {
            return false;
        }
        var distance = parseFloat(distanceStr);
        if (isNaN(distance)) {
            return false;
        }
        return distance >= 0 && distance <= 99.9;

    },

    /**
     * Calculate pace (min/km) based on distance (KM) and time(Seconds) Input
     * arguments should be numbers representing distance and time
     */
    calculatePace:function (distanceInKM, timeInSeconds) {
        if (arguments.length != 2) {
            return null;
        }
        if (typeof distanceInKM == "string") {
            distanceInKM = parseFloat(distanceInKM);
        }
        if (typeof timeInSeconds == "string") {
            timeInSeconds = parseInt(timeInSeconds, 10);
        }
        if (distanceInKM <= 0 || timeInSeconds <= 0) {
            return null;
        }
        var tmp = (timeInSeconds / 60) / distanceInKM;
        var minutes = Math.floor(tmp);
        var seconds = Math.round((tmp - minutes) * 60);
        if (seconds == 60) {
            minutes++;
            seconds = 0;
        }
        return minutes + ":" + Time.paddWithZero(seconds);
    },
    /**
     *
     * @param seconds
     */
    convertSecondsToMMSS:function (seconds) {
        if (arguments.length != 1) {
            return null;
        }
        if (isNaN(parseInt(seconds))) {
            return null;
        }

        var _minutes = Math.floor(seconds / 60);
        var _seconds = seconds % 60;
        return Time.paddWithZero(_minutes) + ":" + Time.paddWithZero(_seconds);
    },
    /**
     *
     */
    convertSecondsToHMMSS:function (seconds) {
        if (arguments.length != 1) {
            return null;
        }
        if (isNaN(parseInt(seconds))) {
            return null;
        }

        var _hours = Math.floor(seconds / 3600);
        seconds = seconds - (_hours * 3600);
        var tmp = Time.convertSecondsToMMSS(seconds);
        return _hours + ":" + tmp;
    },

    paddWithZero:function paddWithZero(input) {
        return input < 10 ? "0" + input : input;
    },
    /**
     *
     * @param timeValue
     * @returns {Boolean}
     */
    validateMMSS:function validateMMSS(timeValue) {
        if (arguments.length != 1) {
            return false;
        }

        var mmssRegExp = /^\d{2}:\d{2}$/; // mm:ss
        var fragments = timeValue.match(mmssRegExp);
        if (fragments != null) {
            var value = fragments[0];

            var minutesToSecondsColonIndex = 2;

            var minutesStr = value.substring(0, minutesToSecondsColonIndex);
            var secStr = value.substring(minutesToSecondsColonIndex + 1,
                value.length);

            var minutes = parseInt(minutesStr);
            var seconds = parseInt(secStr);

            if (minutes < 0 || minutes > 59) {
                return false;
            }
            if (seconds < 0 || seconds > 59) {
                return false;
            }

        } else {
            return false;
        }

        return true;

    },
    /**
     *
     */
    validateHMMSS:function (timeValue) {
        if (arguments.length != 1 || typeof timeValue !== 'string') {
            return false;
        }

        var hmmssRegExp = /^\d{1,2}:\d{2}:\d{2}$/; // h:mm:ss or hh:mm:ss
        var fragments = timeValue.match(hmmssRegExp);
        if (fragments != null) {
            var parts = timeValue.split(':');
            var hour = parseInt(parts[0], 10);
            var minutes = parseInt(parts[1], 10);
            var seconds = parseInt(parts[2], 10);

            if (isNaN(hour) || hour < 0 || hour > 99) {
                return false;
            }
            if (isNaN(minutes) || minutes < 0 || minutes > 59) {
                return false;
            }
            if (isNaN(seconds) || seconds < 0 || seconds > 59) {
                return false;
            }

        } else {
            return false;
        }
        return true;
    },
    /**
     *
     */
    convertHMMSSToSeconds:function (timeValue) {
        var valid = Time.validateHMMSS(timeValue);
        if (!valid) {
            return null;
        }

        var timeValueArray = timeValue.split(':');
        if (timeValueArray.length < 3) {
            return null;
        }

        var hour = parseInt(timeValueArray[0], 10);
        var minutes = parseInt(timeValueArray[1], 10);
        var seconds = parseInt(timeValueArray[2], 10);

        return hour * 3600 + minutes * 60 + seconds;
    },
    /**
     *
     */
    convertMMSSToSeconds:function (timeValue) {
        var valid = Time.validateMMSS(timeValue);
        if (!valid) {
            return null;
        }
        var timeValueArray = timeValue.split(':');
        if (timeValueArray < 2) {
            return null;
        }

        var minutes = parseInt(timeValueArray[0], 10);
        var seconds = parseInt(timeValueArray[1], 10);
        return minutes * 60 + seconds;
    },

    sqlDateToJsDate:function (sqlDate) {
        if (typeof sqlDate != 'string') {
            return null;
        }

        var sqlDateArray = sqlDate.split(' ');
        if (sqlDateArray.length < 2) {
            return null;
        }

        sqlDateArray = sqlDateArray[0].split('-');
        if (sqlDateArray.length < 3) {
            return null;
        }

        var year = sqlDateArray[0];
        var month = sqlDateArray[1];
        var day = sqlDateArray[2];
        if (isNaN(parseInt(year)) || isNaN(parseInt(month)) || isNaN(parseInt(day))) {
            return null;
        }

        return new Date(year, month - 1, day);
    },

    sqlDateToJsDateTime: function (sqlDate) {
        var sqlDateTime = Time.sqlDateToJsDate(sqlDate);

        if (sqlDateTime == null){
            return null;
        }

        var sqlTimeArray = sqlDate.split(' ');
        if (sqlTimeArray.length < 2) {
            return null;
        }

        sqlTimeArray = sqlTimeArray[1].split(':');
        if (sqlTimeArray.length < 3) {
            return null;
        }

        var hour = sqlTimeArray[0];
        var minute = sqlTimeArray[1];
        var second = sqlTimeArray[2];
        if (isNaN(parseInt(hour)) || isNaN(parseInt(minute)) || isNaN(parseInt(second))) {
            return null;
        }

        sqlDateTime.setHours(hour);
        sqlDateTime.setMinutes(minute);
        sqlDateTime.setSeconds(second);

        return sqlDateTime;
    },

    jsDateToHebDate:function (date) {
        if (typeof date != 'object') {
            return null;
        }

        var day = date.getDate();
        var month = date.getMonth() + 1; //Months are zero based
        var year = date.getFullYear();

        return day + '/' + month + '/' + year;
    },

    hebDateToSqlDate:function (hebDate) {
        if (typeof hebDate != 'string') {
            return null;
        }

        var hebDateArray = hebDate.split('/');
        if (hebDateArray.length < 3) {
            return null;
        }

        var year = hebDateArray[2];
        var month = hebDateArray[1];
        var day = hebDateArray[0];
        if (isNaN(parseInt(year)) || isNaN(parseInt(month)) || isNaN(parseInt(day))) {
            return null;
        }

        return year + '-' + month + '-' + day + ' 00:00:00';
    }
};