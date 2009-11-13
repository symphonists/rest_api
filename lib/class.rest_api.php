<?php

Class REST_API {
	
	public static $parameters = array();
	public static $output_type = null;
	
	public function setParameters() {
		
		$url = explode('/', trim($_GET['url'], '/'));
		$section = $url[0];
		$id = $url[1];
		
		$sm = new SectionManager(Frontend::instance());
		$section_id = $sm->fetchIDFromHandle($section);
		
		self::$parameters['section-handle'] = $section;
		self::$parameters['section'] = $section_id;
		
		self::$parameters['entry_id'] = $id;
		
		self::$parameters['included_elements'] = $_GET['include'];
		self::$parameters['limit'] = $_GET['limit'];
		self::$parameters['page'] = $_GET['page'];
		self::$parameters['sort'] = $_GET['sort'];
		self::$parameters['order'] = $_GET['order'];
		self::$parameters['groupby'] = $_GET['groupby'];
		
		self::$parameters['filters'] = $_GET['filter'];
		if (!is_null(self::$parameters['filters']) && !is_array(self::$parameters['filters'])) {
			self::$parameters['filters'] = array(self::$parameters['filters']);
		}
		
		self::$parameters['token'] = trim($_REQUEST['token']);		
		self::$output_type = (isset($_GET['output']) ? $_GET['output'] : 'xml');
	}
	
	public function getParam($param) {
		return self::$parameters[$param];
	}
	
	public function authenticate($frontend) {
		$expire_login = false;
		$logged_in = false;

		// in public mode?
		$public = ($frontend->Configuration->get('public', 'rest_api') == 'yes') ? true : false;

		// log in user from cookie or by token passed
		if (!$logged_in) $logged_in = $frontend->isLoggedIn();
		if (!$logged_in) {
			$logged_in = $frontend->loginFromToken(self::getParam('token'));
			$expire_login = true;
		}

		// if private and no log in...
		if (!$public && !$logged_in) self::sendError('Could not authenticate.');

		// public, but user not authenticated, check against section whitelist
		if ($public && !$logged_in && !in_array(self::getParam('section-handle'), explode(',', $frontend->Configuration->get('public_sections', 'rest_api')))) {
			self::sendError(sprintf('No public access to the section "%s".', self::getParam('section-handle')));
		}
		
		if ($expire_login) $frontend->logout();
	}
	
	public function processEvent($frontend) {
		$eventManager = new EventManager($frontend);
		$event = $eventManager->create('api', NULL, false);
		
		$entry_id = self::getParam('entry_id');
		
		if (is_array($_POST['fields'][0])) {
			$event->eParamFILTERS = array('expect-multiple');
		} elseif ($entry_id) {
			$_POST['id'] = $entry_id;
		}
		
		self::sendOutput($event->load());
	}
	
	public function processDataSource($frontend) {
		
		// instantiate the "API" datasource
		$datasourceManager = new DatasourceManager($frontend);
		$ds = $datasourceManager->create('api', NULL, false);

		// remove placeholder elements
		unset($ds->dsParamINCLUDEDELEMENTS);

		// fill included elements if none are set
		if (is_null(REST_API::getParam('included_elements'))) {
			$fields = $frontend->Database->fetch(
				sprintf(
					"SELECT element_name FROM `tbl_fields` WHERE `parent_section` = %d",
					REST_API::getParam('section')
				)
			);
			foreach($fields as $field) {
				$ds->dsParamINCLUDEDELEMENTS[] = $field['element_name'];
			}
		}
		else {
			$ds->dsParamINCLUDEDELEMENTS = explode(',', REST_API::getParam('included_elements'));
		}

		// fill the other parameters
		if (!is_null(REST_API::getParam('limit'))) $ds->dsParamLIMIT = REST_API::getParam('limit');
		if (!is_null(REST_API::getParam('page'))) $ds->dsParamSTARTPAGE = REST_API::getParam('page');
		if (!is_null(REST_API::getParam('sort'))) $ds->dsParamSORT = REST_API::getParam('sort');
		if (!is_null(REST_API::getParam('order'))) $ds->dsParamORDER = REST_API::getParam('order');
		if (!is_null(REST_API::getParam('groupby'))) {
			$field = end($frontend->Database->fetch(
				sprintf(
					"SELECT id FROM `tbl_fields` WHERE `parent_section` = %d AND `element_name` = '%s'",
					REST_API::getParam('section'),
					REST_API::getParam('groupby')
				)
			));
			if ($field) $ds->dsParamGROUP = $field['id'];
		}
		
		
		$entry_id = self::getParam('entry_id');
		
		if ($entry_id) {
			$ds->dsParamFILTERS['id'] = $entry_id;
		}		
		elseif (self::$parameters['filters']) {
			
			$fm = new FieldManager($frontend);
			
			foreach(self::$parameters['filters'] as $field_handle => $filter_value) {				
				$filter_value = rawurldecode($filter_value);
				$field_id = $frontend->Database->fetchVar('id', 0, 
					sprintf(
						"SELECT `f`.`id` 
						FROM `tbl_fields` AS `f`, `tbl_sections` AS `s` 
						WHERE `s`.`id` = `f`.`parent_section` 
						AND f.`element_name` = '%s' 
						AND `s`.`handle` = '%s' LIMIT 1",
						$field_handle,
						self::getParam('section-handle')
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
				$output = json_encode(REST_API::generateArray($response));
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