<?php

	require_once(TOOLKIT . '/class.event.php');
	
	Class Event_REST_API extends Event{

		const ROOTELEMENT = 'response';

		public $eParamFILTERS = array();

		public static function about(){
			return array(
					 'name' => 'API',
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
			include(TOOLKIT . '/events/event.section.php');
			return $result;
		}		

	}