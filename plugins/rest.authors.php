<?php

require_once(TOOLKIT . '/class.authormanager.php');

Class Rest_Authors {
			
	public function run() {
		
		$url_parts = REST_API::getURI();
		
		$section_url = $url_parts[0];
		
		$response = new XMLElement('response');
		
		// blah blah blah
			
		REST_API::sendOutput($response);		
	}
	
}