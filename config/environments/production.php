<?php
/* Production */
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');

define('WP_HOME', getenv('WP_HOME'));
define('WP_SITEURL', getenv('WP_SITEURL'));

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY'));

define('UNTAPPD_CLIENT_ID', getenv('UNTAPPD_CLIENT_ID'));
define('UNTAPPD_CLIENT_SECRET', getenv('UNTAPPD_CLIENT_SECRET'));

ini_set('display_errors', 0);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

$memcached_servers = array('127.0.0.1:11211');
define('WP_CACHE_KEY_SALT', getenv('DB_NAME'));