<?php

require_once(TOOLKIT . '/class.datasourcemanager.php');
require_once(TOOLKIT . '/class.eventmanager.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');

Class Rest_Entries {
		
	protected static $section_handle = null;
	protected static $section_id = null;	
	protected static $entry_id = null;	
	protected static $ds_included_elements = null;
	protected static $ds_limit = null;
	protected static $ds_page = null;
	protected static $ds_sort = null;
	protected static $ds_order = null;
	protected static $ds_groupby = null;
	protected static $ds_filters = null;
	
	public function run() {
		
		$url_parts = REST_API::getURI();
		
		$section_handle = $url_parts[0];
		$entry_id = $url_parts[1];
		
		$sm = new SectionManager(REST_API::getContext());
		$section_id = $sm->fetchIDFromHandle($section_handle);
		
		if (!$section_id) {
			REST_API::sendError('You must specify a section handle.');
		} else {
			self::$section_id = $section_id;
			self::$section_handle = $section_handle;
		}
				
		self::$entry_id = $entry_id;
		
		self::$ds_included_elements = $_GET['include'];
		self::$ds_limit = $_GET['limit'];
		self::$ds_page = $_GET['page'];
		self::$ds_sort = $_GET['sort'];
		self::$ds_order = $_GET['order'];
		self::$ds_groupby = $_GET['groupby'];
		
		self::$ds_filters = $_GET['filter'];
		if (!is_null(self::$ds_filters) && !is_array(self::$ds_filters)) {
			self::$ds_filters = array(self::$ds_filters);
		}
		
		if ($_POST) {
			self::processEvent();
		} else {	
			self::processDataSource();
		}
		
	}
	
	public function getSource() {
		return self::$section_id;
	}
		
	public function processEvent() {
		$eventManager = new EventManager(REST_API::getContext());
		$event = $eventManager->create('api', NULL, false);
		
		$entry_id = self::$entry_id;
		
		if (is_array($_POST['fields'][0])) {
			$event->eParamFILTERS = array('expect-multiple');
		} elseif ($entry_id) {
			$_POST['id'] = $entry_id;
		}
		
		REST_API::sendOutput($event->load());
	}
	
	public function processDataSource() {
		
		// instantiate the "API" datasource
		$datasourceManager = new DatasourceManager(REST_API::getContext());
		$ds = $datasourceManager->create('api', NULL, false);

		// remove placeholder elements
		unset($ds->dsParamINCLUDEDELEMENTS);

		// fill included elements if none are set
		if (is_null(self::$ds_included_elements)) {
			$fields = REST_API::getContext()->Database->fetch(
				sprintf(
					"SELECT element_name FROM `tbl_fields` WHERE `parent_section` = %d",
					self::$section_id
				)
			);
			foreach($fields as $field) {
				$ds->dsParamINCLUDEDELEMENTS[] = $field['element_name'];
			}
		}
		else {
			$ds->dsParamINCLUDEDELEMENTS = explode(',', self::$ds_included_elements);
		}

		// fill the other parameters
		if (!is_null(self::$ds_limit)) $ds->dsParamLIMIT = self::$ds_limit;
		if (!is_null(self::$ds_page)) $ds->dsParamSTARTPAGE = self::$ds_page;
		if (!is_null(self::$ds_sort)) $ds->dsParamSORT = self::$ds_sort;
		if (!is_null(self::$ds_order)) $ds->dsParamORDER = self::$ds_order;
		if (!is_null(self::$ds_groupby)) {
			$field = end(REST_API::getContext()->Database->fetch(
				sprintf(
					"SELECT id FROM `tbl_fields` WHERE `parent_section` = %d AND `element_name` = '%s'",
					self::$section_id,
					self::$ds_groupby
				)
			));
			if ($field) $ds->dsParamGROUP = $field['id'];
		}
		
		
		$entry_id = self::$entry_id;
		
		if ($entry_id) {
			$ds->dsParamFILTERS['id'] = $entry_id;
		}		
		elseif (self::$ds_filters) {
			
			$fm = new FieldManager(REST_API::getContext());
			
			foreach(self::$ds_filters as $field_handle => $filter_value) {				
				$filter_value = rawurldecode($filter_value);
				$field_id = REST_API::getContext()->Database->fetchVar('id', 0, 
					sprintf(
						"SELECT `f`.`id` 
						FROM `tbl_fields` AS `f`, `tbl_sections` AS `s` 
						WHERE `s`.`id` = `f`.`parent_section` 
						AND f.`element_name` = '%s' 
						AND `s`.`handle` = '%s' LIMIT 1",
						$field_handle,
						self::$section_handle
					)
				);
				if(is_numeric($field_id)) $ds->dsParamFILTERS[$field_id] = $filter_value;				
			}
			
		}

		REST_API::sendOutput($ds->grab());
	}
	
}