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

?>