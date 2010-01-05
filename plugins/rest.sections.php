<?php

require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');

Class Rest_Sections {
		
	protected static $section = null;
	
	private static $field_attributes = array('id', 'label', 'type', 'sortorder', 'location', 'show_column');
	private static $incompatible_publishpanel = array('mediathek', 'imagecropper', 'readonlyinput');
	
	public function run() {
		
		$url_parts = REST_API::getURI();
		
		$section_url = $url_parts[0];		
		$sm = new SectionManager(REST_API::getContext());
		
		if (isset($section_url)) {
			if (is_numeric($section_url)) {
				$section_id = (int)$section_url;	
			} else {
				$section_id = (int)$sm->fetchIDFromHandle($section_url);
			}
		}
		
		if (is_null($section_id) || $section_id > 0) $sections = $sm->fetch($section_id);
		if (!is_array($sections)) $sections = array($sections);
		
		if (!reset($sections) instanceOf Section) {			
			REST_API::sendError('Section not found.');
						
		} else {
			
			$response = new XMLElement('response');
			
			foreach($sections as $section) {
				
				$section_xml = new XMLElement('section');
				
				$meta = $section->get();
				foreach($meta as $key => $value) {
					$section_xml->setAttribute(Lang::createHandle($key), $value);
				}				
				
				$fields = $section->fetchFields();
				
				foreach($fields as $field) {
					$meta = $field->get();
					unset($meta['field_id']);
					
					$field_xml = new XMLElement($meta['element_name'], null);					
					
					foreach(self::$field_attributes as $attr) {
						$field_xml->setAttribute(Lang::createHandle($attr), $meta[$attr]);
					}
					
					foreach($meta as $key => $value) {
						if (in_array($key, self::$field_attributes)) continue;
						$value = General::sanitize($value);
						if ($value != '') {
							$field_xml->appendChild(new XMLElement(Lang::createHandle($key), General::sanitize($value)));
						}
					}
					
					if (!in_array($meta['type'], self::$incompatible_publishpanel)) {
						$publish_html = new XMLElement('publish-panel');
						$html = $field->displayPublishPanel($publish_html);
						$field_xml->appendChild($publish_html);
					}
					
					$section_xml->appendChild($field_xml);
				}
				
				$response->appendChild($section_xml);
				
			}
			
			REST_API::sendOutput($response);
		}
		
	}
	
}