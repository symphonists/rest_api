<?php

require_once('class.xmltoarray.php');

Class REST_API {
	
	protected static $_context = null;
	protected static $_uri = null;
	protected static $_plugin = null;
	protected static $_token = null;
	protected static $_format = null;
	
	public function init($context) {
		
		self::$_context = $context;
		self::$_token = trim($_REQUEST['token']);		
		self::$_format = (isset($_GET['format']) ? $_GET['format'] : 'xml');
		
		self::$_uri = explode('/', trim($_GET['url'], '/'));
		self::$_plugin = self::$_uri[0];
		
		if (empty(self::$_plugin)) REST_API::renderError(sprintf("No plugin specified.", $plugin));
		
		array_shift(self::$_uri);
		
		$class_path = sprintf(EXTENSIONS . '/rest_api/plugins/%s/rest.%s.php', self::$_plugin, self::$_plugin);
		
		if (file_exists($class_path)) {
			include($class_path);
			call_user_func(array('REST_' . ucfirst(self::$_plugin), "run"));
		} else {
			REST_API::renderError(sprintf("Plugin '%s' not found.", self::$_plugin));
		}
	}
	
	public function getURI() {
		return self::$_uri;
	}
	
	public function getContext() {
		return self::$_context;
	}
		
	/*public function authenticate() {
		$expire_login = false;
		$logged_in = false;

		// in public mode?
		$public = (self::$context->Configuration->get('public', 'rest_api') == 'yes') ? true : false;

		// log in user from cookie or by token passed
		if (!$logged_in) $logged_in = self::$context->isLoggedIn();
		if (!$logged_in) {
			$logged_in = self::$context->loginFromToken(self::$token);
			$expire_login = true;
		}

		// if private and no log in...
		if (!$public && !$logged_in) self::renderError('Could not authenticate.');

		// public, but user not authenticated, check against section whitelist
		if ($public && !$logged_in && !in_array(self::$section_handle, explode(',', self::$context->Configuration->get('public_sections', 'rest_api')))) {
			self::renderError(sprintf('No public access to the section "%s".', self::$section_handle));
		}
		
		if ($expire_login) self::$context->logout();
	}*/
	
	public function sendResponse($response) {
		
		switch(self::$_format) {
			case 'json':
				header('Content-Type: text/plain');
				$response = json_encode(XMLToArray::convert($response->generate()));
			break;
			case 'serialise':
				header('Content-Type: text/plain');
				$response = serialize(XMLToArray::convert($response->generate()));
				break;
			case 'yaml':
				header('Content-Type: text/plain');
				require_once('spyc-0.4.5/spyc.php');
				$response = Spyc::YAMLDump(XMLToArray::convert($response->generate()));
				break;
		 	case 'xml':
				header('Content-Type: text/xml');
				$response = $response->generate(true);
			break;
		}
		
		echo $response;
		exit;
	}
	
	public function renderError($message) {
		$response = new XMLElement('response');
		$response->appendChild(new XMLElement('error', $message));
		self::sendResponse($response);
	}
	
	/*public function generateArray($node, $is_field_data=false){

		$result = array();

		if(count($node->getAttributes()) > 0){
			foreach($node->getAttributes() as $attribute => $value ){
				if(strlen($value) != 0){
					$result[$node->getName()]['_' . $attribute] = $value;
				}
			}				
		}

		$value = $node->getValue();
		if (!is_null($value)) $result[$node->getName()]['value'] = $node->getValue();

		$numberOfchildren = $node->getNumberOfChildren();

		if($numberOfchildren > 0 || strlen($node->getValue()) != 0){

			if($numberOfchildren > 0 ) {

				foreach($node->getChildren() as $child) {

					$next_child_is_field_data = ($child->getName() == 'entry');

					if ($is_field_data == true) {
						if(($child instanceof XMLElement)) $result[$node->getName()]['fields'][] = self::generateArray($child, $next_child_is_field_data);
					} else {
						if(($child instanceof XMLElement)) $result[$node->getName()][] = self::generateArray($child, $next_child_is_field_data);
					}
				}

			}			
		}

		return $result;
	}*/
	
}