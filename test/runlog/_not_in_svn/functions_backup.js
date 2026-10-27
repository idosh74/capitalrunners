/* General functions.js */

// Global - Ajax error handler
$.ajaxSetup({
	"error" : function(jqXHR, textStatus, errorThrown) {
		console.log("error " + textStatus);
		console.log("errorThrown " + errorThrown);
		console.log("incoming Text " + jqXHR.responseText);
	}
});

/**
 * 
 * @param term -
 *            the search term
 * @param responseCallback -
 *            a callback to handle response
 */
function fetchUsersWithFilter(request, responseCallback) {
	$.ajax({
		url : 'php/get_users.php',
		dataType : 'json',
		data : {
			search_term : request.term
		},
		success : function(json) {
			if (json.status.ecode == BC_ERR) {
				alert("Fetch users failed - " + json.status.emessage);
				responseCallback(new Array());
			} else {
				responseCallback(json.data);
			}
		}
	});
}
/**
 * Collect the event fields into single object
 */
function getEventFields() {
	var eventFields = new Object();
	eventFields.warmup_distance = $('#warmup_distance').val();
	eventFields.warmup_time = Time.convertMMSSToSeconds($('#warmup_time').val()); 
	eventFields.run_distance = $('#run_distance').val();
	eventFields.run_time = Time.convertHMMSSToSeconds($('#run_time').val());
	eventFields.run_distance = $('#cooldown_distance').val();
	eventFields.run_time = Time.convertMMSSToSeconds($('#cooldown_time').val()); 
	eventFields.notes = $('#notes').val();
	eventFields.shoe_id = $('#shoesSelect').val();
	eventFields.course_id = $('#courseSelect').val();
	eventFields.run_type_id = $('#run_types').val();
	eventFields.share = $('#share').attr('checked');
	return eventFields;
}
/**
 * Populate the shoes <select>
 * @returns
 */
function populateShoes(shoes,shoeId,shoeName) {
	if (shoes.length > 0) {
		$('#shoesSelect').show();
		$('#shoesSelectLabel').show();
		clearAndPopulateSelect(shoes, 'shoesSelect',
				shoeId, shoeName, UNSELECTED_SHOE_LABEL);
	} else {
		// we have no shoes - just hide the shoes select
		$('#shoesSelect').hide();
		$('#shoesSelectLabel').hide();

	}
}
/**
 * Populate the Courses <select>
 * @returns
 */
function populateCourses(courses,courseId,courseName) {
	if (courses.length > 0) {
		$('#courseSelect').show();
		$('#courseSelectLabel').show();
		clearAndPopulateSelect(courses, 'courseSelect',
				courseId, courseName, UNSELECTED_COURSE_LABEL);
	} else {
		// we have no course - just hide the course select
		$('#courseSelect').hide();
		$('#courseSelectLabel').hide();

	}
}
/**
 * Populate the pace fields
 * @returns
 */
function populatePaceFields(fields) {
	var warmup_pace = Time.calculatePace(fields.warmup_distance, fields.warmup_time);
	$('.warmup_pace').html(warmup_pace);
	var run_pace = Time.calculatePace(fields.run_distance, fields.run_time);
	$('.run_pace').html(run_pace);
	var cooldown_pace = Time.calculatePace(fields.cooldown_distance, fields.cooldown_time);
	$('.cooldown_pace').html(cooldown_pace);
	
}

function populateEventFields(calEvent) {
	$.ajax({
		url : 'php/get_event_details.php',
		dataType : 'json',
		data : {
			event_id : calEvent.event_id,
			member_id : calEvent.member_id
		},
		success : function(doc) {
			if (doc.status.ecode == BC_ERR) {
				alert("Get event details failed: " + doc.status.emessage);
			} else {
				var fields = doc.data.event_fields;
				$('#warmup_distance').val(fields.warmup_distance);
				$('#warmup_time').val(Time.convertSecondsToMMSS(fields.warmup_time));
				$('#run_distance').val(fields.run_distance);
				$('#run_time').val(Time.convertSecondsToHMMSS(fields.run_time));
				$('#cooldown_distance').val(fields.cooldown_distance);
				$('#cooldown_time').val(Time.convertSecondsToMMSS(fields.cooldown_time));
				$('#share').attr('checked', fields.share);
				$('#notes').val(fields.notes);
				populatePaceFields(fields);
				populateShoes(doc.data.shoes_data,doc.data.selected_shoe.id,doc.data.selected_shoe.name);
				populateCourses(doc.data.courses_data,doc.data.selected_course.id,doc.data.selected_course.name);
				clearAndPopulateSelect(doc.data.run_types, 'run_types',
						doc.data.selected_run_type.id, doc.data.selected_run_type.name, "");
			}
		}
	});
}
/**
 * A generic method that does the following: 1) Clear select values 2) Populate
 * the select 3) Try to make a select option selected - if it fails, hide the
 * select and show a label instead
 * 
 * @param selectData -
 *            JSON array the will be used in the <select> population
 * @param selectId -
 *            the <select> id
 * @param value -
 *            the value that we want to make selected
 * @param label -
 *            the label to show in case the we fail to select the value
 */
function clearAndPopulateSelect(selectData, selectId, value, label,
		labelForUnselected) {
	SelectUtils.clearSelect(selectId);
	var itemSelected  = false;
	for(var i = 0; i < selectData.length; i++) {
		if(selectData[i].value == value) {
			itemSelected = true;
			break;
		}
	}
	SelectUtils.addOptionAtTopAndSelectIt(selectId, labelForUnselected,
			NOT_SELECTED, !itemSelected);
	SelectUtils.populateSelect(selectId, selectData);
	SelectUtils.makeSelection(selectId,value);
	if (!itemSelected) {
		// there is no match but there is a valid label
		if (label != null) {
			$('#' + selectId).hide();
			$('#' + selectId + 'Label').hide();
			$('.inactive_show').show();
			$('.inactive_show').text(label);
		} else {
			$('.inactive_show').hide();
			$('#' + selectId).hide();
			$('#' + selectId + 'Label').hide();
		}
	} else {
		// we have a match
		$('.inactive_show').hide();
		$('#' + selectId).show();
	}
}

// A namespace for our functions
var Functions = {

	initDialogs : function() {
		$('#delete_dialog').dialog({
			autoOpen : false,
			resizable : false,
			modal : true
		});
		$('#create_update_event_dialog').dialog({
			autoOpen : false,
			resizable : false,
			modal : true
		});
	},
	// init the users autocomplete
	initUsersAutoComplete : function() {
		$("#users1").autocomplete(
				{
					source : fetchUsersWithFilter,
					minLength : 2,
					select : function(event, ui) {
						//console.log(ui.item ? "Selected: " + ui.value + " aka "
						//		+ ui.label : "Nothing selected, input was "
						//		+ this.value);
					}
				});
	},

	// fetch the users from the server and populate the <select> of the users
	_populateUsersSelect : function() {
		$.ajax({
			url : 'php/get_users.php',
			dataType : 'text',
			data : {
			},
			success : function(txt) {
				var docStr = $.trim(txt);
				var doc = Utils.parseJSON(docStr);
				if (doc.status.ecode == BC_ERR) {
					alert("Deletion failed: "
							+ doc.status.emessage);
				} else {
					$.each(doc.data, function(index, item) {   
					     $('#users')
					         .append($("<option></option>")
					         .attr("value",item.id)
					         .text(item.member_name)); 
					});
				}
			}
		});
	},

	// fetch the users from the server and populate the <select> of the users
	populateUsersSelect : function() {
		$.getJSON("php/get_users.php", function(json) {
			if (json.status.ecode == BC_ERR) {
				alert("Users display failed - " + json.status.emessage);
			} else {
				var usersSelect = $("#users");
				$.each(json.data, function(index, item) {
					usersSelect.append(new Option(item.member_name,
							item.id));
				});
			}
		});
	},

	// Handle clicking on 'event'
	handleClickEvent : function(calEvent, jsEvent, view) {
		// Click + shift invoke the 'delete' dialog

		if (jsEvent.shiftKey) {
			$("#delete_dialog").dialog(
					{
						buttons : {
							"אשר" : function() {
								$.ajax({
									url : 'php/delete_event.php',
									dataType : 'json',
									data : {
										event_id : calEvent.event_id,
										member_id : calEvent.member_id
									},
									success : function(doc) {
										if (doc.status.ecode == BC_ERR) {
											alert("Deletion failed: "
													+ doc.status.emessage);
										} else {
											$('#calendar').fullCalendar(
													'render');
										}
									}
								});
								$(this).dialog("close");
							},
							"סגור" : function() {
								$(this).dialog("close");
							}
						}
					});
			$("#delete_dialog").dialog("open");
		} else {
			// alert("-- TODO:UPDATE -- Event ID is " + calEvent.event_id);
			$('#create_update_event_dialog').dialog(
					{
						height : 450,
						width : 450,
						show : {
							effect : 'drop',
							direction : 'rtl'
						},
						buttons : {
							"אשר" : function() {
								$.ajax({
									url : 'php/update_event.php',
									dataType : 'json',
									data : {
										event_id : calEvent.event_id,
										member_id : calEvent.member_id
									},
									success : function(doc) {
										if (doc.status.ecode == BC_ERR) {
											alert("Update failed: "
													+ doc.status.emessage);
										} else {
											$('#calendar').fullCalendar(
													'render');
										}
									}
								});
								$(this).dialog("close");
							},
							"סגור" : function() {
								$(this).dialog("close");
							}
						}
					});
			$('#create_update_event_dialog').css('background-color', '#fafac7')
			populateEventFields(calEvent);
			$("#create_update_event_dialog").dialog("open");
		}
	},
	// Fetch events from the server
	fetchEvents : function(start, end, callback) {
		var the_member_id = $("#users").val();
		$.ajax({
			url : 'php/get_events.php',
			dataType : 'json',
			data : {
				start : Math.round(start.getTime() / 1000),
				end : Math.round(end.getTime() / 1000),
				member_id : the_member_id
			},
			success : function(doc) {
				if (doc.status.ecode == BC_ERR) {
					alert("Fetch event failed - " + doc.status.emessage);
				} else {
					var events = [];
					$.each(doc.data, function(index, item) {
						events.push({
							event_id : item.id,
							title : $(this).attr('title'),
							start : $(this).attr('start'),
							member_id : the_member_id
						});
					});
					callback(events);
				}
			}
		});
	},

	// Handle clicking on the calendar cell (not on the event)
	handleClickDay : function(date, allDay, jsEvent, view) {
		// handle "what did the other runners did today.."
		if (jsEvent.shiftKey) {
			$.ajax({
				url : 'php/get_other_users_workout.php',
				dataType : 'json',
				data : {
					date : Math.round(date.getTime() / 1000)
				},
				success : function(doc) {
					alert(doc.status.ecode);
				}
			})

		} else {
			// TODO: implement
			alert("TODO: Create new event for date " + date);

		}
	}
};
/**
 * General utils
 */
var Utils = {
	/**
	 * Check if the incoming argument is Array
	 */
	isArray : function(a) {
		if (arguments.length != 1) {
			return false;
		}
		return Object.prototype.toString.apply(a) === '[object Array]';
	},
	
	parseJSON : function(jsonStr) {
		return $.browser.msie ? jsonParse(jsonStr) : JSON.parse(jsonStr);
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
	addOptionAtTopAndSelectIt : function(selectId, label, value, selected) {
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
	 * Populate a <select> with JSON array - 'selectId' - the id of the select -
	 * String
	 *  - 'data' - a JSON array that looks like
	 * [{"label":"Hi","value":12},{"label":"Hello","value": 19}]
	 * 
	 */
	populateSelect : function(selectId, data) {
		if (arguments.length != 2) {
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
		$.each(data, function(index, item) {
			select.options[select.options.length] = new Option(item.label,
					item.value);
		});
	},
	/**
	 * Clear the <select> entries
	 */
	clearSelect : function(selectId) {
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
		select.options.length = 0;

	},
	/**
	 * Make a <select> entry selected according to the 'value' Return true if
	 * there is a match and false if there is no match
	 * 
	 */
	makeSelection : function(selectId, value) {
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
		for ( var i = 0; i < select.length; i++) {
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
	getSelectedValue : function(selectId) {
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

	/**
	 * Validate that a distance (as a String) is in the range 0 - 99.9
	 */
	validateDistance : function(distanceStr) {
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
	calculatePace : function(distanceInKM, timeInSeconds) {
		if (arguments.length != 2) {
			return null;
		}
		if (typeof distanceInKM == "string") {
			distanceInKM = parseFloat(distanceInKM);
		} 
		if (typeof timeInSeconds == "string") {
			timeInSeconds = parseInt(timeInSeconds,10);
		}
		if (distanceInKM <= 0 || timeInSeconds <= 0) {
			return null;
		}
		var tmp = (timeInSeconds / 60) / distanceInKM;
		var minutes = Math.floor(tmp);
		var seconds = Math.round((tmp - minutes) * 60);
		return minutes + ":" + Time.paddWithZero(seconds);
	},
	/**
	 * 
	 * @param seconds
	 * @returns
	 */
	convertSecondsToMMSS : function(seconds) {
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
	convertSecondsToHMMSS : function(seconds) {
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

	paddWithZero : function paddWithZero(input) {
		return input <= 9 ? "0" + input : input;
	},
	/**
	 * 
	 * @param timeValue
	 * @returns {Boolean}
	 */
	validateMMSS : function validateMMSS(timeValue) {
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
	validateHMMSS : function(timeValue) {
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
	convertHMMSSToSeconds : function(timeValue) {
		var valid = Time.validateHMMSS(timeValue);
		if (!valid) {
			return null;
		}
		var hoursToMinutesColonIndex = 1;
		var minutesToSecondsColonIndex = 4;

		var hourStr = timeValue.substring(0, hoursToMinutesColonIndex);
		var minutesStr = timeValue.substring(hoursToMinutesColonIndex + 1,
				minutesToSecondsColonIndex);
		var secStr = timeValue.substring(minutesToSecondsColonIndex + 1,
				timeValue.length);

		var hour = parseInt(hourStr, 10);
		var minutes = parseInt(minutesStr, 10);
		var seconds = parseInt(secStr, 10);

		return hour * 3600 + minutes * 60 + seconds;
	},
	/**
	 * 
	 */
	convertMMSSToSeconds : function(timeValue) {
		var valid = Time.validateMMSS(timeValue);
		if (!valid) {
			return null;
		}
		var minutesToSecondsColonIndex = 2;
		var minutesStr = timeValue.substring(0, minutesToSecondsColonIndex);
		var secStr = timeValue.substring(minutesToSecondsColonIndex + 1,
				timeValue.length);

		var minutes = parseInt(minutesStr, 10);
		var seconds = parseInt(secStr, 10);
		return minutes * 60 + seconds;

	}

};
