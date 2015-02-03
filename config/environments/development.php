<?php
/* Development */
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');

define('WP_HOME', getenv('WP_HOME'));
define('WP_SITEURL', getenv('WP_SITEURL'));

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY'));

define('UNTAPPD_CLIENT_ID', getenv('UNTAPPD_CLIENT_ID'));
define('UNTAPPD_CLIENT_SECRET', getenv('UNTAPPD_CLIENT_SECRET'));

define('SAVEQUERIES', true);
define('WP_DEBUG', true);
define('SCRIPT_DEBUG', true);
