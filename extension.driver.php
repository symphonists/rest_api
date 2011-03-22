<?php
	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	Class extension_rest_api extends Extension{
	
		public function about(){
			return array('name' => 'REST API',
						 'version' => '0.1',
						 'release-date' => '2009-07-27',
						 'author' => array(
							'name' => 'Nick Dunn',
							'website' => 'http://nick-dunn.co.uk')
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
							'callback' => 'savePreferences'
						),
					);
		}
		
		public function uninstall(){
			$htaccess = @file_get_contents(DOCROOT . '/.htaccess');
			if($htaccess === FALSE) return FALSE;
			
			$htaccess = self::__removeAPIRules($htaccess);
			return @file_put_contents(DOCROOT . '/.htaccess', $htaccess);
		}

		public function install(){
			$htaccess = @file_get_contents(DOCROOT . '/.htaccess');
			if($htaccess === FALSE) return FALSE;
			
			$token = md5(time());
			
			// Find out if the rewrite base is another other than /
			$rewrite_base = NULL;
			if(preg_match('/RewriteBase\s+([^\s]+)/i', $htaccess, $match)){
				$rewrite_base = trim($match[1], '/') . '/';
			}
			
			$rule = "
	### START API RULES
	RewriteRule ^symphony\/api(\/(.*\/?))?$ {$rewrite_base}extensions/rest_api/handler.php?url={$token}&%{QUERY_STRING}	[NC,L]
	### END API RULES\n\n";
			
			$htaccess = self::__removeAPIRules($htaccess);
			
			$htaccess = preg_replace('/RewriteRule .\* - \[S=14\]\s*/i', "RewriteRule .* - [S=14]\n{$rule}\t", $htaccess);
			$htaccess = str_replace($token, '$1', $htaccess);
			
			return @file_put_contents(DOCROOT . '/.htaccess', $htaccess);
			
		}
		
		private static function __removeAPIRules($htaccess){
			return preg_replace('/### START API RULES(.)+### END API RULES[\n]/is', NULL, $htaccess);
		}
		
		public function savePreferences($context){
			
			/*
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
			*/
		}

		public function appendPreferences($context){

			/*

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'REST API'));			
			
			
			$label = Widget::Label();
			$input = Widget::Input('settings[rest_api][public]', 'yes', 'checkbox');
			if(Symphony::Configuration()->get('public', 'rest_api') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Enable public access (bypass authentication)');
			$group->appendChild($label);
			
			$public_sections = explode(',', Symphony::Configuration()->get('public_sections', 'rest_api'));
			
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
			
			*/
						
		}
		
			
	}