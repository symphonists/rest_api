<?php
	
	Class extension_rest_api extends Extension{
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPageResolved',
					'callback'	=> 'manipulateResolvedPage'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendOutputPreGenerate',
					'callback'	=> 'frontendOutputPreGenerate'
				)
			);
		}
		
		public function manipulateResolvedPage($context) {
			if(!class_exists('REST_API') || (class_exists('REST_API') && !REST_API::isFrontendPageRequest())) return;
			// get the page data from context
			$page = $context['page_data'];

			if(REST_API::getHTTPMethod() == 'get') $page['data_sources'] = 'rest_api_entries';
			if(REST_API::getHTTPMethod() == 'post') $page['events'] = 'rest_api_entries';
			
			$context['page_data'] = $page;
		}
		
		public function frontendOutputPreGenerate($context) {
			if(class_exists('REST_API') && class_exists('REST_Entries') && REST_API::isFrontendPageRequest()) {
				REST_Entries::sendOutput($context['xml']);
			}
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
			
			$rule = "
	### START API RULES
	RewriteRule ^symphony\/api(\/(.*\/?))?$ extensions/rest_api/handler.php?url={$token}&%{QUERY_STRING}	[NC,L]
	### END API RULES\n\n";
			
			$htaccess = self::__removeAPIRules($htaccess);
			$htaccess = preg_replace('/RewriteRule .\* - \[S=14\]\s*/i', "RewriteRule .* - [S=14]\n{$rule}\t", $htaccess);
			$htaccess = str_replace($token, '$1', $htaccess);
			
			return file_put_contents(DOCROOT . '/.htaccess', $htaccess);
			
		}
		
		private static function __removeAPIRules($htaccess){
			return preg_replace('/### START API RULES(.)+### END API RULES[\n]/is', NULL, $htaccess);
		}
				
	}