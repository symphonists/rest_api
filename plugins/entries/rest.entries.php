<?php

require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');

Class REST_Entries {
		
	private static $_section_handle = NULL;
	private static $_section_id = NULL;
	private static $_entry_id = NULL;
	private static $_ds_params = array();
	
	public function setDatasourceParam($name, $value) {
		self::$_ds_params[$name] = $value;
	}
	
	public function getDatasourceParam($name) {
		return self::$_ds_params[$name];
	}
	
	public function getSectionId() {
		return self::$_section_id;
	}
	
	public function getSectionHandle() {
		return self::$_section_handle;
	}
	
	public function getEntryId() {
		return self::$_entry_id;
	}
	
	public function init() {
		
		if(REST_API::getOutputFormat() == 'csv' && !REST_API::getHTTPMethod() == 'get') {
			REST_API::sendError(sprintf(
				'%s output format not supported for %s requests.',
				strtoupper(REST_API::getOutputFormat()),
				strtoupper(REST_API::getHTTPMethod())
			), 401, 'xml');
		}
		
		$request_uri = REST_API::getRequestURI();
		
		self::$_section_handle = $request_uri[0];
		self::$_entry_id = $request_uri[1];
		
		$section_id = SectionManager::fetchIDFromHandle(self::$_section_handle);
		
		if (!$section_id) REST_API::sendError('Section not found.', 404);

		self::$_section_id = $section_id;
		
		self::setDatasourceParam('included_elements', $_REQUEST['fields']);
		self::setDatasourceParam('limit', $_REQUEST['limit']);
		self::setDatasourceParam('page', $_REQUEST['page']);
		self::setDatasourceParam('sort', $_REQUEST['sort']);
		self::setDatasourceParam('order', $_REQUEST['order']);
		self::setDatasourceParam('groupby', $_REQUEST['groupby']);
		
		$filters = $_REQUEST['filter'];
		if (!is_null($filters) && !is_array($filters)) $filters = array($filters);
		self::setDatasourceParam('filters', $filters);
		
	}
	
	public function delete() {

		$entry = EntryManager::fetch(self::$_entry_id);
		
		if(!$entry) {
			REST_API::sendError('Entry not found.', 404);
		} else {
			EntryManager::delete(self::$_entry_id);
			$response = new XMLElement('response', NULL, array(
				'id' => self::$_entry_id,
				'result' => 'success',
				'type' => 'deleted'
			));
			$response->appendChild(new XMLElement('message', 'Entry deleted successfully.'));
			REST_API::sendOutput($response);
		}

	}
	
	/*
	GET and POST instantiate a frontend page. This resolved to the "index" page of your site
	then replaces the page data sources/events with the REST API ones, and lets the page load
	Page delegates fire up to and including FrontendOutputPreGenerate, but _not_ any after, since
	the page does not fully load — we return the API response before the page XSLT transformation occurs
	*/
	public function post() {
		REST_API::isFrontendPageRequest(TRUE);
		Frontend::instance()->display(NULL);
	}
	
	public function get() {
		REST_API::isFrontendPageRequest(TRUE);
		Frontend::instance()->display(NULL);
	}
	
	public function sendOutput($xml) {

		switch(REST_API::getHTTPMethod()) {
			case 'get':
				$xml = $xml->getChildrenByName('response');
				if(is_array($xml)) $xml = reset($xml);
				REST_API::sendOutput($xml);
			break;
			case 'post':
				$xml = $xml->getChildrenByName('events');
				if(is_array($xml)) $xml = reset($xml);

				$xml = $xml->getChildrenByName('response');
				if(is_array($xml)) $xml = reset($xml);

				REST_API::sendOutput($xml);
			break;
		}
	}
	
}
