<?php

	require_once(TOOLKIT . '/class.event.php');
	
	Class EventREST_API_Entries extends Event{

		const ROOTELEMENT = 'response';

		public $eParamFILTERS = array();

		public static function about(){
			return array(
					 'name' => 'REST API: Entries',
					 'author' => array(
							'name' => 'Nick Dunn',
							'website' => 'http://symphony-demo',
							'email' => 'nick.dunn@airlock.com'),
					 'version' => '1.0',
					 'release-date' => '2009-11-13T11:35:07+00:00',
					 'trigger-condition' => 'action[api]');	
		}

		public static function getSource(){
			return REST_Entries::getSectionId();
		}

		public static function documentation(){
			return '';
		}

		public function load(){			
			return $this->__trigger();
		}

		protected function __trigger(){
			
			if (is_array($_POST['fields'][0])) {
				$this->eParamFILTERS = array('expect-multiple');
			} elseif (!is_null(REST_Entries::getEntryId())) {
				$_POST['id'] = REST_Entries::getEntryId();
			}
			
			include(TOOLKIT . '/events/event.section.php');
			return $result;
		}		

	}