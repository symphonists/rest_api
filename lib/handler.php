<?php

define('DOCROOT', rtrim(dirname(__FILE__), '/') . '/../../..');

require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
require_once(CORE . '/class.administration.php');
require_once(CORE . '/class.frontend.php');

require_once(EXTENSIONS . '/rest_api/lib/class.rest_api.php');

$frontend = Frontend::instance();
REST_API::buildContext($frontend);

$plugin = REST_API::getPlugin();
$class = 'REST_' . ucfirst($plugin);

include(EXTENSIONS . "/rest_api/plugins/rest.$plugin.php");

if (method_exists($class, "run")) {
	call_user_func(array($class, "run"));
} else {
	REST_API::sendError(sprintf("Plugin '%s' not found.", $plugin));
}