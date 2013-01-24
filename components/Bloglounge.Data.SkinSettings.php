<?php
	class SkinSettings {
		var $_error;

		function SkinSettings() {
			global $database, $db;
			if ($db->query('SELECT * FROM '.$database['prefix'].'SkinSettings LIMIT 1')) {
				$data = $db->fetchArray();
				foreach ($data as $key=>$value) {
					$this->$key = $value;
				}
			}
			return true;
		}

		function get($field) {
			global $database, $db;
			list($result) = $db->pick('SELECT '.$db->escape($field).' FROM '.$database['prefix'].'SkinSettings LIMIT 1');
			return $result;
		}

		function getAsArray($fields) {
			global $database, $db;
			$db->query('SELECT '.$db->escape($fields).' FROM '.$database['prefix'].'SkinSettings LIMIT 1');
			$data = $db->fetchArray();
			$db->free();
			return $data;
		}

		function set($field, $value) {
			global $database, $db;
			return $db->execute('UPDATE '.$database['prefix'].'SkinSettings SET '.$db->escape($field).'="'.$db->escape($value).'"');
		}

		function setWithArray($arg) {
			global $db;
			foreach ($arg as $field=>$value) {
				if(!$this->set($field, $value)) {
					$this->_error = 'Error: on setAsArray('.$field.','.$value.') : '.$db->error();
					return false;
				}
			}
			return true;
		}
	}
?>