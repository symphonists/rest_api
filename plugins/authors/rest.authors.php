<?php

require_once(TOOLKIT . '/class.authormanager.php');

Class REST_Authors {
			
	public function get() {
		
		$url_parts = REST_API::getRequestURI();
		$author_url = $url_parts[0];

		$response = new XMLElement('response');

		$am = new AuthorManager(Frontend::instance());

		if (isset($author_url)) {
			if (is_numeric($author_url)) {
				$author = $am->fetchByID($author_url);	
			} else {
				$author = $am->fetchByUsername($author_url);
			}
			if(!$author) REST_API::sendError('Author not found.', 404);
			$response->appendChild(self::__buildAuthorXML($author));
		} else {
			$authors = $am->fetch();
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