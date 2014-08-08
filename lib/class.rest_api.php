<?php

if(!class_exists('XMLToArray')) require_once('class.xmltoarray.php');

Class REST_API {
	
	private static $_is_frontend_page = FALSE;
	
	private static $_uri = NULL;
	private static $_token = NULL;
	private static $_output_type = NULL;
	private static $_http_method = NULL;
	private static $_plugin_name = NULL;
	private static $_plugin_class = NULL;
	
	private function __authenticate() {
		$logged_in = Symphony::Engine()->isLoggedIn();
		if (!$logged_in) self::sendError('API is private. Authentication failed.', 403);
	}
	
	public function isFrontendPageRequest($is_frontend_page=NULL) {
		if(isset($is_frontend_page)) self::$_is_frontend_page = $is_frontend_page;
		return self::$_is_frontend_page;
	}
	
	public function getOutputFormat() {
		return self::$_output_type;
	}
	
	public function getHTTPMethod() {
		return self::$_http_method;
	}
	
	public function init() {
		
		// store request parameters for later
		self::$_token = trim($_REQUEST['token']);
		self::$_output_type = (isset($_REQUEST['format']) ? $_REQUEST['format'] : 'xml');		
		self::$_uri = explode('/', trim($_REQUEST['url'], '/'));
		self::$_http_method = strtolower($_SERVER['REQUEST_METHOD']);
		
		// get plugin name from the first segment in the URL
		// and remove it from the URL segments list
		$plugin_name = strtolower(self::$_uri[0]);
		self::$_plugin_name = $plugin_name;
		self::$_plugin_class = 'REST_' . ucfirst($plugin_name);
		array_shift(self::$_uri);
		
		// include the plugin!
		require_once(EXTENSIONS . "/rest_api/plugins/$plugin_name/rest.$plugin_name.php");
		if (!class_exists(self::$_plugin_class)) REST_API::sendError(sprintf("Plugin '%s' does not exist.", self::$_plugin_class), 404);
		
		// perform global API authentication
		self::__authenticate($plugin_class);
		
		// initialise the plugin
		if(method_exists(self::$_plugin_class, 'init')) call_user_func(array(self::$_plugin_class, 'init'));
		
		// perform plugin authentication
		if(method_exists(self::$_plugin_class, 'authenticate')) call_user_func(array(self::$_plugin_class, 'authenticate'));
		
		if(method_exists(self::$_plugin_class, self::$_http_method)) {
			call_user_func(array(self::$_plugin_class, self::$_http_method));
		} else {
			REST_API::sendError(sprintf("Plugin '%s' does not support HTTP %s.", self::$_plugin_class, strtoupper($method)), 401);
		}
	}
	
	public function getRequestURI() {
		return self::$_uri;
	}
	
	public function sendOutput($response_body=NULL, $code=200) {
		
		switch($code) {
			case 200: header('HTTP/1.0 200 OK'); break;
			case 401: header('HTTP/1.0 401 Bad Request'); break;
			case 403: header('HTTP/1.0 403 Forbidden'); break;
			case 404: header('HTTP/1.0 404 Not Found'); break;
		}
		
		$xml = $response_body;
		if(is_array($xml)) $xml = reset($xml);
		if($xml instanceOf XMLElement) $xml = $xml->generate(TRUE);
		
		switch(self::$_output_type) {
			
			case 'json':
				header('Content-Type: text/plain; charset=utf-8');
				$output = json_encode(XMLToArray::convert($xml));				
			break;
			
			case 'serialise':
			case 'serialize':
				header('Content-Type: text/plain; charset=utf-8');
				$output = serialize(XMLToArray::convert($xml));
			break;
			
			case 'yaml':
				header('Content-Type: text/plain; charset=utf-8');
				require_once('spyc-0.4.5/spyc.php');
				$output = Spyc::YAMLDump(XMLToArray::convert($xml));
			break;
			
		 	case 'xml':
				header('Content-Type: text/xml; charset=utf-8');
				$output = $xml;
			break;
			
			case 'csv':
				header('Content-Type: text/plain; charset=utf-8');
				
				$entries = XMLToArray::convert($xml);
				$entries = $entries['response']['entry'];
				
				$file_name = sprintf('%s/%s-%d.csv', TMP, self::$_plugin_name, time());
				$csv = fopen($file_name, 'w');
				
				$columns = array();
				$rows = array();
				
				// iterate over all entries to build columns. do not assume that the
				// first entry has all fields (if a value is missing the field will not be present!)
				foreach($entries as $entry) {
					foreach($entry as $handle => $value) {
						if(!in_array($handle, $columns)) $columns[] = $handle;
					}
				}
				
				fputcsv($csv, $columns, ',', '"');
				
				foreach($entries as $entry) {
					$row = array();
					// build the data for each field in this entry
					foreach($columns as $column) {
						// use a "value" column if it exists
						if(isset($entry[$column]['value'])) {
							$value = $entry[$column]['value'];
						}
						// file upload fields use the filename column
						elseif(isset($entry[$column]['filename']['value'])) {
							$value = $entry[$column]['filename']['value'];
						}
						// return nothing for empty or unsupported fields
						else {
							$value = '';
						}
						$row[$column] = $value;
					}
					fputcsv($csv, $row, ',', '"');
				}
				
				fclose($csv);
				$output = file_get_contents($file_name);
				unlink($file_name);
				
			break;
		}
		
		echo $output;
		exit;
	}
	
	public function sendError($message=NULL, $code=200, $format=NULL) {
		if($format) self::$_output_type = $format;
		$response = new XMLElement('response', NULL, array(
			'result' => 'error',
			'code' => $code
		));
		$response->appendChild(new XMLElement('error', $message));
		self::sendOutput($response, $code);
	}
	
}