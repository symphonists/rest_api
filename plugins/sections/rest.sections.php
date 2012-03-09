<?php

require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');

Class REST_Sections {
	
	private static $_sections = NULL;
	private static $_field_attributes = array('id', 'label', 'type', 'sortorder', 'location', 'show_column');
	
	public function init() {
		
		if(REST_API::getOutputFormat() == 'csv') {
			REST_API::sendError(sprintf('%s output format not supported.', strtoupper(REST_API::getOutputFormat())), 401, 'xml');
		}
		
		$request_uri = REST_API::getRequestURI();
		
		$section_reference = $request_uri[0];
		$sections = NULL;
		
		if(is_null($section_reference)) {
			$sections = SectionManager::fetch();
		} elseif(is_numeric($section_reference)) {
			$sections = SectionManager::fetch($section_reference);
		} else {
			$section_id = SectionManager::fetchIDFromHandle($section_reference);
			if($section_id) $sections = SectionManager::fetch($section_id);
		}
		
		if(!is_array($sections)) $sections = array($sections);
		
		if (!reset($sections) instanceOf Section) REST_API::sendError(sprintf("Section '%s' not found.", $section_reference), 404);
		
		self::$_sections = $sections;
	}
	
	public function get() {
			
		$response = new XMLElement('response');
		
		foreach(self::$_sections as $section) {
			
			$section_xml = new XMLElement('section');
			
			$meta = $section->get();
			foreach($meta as $key => $value) $section_xml->setAttribute(Lang::createHandle($key), $value);
			
			$fields = $section->fetchFields();
			
			foreach($fields as $field) {
				$meta = $field->get();
				unset($meta['field_id']);
				
				$field_xml = new XMLElement($meta['element_name'], null);					
				
				foreach(self::$_field_attributes as $attr) $field_xml->setAttribute(Lang::createHandle($attr), $meta[$attr]);
				
				foreach($meta as $key => $value) {
					if (in_array($key, self::$_field_attributes)) continue;
					$value = General::sanitize($value);
					if ($value != '') {
						$field_xml->appendChild(new XMLElement(Lang::createHandle($key), General::sanitize($value)));
					}
				}
				
				$section_xml->appendChild($field_xml);
			}
			
			$response->appendChild($section_xml);
			
		}
		
		REST_API::sendOutput($response);
		
	}
	
}