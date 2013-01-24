<?php

	class Settings {
		var $_error;

		function Settings() {
			global $database, $db;
			if ($db->query('SELECT * FROM '.$database['prefix'].'Settings LIMIT 1')) {
				$data = $db->fetchArray();
				foreach ($data as $key=>$value) {
					$this->$key = $value;
				}
			}
			return true;
		}

		function get($field) {
			global $database, $db;
			list($result) = $db->pick('SELECT '.$db->escape($field).' FROM '.$database['prefix'].'Settings LIMIT 1');
			return $result;
		}

		function gets($fields) {
			global $database, $db;
			$result = array();
			if (!$db->query('SELECT '.$db->escape($fields).' FROM '.$database['prefix'].'Settings LIMIT 1'))
				return false;
			$data = $db->fetchRow();
			foreach ($data as $row) {
				array_push($result, $row);
			}
			$db->free();
			return $result;
		}

		function getAsArray($fields) {
			global $database, $db;
			$result = array();
			if (!$db->query('SELECT '.$db->escape($fields).' FROM '.$database['prefix'].'Settings LIMIT 1'))
				return false;
			$data = $db->fetchArray();
			foreach ($data as $key=>$value) {
				$result[$key] = $value;
			}
			$db->free();
			return $result;
		}

		function set($field, $value) {
			global $database, $db;
			return $db->execute('UPDATE '.$database['prefix'].'Settings SET '.$db->escape($field).'="'.$db->escape($value).'"');
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