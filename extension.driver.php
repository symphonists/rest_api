<?php
	
	Class extension_rest_api extends Extension{
	
		public function about(){
			return array('name' => 'REST API',
						 'version' => '1.0.0',
						 'release-date' => '2011-03-22',
						 'author' => array(
							'name' => 'Nick Dunn',
							'website' => 'http://nick-dunn.co.uk')
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
		
			
	}