<?php

require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');

require_once(EXTENSIONS . '/rest_api/plugins/entries/class.datasource_rest_api.php');
require_once(EXTENSIONS . '/rest_api/plugins/entries/class.event_rest_api.php');

Class REST_Entries {
		
	private static $_section_handle = NULL;
	private static $_section_id = NULL;
	private static $_entry_id = NULL;
	private static $_ds_params = array();
	
	private function setDatasourceParam($name, $value) {
		self::$_ds_params[$name] = $value;
	}
	
	private function getDatasourceParam($name) {
		return self::$_ds_params[$name];
	}
	
	public function getSectionId() {
		return self::$_section_id;
	}
	
	/*public function authenticate() {
		// API is public so check against section whitelist
		if (REST_API::isPublic() && !in_array(self::$_section_handle, REST_API::getPublicSections())) {
			REST_API::sendError(sprintf('No public access to the section "%s".', self::$_section_handle), 403);
		}
	}*/
	
	public function init() {
		
		$request_uri = REST_API::getRequestURI();
		
		self::$_section_handle = $request_uri[0];
		self::$_entry_id = $request_uri[1];
		
		$sm = new SectionManager(Frontend::instance());
		$section_id = $sm->fetchIDFromHandle(self::$_section_handle);
		
		if (!$section_id) REST_API::sendError('Section not found.', 404);

		self::$_section_id = $section_id;
		
		self::setDatasourceParam('included_elements', $_REQUEST['include']);
		self::setDatasourceParam('limit', $_REQUEST['limit']);
		self::setDatasourceParam('page', $_REQUEST['page']);
		self::setDatasourceParam('sort', $_REQUEST['sort']);
		self::setDatasourceParam('order', $_REQUEST['order']);
		self::setDatasourceParam('groupby', $_REQUEST['groupby']);
		
		$filters = $_REQUEST['filter'];
		if (!is_null($filters) && !is_array($filters)) $filters = array($filters);
		self::setDatasourceParam('filters', $filters);
		
	}
		
	public function post() {

		$event = new Event_REST_API(Frontend::instance(), array());
		
		if (is_array($_POST['fields'][0])) {
			$event->eParamFILTERS = array('expect-multiple');
		} elseif (!is_null(self::$_entry_id)) {
			$_POST['id'] = self::$_entry_id;
		}
		
		REST_API::sendOutput($event->load());
	}
	
	public function get() {
		
		// instantiate the "REST API" datasource
		$ds = new Datasource_REST_API(Frontend::instance(), array(), FALSE);

		// remove placeholder elements
		unset($ds->dsParamINCLUDEDELEMENTS);

		// fill with all included elements if none are set
		if (is_null(self::getDatasourceParam('included_elements'))) {
			// get all fields in this section
			$fields = Frontend::instance()->Database->fetch(
				sprintf(
					"SELECT element_name FROM `tbl_fields` WHERE `parent_section` = %d",
					Frontend::instance()->Database->cleanValue(self::$_section_id)
				)
			);
			// add them to the data source
			foreach($fields as $field) {
				$ds->dsParamINCLUDEDELEMENTS[] = $field['element_name'];
			}
			// also add pagination
			$ds->dsParamINCLUDEDELEMENTS[] = 'system:pagination';
		}
		// if included elements are spcified, use them only
		else {
			$ds->dsParamINCLUDEDELEMENTS = explode(',', self::getDatasourceParam('included_elements'));
		}

		// fill the other parameters
		if (!is_null(self::getDatasourceParam('limit'))) $ds->dsParamLIMIT = self::getDatasourceParam('limit');
		if (!is_null(self::getDatasourceParam('page'))) $ds->dsParamSTARTPAGE = self::getDatasourceParam('page');
		if (!is_null(self::getDatasourceParam('sort'))) $ds->dsParamSORT = self::getDatasourceParam('sort');
		if (!is_null(self::getDatasourceParam('order'))) $ds->dsParamORDER = self::getDatasourceParam('order');
		
		if (!is_null(self::getDatasourceParam('group_by'))) {
			$field = end(Frontend::instance()->Database->fetch(
				sprintf(
					"SELECT id FROM `tbl_fields` WHERE `parent_section` = %d AND `element_name` = '%s'",
					Frontend::instance()->Database->cleanValue(self::$_section_id),
					Frontend::instance()->Database->cleanValue(self::getDatasourceParam('group_by'))
				)
			));
			if ($field) $ds->dsParamGROUP = $field['id'];
		}
		
		// if API is calling a known entry, filter on System ID only
		if (!is_null(self::$_entry_id)) {
			$ds->dsParamFILTERS['id'] = self::$_entry_id;
		}
		// otherwise use filters
		elseif (self::getDatasourceParam('filters')) {
			
			$fm = new FieldManager(Frontend::instance());
			
			foreach(self::getDatasourceParam('filters') as $field_handle => $filter_value) {
				$filter_value = rawurldecode($filter_value);
				$field_id = Frontend::instance()->Database->fetchVar('id', 0, 
					sprintf(
						"SELECT `f`.`id` 
						FROM `tbl_fields` AS `f`, `tbl_sections` AS `s` 
						WHERE `s`.`id` = `f`.`parent_section` 
						AND f.`element_name` = '%s' 
						AND `s`.`handle` = '%s' LIMIT 1",
						Frontend::instance()->Database->cleanValue($field_handle),
						Frontend::instance()->Database->cleanValue(self::$_section_handle)
					)
				);
				if(is_numeric($field_id)) $ds->dsParamFILTERS[$field_id] = $filter_value;
			}
			
		}

		$params = array();
		REST_API::sendOutput($ds->grab($params));
	}
	
}