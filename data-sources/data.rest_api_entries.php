<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	
	Class DatasourceREST_API_Entries extends Datasource{

		public $dsParamROOTELEMENT = 'response';
		public $dsParamORDER = 'desc';
		public $dsParamLIMIT = '20';
		public $dsParamREDIRECTONEMPTY = 'no';
		public $dsParamSORT = 'system:id';
		public $dsParamSTARTPAGE = '1';
		public $dsParamINCLUDEDELEMENTS = array('system:id');
		public $dsParamFILTERS = array();

		public function about(){
			return array(
					 'name' => 'REST API: Entries',
					 'author' => array(
							'name' => 'Nick Dunn',
							'website' => 'http://symphony-demo',
							'email' => 'nick.dunn@airlock.com'),
					 'version' => '1.0',
					 'release-date' => '2009-11-12T08:14:58+00:00');	
		}

		public function getSource(){
			return REST_Entries::getSectionId();
		}

		public function grab(&$param_pool){
			$result = new XMLElement($this->dsParamROOTELEMENT);
			
			// remove placeholder elements
			unset($this->dsParamINCLUDEDELEMENTS);

			// fill with all included elements if none are set
			if (is_null(REST_Entries::getDatasourceParam('included_elements'))) {
				// get all fields in this section
				$fields = Symphony::Database()->fetch(
					sprintf(
						"SELECT element_name FROM `tbl_fields` WHERE `parent_section` = %d",
						Symphony::Database()->cleanValue(REST_Entries::getSectionId())
					)
				);
				// add them to the data source
				foreach($fields as $field) {
					$this->dsParamINCLUDEDELEMENTS[] = $field['element_name'];
				}
				// also add pagination
				$this->dsParamINCLUDEDELEMENTS[] = 'system:pagination';
			}
			// if included elements are spcified, use them only
			else {
				$this->dsParamINCLUDEDELEMENTS = explode(',', REST_Entries::getDatasourceParam('included_elements'));
			}

			// fill the other parameters
			if (!is_null(REST_Entries::getDatasourceParam('limit'))) $this->dsParamLIMIT = REST_Entries::getDatasourceParam('limit');
			if (!is_null(REST_Entries::getDatasourceParam('page'))) $this->dsParamSTARTPAGE = REST_Entries::getDatasourceParam('page');
			if (!is_null(REST_Entries::getDatasourceParam('sort'))) $this->dsParamSORT = REST_Entries::getDatasourceParam('sort');
			if (!is_null(REST_Entries::getDatasourceParam('order'))) $this->dsParamORDER = REST_Entries::getDatasourceParam('order');

			if (!is_null(REST_Entries::getDatasourceParam('group_by'))) {
				$field = end(Symphony::Database()->fetch(
					sprintf(
						"SELECT id FROM `tbl_fields` WHERE `parent_section` = %d AND `element_name` = '%s'",
						Symphony::Database()->cleanValue(REST_Entries::getSectionId()),
						Symphony::Database()->cleanValue(REST_Entries::getDatasourceParam('group_by'))
					)
				));
				if ($field) $this->dsParamGROUP = $field['id'];
			}

			// if API is calling a known entry, filter on System ID only
			if (!is_null(REST_Entries::getEntryId())) {
				$this->dsParamFILTERS['id'] = REST_Entries::getEntryId();
			}
			// otherwise use filters
			elseif (REST_Entries::getDatasourceParam('filters')) {

				foreach(REST_Entries::getDatasourceParam('filters') as $field_handle => $filter_value) {
					$filter_value = rawurldecode($filter_value);
					$field_id = Symphony::Database()->fetchVar('id', 0, 
						sprintf(
							"SELECT `f`.`id` 
							FROM `tbl_fields` AS `f`, `tbl_sections` AS `s` 
							WHERE `s`.`id` = `f`.`parent_section` 
							AND f.`element_name` = '%s' 
							AND `s`.`handle` = '%s' LIMIT 1",
							Symphony::Database()->cleanValue($field_handle),
							Symphony::Database()->cleanValue(REST_Entries::getSectionHandle())
						)
					);
					if(is_numeric($field_id)) $this->dsParamFILTERS[$field_id] = $filter_value;
				}

			}			
			
			try{
				include(TOOLKIT . '/data-sources/datasource.section.php');
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}
			if($this->_force_empty_result) $result = $this->emptyXMLSet();

			return $result;		

		}

	}