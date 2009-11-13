<?php

define('DOCROOT', rtrim(dirname(__FILE__), '/') . '/../../..');

require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
require_once(CORE . '/class.frontend.php');

require_once(TOOLKIT . '/class.datasourcemanager.php');
require_once(TOOLKIT . '/class.eventmanager.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');

require_once(EXTENSIONS . '/rest_api/lib/class.rest_api.php');

$frontend = Frontend::instance();

REST_API::setParameters();
REST_API::authenticate($frontend);

if ($_POST) {	
	REST_API::processEvent($frontend);
} else {	
	REST_API::processDataSource($frontend);
}