var STATUS_OK = 1;
var STATUS_ERR = 0;

// for event validation
var MIN_RUN_DISTANCE = 0;
var MAX_RUN_DISTANCE = 99.9;
var MIN_RUN_TIME = 0;
var MAX_RUN_TIME = 35999;
var MIN_EXTRA_DISTANCE = 0;
var MAX_EXTRA_DISTANCE = 99.9;

var NOT_SELECTED = 0;
var SELECT_SHOE_PROMPT = '-- בחר נעל --';
var SELECT_COURSE_PROMPT = '-- בחר מסלול --';
var SELECT_RACE_PROMPT = '-- בחר תחרות --';


/**
 * A simple object to hold run type attributes
 * @param color - the event color
 * @param borderColor - the event border coor
 * @param label - the run type label
 */
var  EventTypeAttributes = function(color,borderColor,label) {
    var _color = color;
    var _borderColor = borderColor;
    var _label = label;

    this.getColor = function() {
        return _color;
    };
    this.getBorderColor = function() {
        return _borderColor;
    };
    this.getLabel = function() {
        return _label;
    };

};

var EventTypes  = {
    RECOVERY_RUN : 1,
    LONG_RUN : 6,
    INTERVAL_RUN : 3,
    TEMPO_RUN : 4,
    MEDUIM_PACE_RUN : 2,
    RACE_RUN : 8,
    FARTLAK_RUN : 7,
    HILLS_RUN : 5,
    OTHER_SPORT : 9,
    EVENT_CANCELED : 10,
    REST_DAY : 11,
    TRAIL_RUN : 12,
    POWER :13
}

var EVENT_TYPES_ATTRIBUTES = new Array();
EVENT_TYPES_ATTRIBUTES[EventTypes.RACE_RUN]        	   = new EventTypeAttributes("#f5ccdc","#e0e0e0","תחרות");
EVENT_TYPES_ATTRIBUTES[EventTypes.INTERVAL_RUN]        = new EventTypeAttributes("#bcd7e9","#e0e0e0","הפוגות");
EVENT_TYPES_ATTRIBUTES[EventTypes.TEMPO_RUN]           = new EventTypeAttributes("#bcd7e9","#e0e0e0","ריצת קצב");
EVENT_TYPES_ATTRIBUTES[EventTypes.FARTLAK_RUN]         = new EventTypeAttributes("#bcd7e9","#e0e0e0","פארטלק");
EVENT_TYPES_ATTRIBUTES[EventTypes.HILLS_RUN]           = new EventTypeAttributes("#bcd7e9","#e0e0e0","אימון עליות");
EVENT_TYPES_ATTRIBUTES[EventTypes.LONG_RUN]            = new EventTypeAttributes("#d8e7d1","#e0e0e0","ריצה ארוכה");
EVENT_TYPES_ATTRIBUTES[EventTypes.RECOVERY_RUN]        = new EventTypeAttributes("#ffffff","#e0e0e0","ריצה קלה");
EVENT_TYPES_ATTRIBUTES[EventTypes.MEDUIM_PACE_RUN]     = new EventTypeAttributes("#ffffff","#e0e0e0","ריצת קצב בינוני");
EVENT_TYPES_ATTRIBUTES[EventTypes.OTHER_SPORT]         = new EventTypeAttributes("#ffffff","#e0e0e0","ספורט אחר");
EVENT_TYPES_ATTRIBUTES[EventTypes.REST_DAY]            = new EventTypeAttributes("#ffffff","#e0e0e0","יום מנוחה");
EVENT_TYPES_ATTRIBUTES[EventTypes.EVENT_CANCELED]      = new EventTypeAttributes("#ebebeb","#e0e0e0","אימון שבוטל");
EVENT_TYPES_ATTRIBUTES[EventTypes.TRAIL_RUN]           = new EventTypeAttributes("#d0d3d4","#e0e0e0","ריצת שטח");
EVENT_TYPES_ATTRIBUTES[EventTypes.POWER]               = new EventTypeAttributes("#ffffff","#e0e0e0","אימון כח");

var  RPEAttributes = function(label) {
    var _label = label;

   this.getLabel = function() {
       return _label;
   };

};

var RPE  = {
   EASY1 : 1,
   EASY2 : 2,
   EASY3 : 3,
   MEDIUM4 : 4,
   MEDIUM5 : 5,
   MEDIUM6 : 6,
   HARD7 : 7,
   HARD8 : 8,
   HARD9 : 9,
   MAX : 10
}

var RPE_ATTRIBUTES = new Array();
RPE_ATTRIBUTES[RPE.EASY1]        	= new RPEAttributes("קל - 1");
RPE_ATTRIBUTES[RPE.EASY2]        	= new RPEAttributes("קל - 2");
RPE_ATTRIBUTES[RPE.EASY3]           = new RPEAttributes("קל  - 3");
RPE_ATTRIBUTES[RPE.MEDIUM4]         = new RPEAttributes("בינוני - 4");
RPE_ATTRIBUTES[RPE.MEDIUM5]         = new RPEAttributes("בינוני - 5");
RPE_ATTRIBUTES[RPE.MEDIUM6]         = new RPEAttributes("בינוני - 6");
RPE_ATTRIBUTES[RPE.HARD7]        	= new RPEAttributes("קשה - 7");
RPE_ATTRIBUTES[RPE.HARD8]     		= new RPEAttributes("קשה - 8");
RPE_ATTRIBUTES[RPE.HARD9]        	= new RPEAttributes("קשה - 9");
RPE_ATTRIBUTES[RPE.MAX]            	= new RPEAttributes("מאמץ מקסימלי - 10");



