<?php

	class DB {
		function start($mode = 'mysql') { // initialize
			switch (strtolower($mode)) {
				case 'sqlite':
					requireComponent('LZ.DB.SQLite');
					if (!class_exists('SQLite')) return false;
					return new SQLite;
					break;
				case 'pgsql':
					requireComponent('LZ.DB.PostgreSQL');
					if (!class_exists('PostgreSQL')) return false;
					return new PostgreSQL;
					break;
				case 'cubrid':
					requireComponent('LZ.DB.Cubrid');
					if (!class_exists('Cubrid')) return false;
					return new Cubrid;
					break;
				default:
				case 'mysql':
					requireComponent('LZ.DB.MySQL');
					if (!class_exists('MySQL')) return false;
					return new MySQL;
					break;
			}
		}

		function lessen($str, $length = 255, $tail = '..') {
			return UTF8::lessen($str, $length, $tail);
		}

		function escape($str) {
			return addslashes($str);
		}
	}
?>