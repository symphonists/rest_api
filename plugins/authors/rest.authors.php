<?php

require_once(TOOLKIT . '/class.authormanager.php');

Class REST_Authors {
			
	public function run() {
		
		$url_parts = REST_API::getRequestURI();
		
		$section_url = $url_parts[0];
		
		$response = new XMLElement('response');
		
		REST_API::sendResponse($response);		
	}
	
}