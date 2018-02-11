<?php
/* Development */
define('SAVEQUERIES', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SCRIPT_DEBUG', true);

define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY'));
define('UNTAPPD_CLIENT_ID', env('UNTAPPD_CLIENT_ID'));
define('UNTAPPD_CLIENT_SECRET', env('UNTAPPD_CLIENT_SECRET'));

$memcached_servers = array('127.0.0.1:11211');
define('WP_CACHE_KEY_SALT', env('DB_NAME'));