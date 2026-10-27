<?php
define('STATUS_OK', "1");
define('STATUS_ERR', "0");

define('LOGGER_ERR', 0);
define('LOGGER_DBG', 1);

define('AUTH_SECRET_KEY', 'ettiEiner#2');
define('AUTH_SESSION_KEY_NAME', 'sw_auth_member');
define('AUTH_COOKIE_NAME', 'swlog-number');
define('AUTH_COOKIE_DURATION', 2592000); // 30 days in seconds

define('MEMBER_NAME_SESSION_KEY_NAME', 'member_name');
define('MEMBER_ADMIN_SESSION_KEY_NAME', 'm_is_admin');
define('MEMBER_COACH_SESSION_KEY_NAME', 'm_is_coach');

define('tl_members', 'members');
define('tl_events', 'events');
define('tl_shoes', 'shoes');
define('tl_course', 'courses');
define('tl_logger', 'tl_logger');

define('MIN_RUN_DISTANCE', 0);
define('MAX_RUN_DISTANCE', 99.9);
define('MIN_EXTRA_DISTANCE', 0);
define('MAX_EXTRA_DISTANCE', 99.9);
define('MIN_RUN_TIME', 0);
define('MAX_RUN_TIME', 35999);

define('JS_VERSION', 44);
define('CSS_VERSION', 17);