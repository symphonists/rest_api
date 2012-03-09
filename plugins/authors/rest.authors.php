<?php

require_once(TOOLKIT . '/class.authormanager.php');

Class REST_Authors {
	
	public function init() {
		if(REST_API::getOutputFormat() == 'csv') {
			REST_API::sendError(sprintf('%s output format not supported.', strtoupper(REST_API::getOutputFormat())), 401, 'xml');
		}
	}
	
	public function get() {
		
		$url_parts = REST_API::getRequestURI();
		$author_url = $url_parts[0];

		$response = new XMLElement('response');

		if (isset($author_url)) {
			if (is_numeric($author_url)) {
				$author = AuthorManager::fetchByID($author_url);	
			} else {
				$author = AuthorManager::fetchByUsername($author_url);
			}
			if(!$author) REST_API::sendError('Author not found.', 404);
			$response->appendChild(self::__buildAuthorXML($author));
		} else {
			$authors = AuthorManager::fetch();
			foreach($authors as $author){
				$response->appendChild(self::__buildAuthorXML($author));
			}
		}
		
		REST_API::sendOutput($response);		
	}
	
	private function __buildAuthorXML($author){
		
		$author_xml = new XMLElement('author');

		foreach($author->get() as $key => $value){
			$value = General::sanitize($value);
			if ($value != '') {
				$author_xml->appendChild(
					new XMLElement(Lang::createHandle($key), General::sanitize($value))
				);
			}
		}
		
		return $author_xml;
	}
	
}