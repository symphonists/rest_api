<?php

Class REST_API {
	
	protected static $context = null;
	
	protected static $token = null;
	protected static $output_type = null;
	
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
	
	public function buildContext($context) {
		
		self::$context = $context;
		
		$url_parts = explode('/', trim($_GET['url'], '/'));
		$section_handle = $url_parts[0];
		$entry_id = $url_parts[1];
		
		$sm = new SectionManager(self::$context);
		$section_id = $sm->fetchIDFromHandle($section_handle);
		
		if (!$section_id) {
			self::sendError('Section not found.');
		} else {
			self::$section_id = $section_id;
			self::$section_handle = $section_handle;
		}
		
		self::authenticate();
		
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
		
		self::$token = trim($_REQUEST['token']);		
		self::$output_type = (isset($_GET['output']) ? $_GET['output'] : 'xml');

	}
	
	public function getSource() {
		return self::$section_id;
	}
	
	public function authenticate() {
		$expire_login = false;
		$logged_in = false;

		// in public mode?
		$public = (self::$context->Configuration->get('public', 'rest_api') == 'yes') ? true : false;

		// log in user from cookie or by token passed
		if (!$logged_in) $logged_in = self::$context->isLoggedIn();
		if (!$logged_in) {
			$logged_in = self::$context->loginFromToken(self::$token);
			$expire_login = true;
		}

		// if private and no log in...
		if (!$public && !$logged_in) self::sendError('Could not authenticate.');

		// public, but user not authenticated, check against section whitelist
		if ($public && !$logged_in && !in_array(self::$section_handle, explode(',', self::$context->Configuration->get('public_sections', 'rest_api')))) {
			self::sendError(sprintf('No public access to the section "%s".', self::$section_handle));
		}
		
		if ($expire_login) self::$context->logout();
	}
	
	public function processEvent() {
		$eventManager = new EventManager(self::$context);
		$event = $eventManager->create('api', NULL, false);
		
		$entry_id = self::$entry_id;
		
		if (is_array($_POST['fields'][0])) {
			$event->eParamFILTERS = array('expect-multiple');
		} elseif ($entry_id) {
			$_POST['id'] = $entry_id;
		}
		
		self::sendOutput($event->load());
	}
	
	public function processDataSource() {
		
		// instantiate the "API" datasource
		$datasourceManager = new DatasourceManager(self::$context);
		$ds = $datasourceManager->create('api', NULL, false);

		// remove placeholder elements
		unset($ds->dsParamINCLUDEDELEMENTS);

		// fill included elements if none are set
		if (is_null(self::$ds_included_elements)) {
			$fields = self::$context->Database->fetch(
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
			$field = end(self::$context->Database->fetch(
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
			
			$fm = new FieldManager(self::$context);
			
			foreach(self::$ds_filters as $field_handle => $filter_value) {				
				$filter_value = rawurldecode($filter_value);
				$field_id = self::$context->Database->fetchVar('id', 0, 
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

		self::sendOutput($ds->grab());
	}
	
	public function sendOutput($response) {
		
		switch(self::$output_type) {
			case 'json':
				header('Content-Type: text/plain');
				$output = json_encode(self::generateArray($response));
			break;
			case 'serialise':
				header('Content-Type: text/plain');
				$output = serialize(self::generateArray($response));
				break;
			case 'yaml':
				header('Content-Type: text/yaml');
				require_once('spyc-0.4.5/spyc.php');
				$output = Spyc::YAMLDump(self::generateArray($response));
				break;
		 	case 'xml':
				header('Content-Type: text/xml');
				$output = $response->generate(true);
			break;
		}
		
		echo $output;
		exit;
	}
	
	public function sendError($message) {
		$response = new XMLElement('response');
		$response->appendChild(new XMLElement('error', $message));
		self::sendOutput($response);
	}
	
	public function generateArray($node, $is_field_data=false){

		$result = array();

		if(count($node->getAttributes()) > 0){
			foreach($node->getAttributes() as $attribute => $value ){
				if(strlen($value) != 0){
					$result[$node->getName()]['_' . $attribute] = $value;
				}
			}				
		}

		$value = $node->getValue();
		if (!is_null($value)) $result[$node->getName()]['value'] = $node->getValue();

		$numberOfchildren = $node->getNumberOfChildren();

		if($numberOfchildren > 0 || strlen($node->getValue()) != 0){

			if($numberOfchildren > 0 ) {

				foreach($node->getChildren() as $child) {

					$next_child_is_field_data = ($child->getName() == 'entry');

					if ($is_field_data == true) {
						if(($child instanceof XMLElement)) $result[$node->getName()]['fields'][] = self::generateArray($child, $next_child_is_field_data);
					} else {
						if(($child instanceof XMLElement)) $result[$node->getName()][] = self::generateArray($child, $next_child_is_field_data);
					}
				}

			}			
		}

		return $result;
	}
	
	
}