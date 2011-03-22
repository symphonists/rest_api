<?php

Class REST_API {
	
	private static $_uri = NULL;
	private static $_token = NULL;
	private static $_output_type = NULL;	
	private static $_plugin_class = NULL;
	
	public static $auth_logged_in = FALSE;
	
	private function __authenticate() {
		$expire_login = FALSE;

		// log in user from cookie or by token passed if API is not already public
		self::$auth_logged_in = Frontend::instance()->isLoggedIn();
		
		if (!self::$auth_logged_in) {
			self::$auth_logged_in = Frontend::instance()->loginFromToken(self::$_token);
			// we are logging in as a user, be sure to log out session after request
			$expire_login = TRUE;
		}

		// if private and no log in...
		if (!self::isLoggedIn()) self::sendError('API is private. Authentication failed.', 403);
		
		if ($expire_login) Frontend::instance()->logout();
		
		return TRUE;
	}
	
	public function init() {
		
		// store request parameters for later
		self::$_token = trim($_REQUEST['token']);		
		self::$_output_type = (isset($_REQUEST['output']) ? $_REQUEST['output'] : 'xml');
		self::$_uri = explode('/', trim($_REQUEST['url'], '/'));
		
		// get plugin name from the first segment in the URL
		// and remove it from the URL segments list
		$plugin_name = strtolower(self::$_uri[0]);
		self::$_plugin_class = 'REST_' . ucfirst($plugin_name);
		array_shift(self::$_uri);
		
		// include the plugin!
		include(EXTENSIONS . "/rest_api/plugins/$plugin_name/rest.$plugin_name.php");
		if (!class_exists(self::$_plugin_class)) REST_API::sendError(sprintf("Plugin '%s' does not exist.", self::$_plugin_class), 404);
		
		// perform global API authentication
		self::__authenticate($plugin_class);
		
		// initialise the plugin
		if(method_exists(self::$_plugin_class, 'init')) call_user_func(array(self::$_plugin_class, 'init'));
		
		// perform plugin authentication
		// if(method_exists(self::$_plugin_class, 'authenticate')) call_user_func(array(self::$_plugin_class, 'authenticate'));
		
		// choose whether the plugin should respond to a POST or a GET request
		if ($_POST) {
			if(method_exists(self::$_plugin_class, 'post')) call_user_func(array(self::$_plugin_class, 'post'));
			else REST_API::sendError(sprintf("Plugin '%s' does not support POST.", self::$_plugin_class), 401);
		} else {
			if(method_exists(self::$_plugin_class, 'get')) call_user_func(array(self::$_plugin_class, 'get'));
			else REST_API::sendError(sprintf("Plugin '%s' does not support GET.", self::$_plugin_class), 401);
		}
	}
	
	public function getRequestURI() {
		return self::$_uri;
	}
	
	public function isLoggedIn() {
		return self::$auth_logged_in;
	}
	
	/*public function isPublic() {
		return (Frontend::instance()->Configuration->get('public', 'rest_api') == 'yes') ? TRUE : FALSE;
	}
	
	/*public function getPublicSections() {
		$sections = Symphony::Configuration()->get('public_sections', 'rest_api');
		if(is_null($sections)) return array();
		return explode(',', $sections);
	}*/
	
	public function sendOutput($response_body=NULL, $code=200) {
		
		switch($code) {
			case 200: header('HTTP/1.0 200 OK'); break;
			case 401: header('HTTP/1.0 401 Bad Request'); break;
			case 403: header('HTTP/1.0 403 Forbidden'); break;
			case 404: header('HTTP/1.0 404 Not Found'); break;
		}
		
		switch(self::$_output_type) {
			case 'json':
				header('Content-Type: text/plain; charset=utf-8');
				require_once('class.xmltoarray.php');
				$output = json_encode(XMLToArray::convert($response_body->generate()));
			break;
			case 'serialise':
			case 'serialize':
				header('Content-Type: text/plain; charset=utf-8');
				require_once('class.xmltoarray.php');
				$output = serialize(XMLToArray::convert($response_body->generate()));
				break;
			case 'yaml':
				header('Content-Type: text/plain; charset=utf-8');
				require_once('class.xmltoarray.php');
				require_once('spyc-0.4.5/spyc.php');
				$output = Spyc::YAMLDump(XMLToArray::convert($response_body->generate()));
				break;
		 	case 'xml':
				header('Content-Type: text/xml; charset=utf-8');
				$output = $response_body->generate(TRUE);
			break;
		}
		
		echo $output;
		exit;
	}
	
	public function sendError($message=NULL, $code=NULL) {
		$response = new XMLElement('response');
		$response->appendChild(new XMLElement('error', $message));
		self::sendOutput($response, $code);
	}
	
}