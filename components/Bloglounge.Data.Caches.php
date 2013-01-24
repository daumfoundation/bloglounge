<?php
	class Caches {
		var $_error;
		var $_datas = array();

		function Caches() {
			return true;
		}

		function get($name) {
			if(isset($this->_datas[$name])) return $this->_datas[$name];
			return false;
		}

		function gets($names) {
			global $database, $db;
		
			$names = explode(',',$names);

			$result = array();
			
			foreach($names as $name) {
				if(isset($this->_datas[$name])) {
					$result[$name] = $this->_datas[$name];
				} else {
					return false;
				}
			}
			
			$data = array();
				
			foreach($result as $item) {
				array_push($data, $item);
			}
			
			return $data;
		}

		function set($name, $value) {
			$this->_datas[$name] = $value;

			return true;
		}
	}
?>