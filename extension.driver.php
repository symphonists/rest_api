<?php
	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	Class extension_rest_api extends Extension{
	
		public function about(){
			return array('name' => 'REST API',
						 'version' => '0.01',
						 'release-date' => '2009-07-27',
						 'author' => array('name' => 'Symphony Team',
										   'website' => 'http://symphony-cms.com',
										   'email' => 'team@symphony-cms.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),
						
						array(
							'page' => '/system/preferences/',
							'delegate' => 'Save',
							'callback' => '__SavePreferences'
						),
					);
		}
		
		public function uninstall(){

		}

		public function install(){

		}
		
		public function __SavePreferences($context){
			
			if(!is_array($context['settings'])) {
				$context['settings'] = array('rest_api' => array(
					'public' => 'no',
					'public_sections' => ''
				));
			}
			elseif(!isset($context['settings']['rest_api'])) {
				$context['settings']['rest_api'] = array(
					'public' => 'no',
					'public_sections' => ''
				);
			}
			// have to override the default saving here since config doesn't seem to
			// save arrays properly. here I force into a comma-delimeted string instead
			else {
				$context['settings']['rest_api'] = array(
					'public' => $context['settings']['rest_api']['public'],
					'public_sections' => implode(',', $context['settings']['rest_api']['public_sections'])
				);
			}
		}

		public function appendPreferences($context){

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'REST API'));			
			
			
			$label = Widget::Label();
			$input = Widget::Input('settings[rest_api][public]', 'yes', 'checkbox');
			if($this->_Parent->Configuration->get('public', 'rest_api') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Enable public access (bypass authentication)');
			$group->appendChild($label);

			
			$public_sections = explode(',', $this->_Parent->Configuration->get('public_sections', 'rest_api'));
			
			$sm = new SectionManager(Administration::instance());
			$sections = $sm->fetch();
			$options = array();
			foreach($sections as $section) {
				$options[] = array($section->get('handle'), in_array($section->get('handle'), $public_sections), $section->get('name'));
			}
			
			$label = Widget::Label();
			$select = Widget::Select('settings[rest_api][public_sections][]', $options, array('multiple' => 'multiple', 'style' => 'width:300px;'));
			$label->setValue('Public section access (only when public access is enabled) ' . $select->generate());
			$group->appendChild($label);
			
								
			$context['wrapper']->appendChild($group);
						
		}
		
			
	}