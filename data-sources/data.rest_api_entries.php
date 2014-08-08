<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(EXTENSIONS . '/rest_api/plugins/entries/rest.entries.php');
	
	Class DatasourceREST_API_Entries extends SectionDatasource {

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
			// remove placeholder elements
			unset($this->dsParamINCLUDEDELEMENTS);

			// fill with all included elements if none are set
			if (is_null(REST_Entries::getDatasourceParam('included_elements'))) {
				// get all fields in this section
				$fields = FieldManager::fetchFieldsSchema(REST_Entries::getSectionId());

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

			// Do grouping
			if (!is_null(REST_Entries::getDatasourceParam('groupby'))) {
				$field_id = FieldManager::fetchFieldIDFromElementName(
					REST_Entries::getDatasourceParam('groupby'),
					REST_Entries::getSectionId()
				);
				if ($field_id) $this->dsParamGROUP = $field_id;
			}

			// if API is calling a known entry, filter on System ID only
			if (!is_null(REST_Entries::getEntryId())) {
				$this->dsParamFILTERS['id'] = REST_Entries::getEntryId();
			}
			// otherwise use filters
			elseif (REST_Entries::getDatasourceParam('filters')) {

				foreach(REST_Entries::getDatasourceParam('filters') as $field_handle => $filter_value) {
					$filter_value = rawurldecode($filter_value);
					$field_id = FieldManager::fetchFieldIDFromElementName(
						$field_handle, 
						REST_Entries::getSectionId()
					);
					if(is_numeric($field_id)) $this->dsParamFILTERS[$field_id] = $filter_value;
				}

			}

			return $this->execute($param_pool);
		}

	}