<?php
// set up Symphony 
define('DOCROOT', rtrim(dirname(__FILE__), '/') . '/../..');
chdir(DOCROOT);
if (file_exists(DOCROOT . '/vendor/autoload.php')) {
	require_once(DOCROOT . '/vendor/autoload.php');
}
require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
require_once(CORE . '/class.frontend.php');

// include the extension core
require_once(EXTENSIONS . '/rest_api/lib/class.rest_api.php');

REST_API::init();
