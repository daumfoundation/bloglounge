<?php

	class MySQL extends DB {
		var $alive = false, $resources = array(), $resourceIndex = 0;

		function MySQL() {
			global $database;
			if (mysql_connect($database['server'], $database['username'], $database['password']) && mysql_select_db($database['database'])) {
				if (isset($this)) { $this->alive = true; } else { $database['alive'] = true; }
				$database['utf8'] = (mysql_query('SET CHARACTER SET utf8')) ? true : false;
				@mysql_query('SET SESSION collation_connection = \'utf8_general_ci\'');
			}
		}

		function connect($config) { // connect with specified array argument, same skel with $database
			if (!isset($config) || !is_array($config)) return false;
			mysql_connect($config['server'], $config['username'], $config['password']);
			mysql_select_db($config['database']);
			$this->utf8 = (mysql_query('SET CHARACTER SET utf8')) ? true : false;
			@mysql_query('SET SESSION collation_connection = \'utf8_general_ci\'');
			$this->alive = true;
		}

		function close() {
			return mysql_close();
		}

		function lessen($str, $length = 255, $tail = '..') {
			global $database;
			return ($database['utf8']) ? UTF8::lessen($str, $length, $tail) : UTF8::lessenAsByte($str, $length, $tail);
		}

		function escape($str) {
			global $database;
			$noConnection = (isset($this)) ? $this->alive : $database['alive'];
			return ($noConnection) ? mysql_escape_string($str) : mysql_real_escape_string($str);
		}
		
		function lastResult() {
			return $this->resources[$this->resourceIndex]['resource'];
		}

		function query($query) {
			if ($result = mysql_query($query)) {
				array_push($this->resources, array('query'=>$query, 'resource'=>$result, 'length'=>((strpos(strtoupper($query), 'SELECT') !== false) ? mysql_num_rows($result) : mysql_affected_rows())));
				$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
				return $this->resources[$this->resourceIndex]['resource'];
			}
			return false;
		}

		function execute($query) {
			return mysql_query($query) ? true : false;
		}

		function fetch($result = null) { // fetchObject is default.
			return $this->fetchObject($result);
		}

		function fetchObject($result = null) { // fetch = fetchObject
			if (isset($result)) 
				return mysql_fetch_object($result);

			if (!isset($this->resources[$this->resourceIndex]['resource']) || empty($this->resources[$this->resourceIndex]['resource']))
				return false;
			if (($this->resources[$this->resourceIndex]['length'] > 0)) {
				if (($res = mysql_fetch_object($this->resources[$this->resourceIndex]['resource'])) === false) {
					if (!is_null(array_pop($this->resources))) {
						$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
						return ((isset($this->resources[$this->resourceIndex]['resource']) && is_resource($this->resources[$this->resourceIndex]['resource'])) ? mysql_fetch_object($this->resources[$this->resourceIndex]['resource']) : false);
					}
				}
			} else { // length = 0
				array_pop($this->resources);
				$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
				return false;
			}
			return $res;
		}

		function fetchArray($result = null) {
			if (isset($result)) 
				return mysql_fetch_array($result);

			if (!isset($this->resources[$this->resourceIndex]['resource']) || empty($this->resources[$this->resourceIndex]['resource']))
				return false;
			if (($this->resources[$this->resourceIndex]['length'] > 0)) {
				if (($res = mysql_fetch_array($this->resources[$this->resourceIndex]['resource'])) === false) {
					if (!is_null(array_pop($this->resources))) {
						$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
						return ((isset($this->resources[$this->resourceIndex]['resource']) && is_resource($this->resources[$this->resourceIndex]['resource'])) ? mysql_fetch_array($this->resources[$this->resourceIndex]['resource']) : false);
					}
				}
			} else { // length = 0
				array_pop($this->resources);
				$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
				return false;
			}
			return $res;
		}

		function fetchRow($result = null) {
			if (isset($result)) 
				return mysql_fetch_row($result);

			if (!isset($this->resources[$this->resourceIndex]['resource']) || empty($this->resources[$this->resourceIndex]['resource']))
				return false;
			if (($this->resources[$this->resourceIndex]['length'] > 0)) {
				if (($res = mysql_fetch_row($this->resources[$this->resourceIndex]['resource'])) === false) {
					if (!is_null(array_pop($this->resources))) {
						$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
						return ((isset($this->resources[$this->resourceIndex]['resource']) && is_resource($this->resources[$this->resourceIndex]['resource'])) ? mysql_fetch_row($this->resources[$this->resourceIndex]['resource']) : false);
					}
				}
			} else { // length = 0
				array_pop($this->resources);
				$this->resourceIndex = (($na = count($this->resources) -1) < 0) ? 0 : $na;
				return false;
			}
			return $res;
		}

		function free($result = null) {
			// this is useless if using PHP version over 4.
			// using mysql_free_result may costs more memory. decomment below after check your environment
			// return (isset($result)) ? mysql_free_result($result) : mysql_free_result(array_pop($this->resources));
		}

		function exists($query) {
			if ($result = mysql_query($query)) {
				if (mysql_num_rows($result) > 0) {
					mysql_free_result($result);
					return true;
				}
				mysql_free_result($result);
			}
			return false;
		}

		function insertId() {
			return mysql_insert_id();
		}

		function pick($query) {
			if (empty($query) || !isset($query)) 
				return false;

			if (!$result = mysql_query($query))
				return false;

			$rows  = mysql_fetch_row($result);
			mysql_free_result($result);
			return $rows;
		}

		function queryCell($query, $field = 0) {
			if ($result = mysql_query($query)) {
				if (is_numeric($field)) {
					$row = mysql_fetch_row($result);
					$cell = @$row[$field];
				} else {
					$row = mysql_fetch_assoc($result);
					$cell = @$row[$field];
				}
				mysql_free_result($result);
				return $cell;
			}
			return null;
		}

		function queryCount($query) {
			$count = 0;
			if ($result = mysql_query($query)) {
				$count = mysql_num_rows($result);
				mysql_free_result($result);
			}
			return $count;
		}

		function queryRow($query) {
			if ($result = mysql_query($query)) {
				$row = mysql_fetch_assoc($result);			
				mysql_free_result($result);
				return $row;
			}
			return null;
		}

		function queryAll($query) {
			$all = array();
			if($result = $this->query($query)) {
				while ($row = mysql_fetch_array($result))
					array_push($all, $row);
				mysql_free_result($result);
				return $all;
			}
			return null;
		}

		function affectedRows() {
			return (($n = mysql_affected_rows()) > 0) ? $n : 0;
		}

		function numRows($result = null) {
			return (isset($result)) ? mysql_num_rows($result) : mysql_num_rows($this->resources[$this->resourceIndex]['resource']);
		}

		function error() {
			return mysql_error();
		}

		function errno() {
			return mysql_errno();
		}

		function doesExistTable($tablename) {
			$likeEscape = array ( '/_/' , '/%/' ); 
			$likeReplace = array ( '\\_' , '\\%' );
			$escapename = preg_replace($likeEscape, $likeReplace, $tablename);
			$result = mysql_query("SHOW TABLES LIKE '$escapename' ");
			if ($result == false) return false;
			if (mysql_num_rows($result) > 0) return true;
			return false;
		}

		// doesExistTableArray : 여러 테이블의 존재여부 검색
		// needle 로 시작하는 테이블로 한정하여, tables(array)중 존재하는 테이블 목록 반환. 
		// needle 을 빈 문자열로 하면 모든 테이블을 대상으로 한다.
		// doesExistTable 을 여러번 호출하지 않기 위해 만들어진 함수

		function doesExistTableArray($needle, $tables) { 
			if (!is_array($tables)) return false;

			$response['exist'] = 0;
			$response['names'] = array();

			$query = mysql_query("SHOW TABLES LIKE '{$needle}%'");
			while ($row = mysql_fetch_row($query)) {
				if (in_array($row[0], $tables)) {
					$response['exist']++;
					array_push($response['names'], $row[0]);
				}
			}
			mysql_free_result($query);

			return $response;
		}
	}

?>