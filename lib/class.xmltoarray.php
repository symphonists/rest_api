<?php
/*
Converts a string of XML to a keyed array
Adapted from http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
*/
Class XMLToArray {
	
	public static function convert($string) {
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);	
		xml_parse_into_struct($parser, $string, $vals, $index);
		xml_parser_free($parser);

		$return = array();
		$ary = &$return;

		foreach ($vals as $r) {

			$t = $r['tag'];

			if ($r['type'] == 'open' || $r['type']=='complete') {
				
				if (isset($ary[$t])) {
					if (isset($ary[$t][0])) {
						$ary[$t][] = array();
					} else {
						$ary[$t] = array($ary[$t], array());
					}
					$cv = &$ary[$t][count($ary[$t])-1];
				} else {
					$cv = &$ary[$t];
				}
				if (isset($r['attributes'])) {
					foreach ($r['attributes'] as $k => $v) {
						$cv['_' . $k] = $v;
					}
				}
			}

			if ($r['type'] == 'open') {
				//$cv[] = array();
				$cv['_p'] = &$ary;
				$ary = &$cv;
			}
			elseif ($r['type']=='complete') {
				$cv['value'] = (isset($r['value']) ? $r['value'] : '');
			}
			elseif ($r['type']=='close') {
				$ary = &$ary['_p'];
			}
		}    

		self::deleteParents($return);
		return $return;
	}

	// Remove recursion in result array
	private function deleteParents(&$ary) {
		foreach ($ary as $k => $v) {
			if ($k === '_p') {
				unset($ary[$k]);
			}
			elseif (is_array($ary[$k])) {
				self::deleteParents($ary[$k]);
			}
		}
	}
	
}