<?php
	class ServiceSettings {
		var $_error;

		function ServiceSettings() {
			global $database, $db;
			if ($db->query('SELECT * FROM '.$database['prefix'].'ServiceSettings')) {
				$data = $db->fetchArray();
				foreach ($data as $key=>$value) {
					$this->$key = $value;
				}
			}
			return true;
		}

		function get($name, $field) {
			global $database, $db;
			list($result) = $db->pick('SELECT '.$db->escape($field).' FROM '.$database['prefix'].'ServiceSettings WHERE name = "'.$name.'" LIMIT 1');
			return $result;
		}

		function gets($name, $fields) {
			global $database, $db;
			$result = array();
			if (!$db->query('SELECT '.$db->escape($fields).' FROM '.$database['prefix'].'ServiceSettings WHERE name = "'.$name.'" LIMIT 1'))
				return false;
			$data = $db->fetchRow();
			foreach ($data as $row) {
				array_push($result, $row);
			}
			$db->free();
			return $result;
		}		
		
		function getAll($name) {
			global $database, $db;
			$db->query('SELECT * FROM '.$database['prefix'].'ServiceSettings WHERE name='.$name);
			return $db->fetchArray();
		}

		function set($name, $field, $value) {
			global $database, $db;
			return $db->execute('UPDATE '.$database['prefix'].'ServiceSettings SET '.$db->escape($field).'="'.$db->escape($value).'" WHERE name = "'.$name.'"');
		}
	}
?>