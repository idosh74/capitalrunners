/* General functions.js */

// Global - Ajax error handler
$.ajaxSetup({
    "error":function (jqXHR, textStatus, errorThrown) {
        console.log("error " + textStatus);
        console.log("errorThrown " + errorThrown);
        console.log("incoming Text " + jqXHR.responseText);
    }
});

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

    // Fetch events from the server
    fetchEvents:function (start, end, callback) {
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
            height:700,
            width:500,
            show:{
                effect:'drop',
                direction:'rtl'
            },
            buttons:{
                "אשר":function () {
                    var event_fields_data = EventDialog.getData();
                    if (event_fields_data != null) {
                        // passed validation
                        event_fields_data = Calendar.appendIdentityFields(event_fields_data, calEvent);
                        var event_fields_str = JSON.stringify(event_fields_data);
                        $.ajax({
                            url:'php/update_event.php',
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
                                    $('#calendar').fullCalendar('render');

                                }
                            }
                        });
                        $(this).dialog("close");
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
                                            $('#calendar').fullCalendar(
                                                'render');
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
        $("#create_update_event_dialog").dialog("open");
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
                height:700,
                width:500,
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
                            event_fields_data.date = the_date;
                            var event_fields_str = JSON.stringify(event_fields_data);
                            $.ajax({
                                url:'php/create_event.php',
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
                                        $('#calendar').fullCalendar('render');

                                    }
                                }
                            });
                            $(this).dialog("close");
                        }
                    },
                    "סגור":function () {
                        $(this).dialog("close");
                    }
                }
            });
        $('#create_update_event_dialog').css('background-color', '#feffe5');
        $("#create_update_event_dialog").dialog("open");
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
        // if (weight != 0 && weight != null)
        // {
        //     html += " - משקל: <span>"+ weight+" קג'</span>  ";
        // }
        
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

        return html;
    }
}

var EventFormatter = {
    getTotalDistance:function (warmupDistance, runDistance, cooldownDistance, runTypeId) {

        var runDistance = parseFloat(runDistance);
        if (runDistance == NaN || runDistance == 0 || runTypeId == EventTypes.OTHER_SPORT || runTypeId == EventTypes.REST_DAY || runTypeId == EventTypes.EVENT_CANCELED) {
            return null;
        }
        else {
            return (runDistance).toFixed(1) + ' ק"מ';
        }
    },

    getRunDistanceAndPace:function (warmupDistance, runDistance, cooldownDistance, runTime, runTypeId) {

        if (runTypeId == EventTypes.OTHER_SPORT || runTypeId == EventTypes.REST_DAY || runTypeId == EventTypes.EVENT_CANCELED) {
            // no meaning for run pace in these cases
            return null;
        }

        var runDistance = parseFloat(runDistance);
        if (runDistance == NaN || runDistance == 0) {
            return;
        }

        var warmupDistance = parseFloat(warmupDistance);
        if (warmupDistance == NaN) {
            warmupDistance = 0;
        }

        var cooldownDistance = parseFloat(cooldownDistance);
        if (cooldownDistance == NaN) {
            cooldownDistance = 0;
        }

        var runTimeFormatted = null;
        if (runTime > 3600) {
            runTimeFormatted = Time.convertSecondsToHMMSS(runTime);
        }
        else if (runTime > 0) {
            runTimeFormatted = Time.convertSecondsToMMSS(runTime);
        }

        var runPace = Time.calculatePace(runDistance, runTime);
        var warmupOrCooldown = (warmupDistance > 0 || cooldownDistance > 0);
        if (warmupOrCooldown) {
            totalRunDistanceFormatted = (warmupDistance + runDistance + cooldownDistance).toFixed(1) + ' ק"מ';
        } else {
            totalRunDistanceFormatted = '';
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
}

var EventDialog = {

    // initializes the event dialog with default values and fills the shoes and courses <select>s
    // with values appropriate to the current user
    init:function () {
        this.initRunTypes();
        this.initRPE();
        this.initShoesAndCourses();
        $('#rpe').change(this.RPEChanged);
        $('#courseSelect').change(this.courseChanged);
        $('#raceSelect').change(this.raceChanged);
        EventDialog.shoesDetached = false;
        $('#shoeSelect').change(this.shoeChanged);
        $('#extraShoeSelect').change(this.extraShoeChanged);

        // input masking for distance, duration fields
        $.mask.definitions['5'] = "[0-5]";
        $('#run_distance').mask('99.9', {placeholder:"0"});
        $('#run_time').mask('9:59:59', {placeholder:"0"});
        $('#extra_run_distance').mask('99.9', {placeholder:"0"});

        // handler for distance, duration change event in order to update pace
        $('#main_run input[type="text"]').blur(this.calculatePace);
        $('#pulse').val('0');
		$('#max_pulse').val('0');
		$('#elevation').val('0');
 		$('#weight').mask('99.9', {placeholder:"0"});    
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

        $('#run_distance').val('00.0');
        $('#run_time').val('0:00:00');
        $('#run_pace').text('');
        $('#extra_run_distance').val('00.0');

        $('#notesContainer').empty().append('<textarea id="notes"></textarea>');
        $('#notes').val('').elastic();
		$('#pulse').val('0');
		$('#max_pulse').val('0');
		$('#elevation').val('0');
 		$('#weight').val('00.0');
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
        $('#run_types').change(this.runTypeChanged);
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
        if (!this.validateDistance("תרגיל", eventFields.run_distance, MIN_RUN_DISTANCE, MAX_RUN_DISTANCE)) {
            return null;
        }
        eventFields.run_time = Time.convertHMMSSToSeconds($('#run_time').val());
        if (!this.validateDuration("תרגיל", eventFields.run_time, MIN_RUN_TIME, MAX_RUN_TIME, Time.convertSecondsToHMMSS)) {
            return null;
        }
        eventFields.extra_run_distance = $('#extra_run_distance').val();
        if (!this.validateDistance("תוספת", eventFields.extra_run_distance, MIN_EXTRA_DISTANCE, MAX_EXTRA_DISTANCE)) {
            return null;
        }
        var runType = $('#run_types').val();
        if (runType == EventTypes.OTHER_SPORT || runType == EventTypes.REST_DAY || runType == EventTypes.EVENT_CANCELED) {
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
                    $('#run_distance').val(Time.paddWithZero(fields.run_distance));
                    $('#run_time').val(Time.convertSecondsToHMMSS(fields.run_time));
                    $('#extra_run_distance').val(Time.paddWithZero(fields.extra_distance));
                    $('#notesContainer').empty().append('<textarea id="notes"></textarea>');
                    $('#notes').val(fields.notes).elastic();
					$('#pulse').val(fields.pulse);
					$('#max_pulse').val(fields.max_pulse);
					$('#elevation').val(fields.elevation);
				 	$('#weight').val(Time.paddWithZero(fields.weight));
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
        if (runType == EventTypes.OTHER_SPORT || runType == EventTypes.REST_DAY || runType == EventTypes.EVENT_CANCELED) {
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
                $('#run_distance').val(Time.paddWithZero(courseLength.toFixed(1)));
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
        var run_pace = Time.calculatePace($('#run_distance').val(), Time.convertHMMSSToSeconds($('#run_time').val()));
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
        var eventHtml =
            '<div class="event">' +
                '   <div>' +
                '       <div class="event_type" style="background-color:' + Calendar.getEventColor(event.run_type_id) + '; border:solid 1px ' + Calendar.getEventBorderColor(event.run_type_id) + ';"></div>' +
                '       <div class="event_title">' + Calendar.getFeedEventHtml(event) + '</div>' +
                '   </div>' +
                '   <div class="comments">' +
                '       <div id="event_comments_' + event.id + '" class="list"></div>' +
                '       <div><textarea id="event_new_comment_' + event.id + '" class="event_new_comment" placeholder="הוספת פרגון..." maxlength="2000"></textarea></div>' +
                '   </div>' +
                '</div>';

        $container.append(eventHtml);
        $('#event_new_comment_'+event.id).elastic().keypress(Comments.onNewCommentKeyPress);
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
        if (arguments.length != 1) {
            return false;
        }

        var hmmssRegExp = /^\d{1}:\d{2}:\d{2}$/; // h:mm:ss
        var fragments = timeValue.match(hmmssRegExp);
        if (fragments != null) {
            var value = fragments[0];

            var hoursToMinutesColonIndex = 1;
            var minutesToSecondsColonIndex = 4;

            var hourStr = value.substring(0, hoursToMinutesColonIndex);
            var minutesStr = value.substring(hoursToMinutesColonIndex + 1,
                minutesToSecondsColonIndex);
            var secStr = value.substring(minutesToSecondsColonIndex + 1,
                value.length);

            var hour = parseInt(hourStr);
            var minutes = parseInt(minutesStr);
            var seconds = parseInt(secStr);

            if (hour < 0 || hour > 9) {
                return false;
            }
            if (minutes < 0 || minutes > 59) {
                return false;
            }
            if (seconds < 0 || seconds > 59) {
                return false;
            }

        } else {
            return;
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


function aaa() {
    alert("Email function called!");
}