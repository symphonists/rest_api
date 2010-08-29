<?php

define('DOCROOT', rtrim(dirname(__FILE__), '/') . '/../../..');

require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
require_once(CORE . '/class.administration.php');
require_once(CORE . '/class.frontend.php');

require_once(EXTENSIONS . '/rest_api/lib/class.rest_api.php');

$frontend = Frontend::instance();
REST_API::init($frontend);