<?php

require_once('class.xmltoarray.php');

Class REST_API {
	
	protected static $context = null;
	protected static $uri = null;
	protected static $plugin = null;
	protected static $token = null;
	protected static $output_type = null;
	
	public function buildContext($context) {
		
		self::$context = $context;
		self::$token = trim($_REQUEST['token']);		
		self::$output_type = (isset($_GET['output']) ? $_GET['output'] : 'xml');
		
		self::$uri = explode('/', trim($_GET['url'], '/'));
		self::$plugin = self::$uri[0];
		array_shift(self::$uri);
		
		//self::authenticate();
		
	}
	
	public function getURI() {
		return self::$uri;
	}
	
	public function getContext() {
		return self::$context;
	}
	
	public function getPlugin() {
		return self::$plugin;
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
		if (!$public && !$logged_in) self::sendError('Could not authenticate.');

		// public, but user not authenticated, check against section whitelist
		if ($public && !$logged_in && !in_array(self::$section_handle, explode(',', self::$context->Configuration->get('public_sections', 'rest_api')))) {
			self::sendError(sprintf('No public access to the section "%s".', self::$section_handle));
		}
		
		if ($expire_login) self::$context->logout();
	}*/
	
	public function sendOutput($response) {
		
		switch(self::$output_type) {
			case 'json':
				header('Content-Type: text/plain');
				$output = json_encode(XMLToArray::convert($response->generate()));
			break;
			case 'serialise':
				header('Content-Type: text/plain');
				$output = serialize(XMLToArray::convert($response->generate()));
				break;
			case 'yaml':
				header('Content-Type: text/plain');
				require_once('spyc-0.4.5/spyc.php');
				$output = Spyc::YAMLDump(XMLToArray::convert($response->generate()));
				break;
		 	case 'xml':
				header('Content-Type: text/xml');
				$output = $response->generate(true);
			break;
		}
		
		echo $output;
		exit;
	}
	
	public function sendError($message) {
		$response = new XMLElement('response');
		$response->appendChild(new XMLElement('error', $message));
		self::sendOutput($response);
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