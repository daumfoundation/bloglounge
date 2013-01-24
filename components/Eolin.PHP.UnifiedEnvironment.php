<?php
	// from Eolin.PHP.UnifiedEnvironment component / Textcube 1.5
	// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
	// All rights reserved. Licensed under the GPL.
	
	ini_set('session.use_trans_sid', '0');
	if (intval(ini_get("session.auto_start")) == 1) {
	   @session_destroy();
	   @ini_set('session.auto_start', '0');
	}

	if (get_magic_quotes_runtime())
		set_magic_quotes_runtime(0);
	 
	if (get_magic_quotes_gpc()) {
		function stripSlashesRecursively($value) {
			if (is_array($value))
				return array_map('stripSlashesRecursively', $value);
			else if (is_string($value))
				return stripslashes($value);
			else
				return $value;
		}

		$_GET = array_map('stripSlashesRecursively', $_GET);
		$_POST = array_map('stripSlashesRecursively', $_POST);
		$_COOKIE = array_map('stripSlashesRecursively', $_COOKIE);
		$_ENV = array_map('stripSlashesRecursively', $_ENV);
		$_REQUEST = array_map('stripSlashesRecursively', $_REQUEST);
		$_SERVER = array_map('stripSlashesRecursively', $_SERVER);
	}
?>