<?php
	function multiarray_keys($ar) {				
		foreach($ar as $k => $v) {
			$keys[] = $k;
			if (is_array($ar[$k]))
				$keys = array_merge($keys, multiarray_keys($ar[$k]));
		}
		return $keys;
	}
	
	function multiarray_values($ar,$key) {		
		$values = array();
		foreach($ar as $k => $v) {
			if($k === $key) {
				$values[] = $v;
			}
			if (is_array($ar[$k]))
				$values = array_merge($values, multiarray_values($ar[$k],$key));
		}
		return $values;
	}

	function array_keys_exist($keys,$array) {
		if(count(array_intersect($keys,array_keys($array)))>0) {
			return true;
		}
	}

	function array_to_lower($array,$round = 0){
		foreach($array as $key => $value){
			if(is_array($value)) $array[strtolower($key)] =  $this->arraytolower($value,$round+1);
			else $array[strtolower($key)] = strtolower($value);
		}
		return $array;
	} 

	function implode_string($glue, $pieces) {
		array_walk($pieces, create_function('&$elem','$elem = "\'".trim($elem)."\'";'));
		return implode($glue, $pieces); 
	}
	

	// from CackePHP
	function debug($var = false, $showHtml = false, $showFrom = true) {
		if ($showFrom) {
			$calledFrom = debug_backtrace();
			print "<strong>".substr(str_replace(ROOT, "", $calledFrom[0]['file']), 1)."</strong> (line <strong>".$calledFrom[0]['line']."</strong>)";
		}
		print "\n<pre class=\"cake-debug\">\n";
		$var = print_r($var, true);

		if ($showHtml) {
			$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
		}
		print "{$var}\n</pre>\n";
	}

	function debug_log($error) {
		$fp = fopen(ROOT.'/cache/log.txt','w');
		fwrite($fp, $error);
		fclose($fp);
	}
?>