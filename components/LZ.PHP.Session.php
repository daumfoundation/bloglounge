<?php

		$_SERVER['REMOTE_IP'] = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
		$sessionMicrotime = getMicrotimeAsFloat();
		$sessionDBRepair = false;

		function getMicrotimeAsFloat() {
			list($usec, $sec) = explode(" ", microtime());
			return ($usec + $sec);
		}

		function openSession($savePath, $sessionName) {
			return true;
		}

		function closeSession() {
			gcSession(ini_get("session.gc_maxlifetime"));
			return true;
		}

		function readSession($sid) {
			global $database, $db, $service;

			$sessionData = '';
			if ($result = sessionQuery("SELECT data FROM {$database['prefix']}SessionsData WHERE id = '$sid' AND address = '{$_SERVER['REMOTE_IP']}' AND updated >= (UNIX_TIMESTAMP() - {$service['timeout']})")) {
				list($sessionData) = $db->fetchRow($result);
			}
			return $sessionData;
		}

		function writeSession($sid, $data) {
			global $database, $db, $sessionMicrotime, $session;
			if (strlen($sid) < 16)
				return false;
			$userid = !empty($session['id']) ? $session['id'] : 'null';
			$data = $db->escape($data);
			$server = $db->escape($_SERVER['HTTP_HOST']);
			$request = $db->escape($_SERVER['REQUEST_URI']);
			$referer = isset($_SERVER['HTTP_REFERER']) ? $db->escape($_SERVER['HTTP_REFERER']) : '';
			$timer = getMicrotimeAsFloat() - $sessionMicrotime;
			$db->execute("UPDATE {$database['prefix']}SessionsData SET data = '$data', updated = UNIX_TIMESTAMP() WHERE id = '$sid' AND address = '{$_SERVER['REMOTE_IP']}'");
			if (!$db->affectedRows())
				return false;
			$db->execute("UPDATE {$database['prefix']}Sessions SET userid = $userid, server = '$server', request = '$request', referer = '$referer', timer = $timer, updated = UNIX_TIMESTAMP() WHERE id = '$sid' AND address = '{$_SERVER['REMOTE_IP']}'");
			if ($db->affectedRows() == 1)
				return true;
			return false;
		}

		function destroySession($sid, $setCookie = false) {
			global $database, $db, $session;
			if (empty($session['id']))
				return;
			@$db->execute("DELETE FROM {$database['prefix']}Sessions WHERE id = '$sid' AND address = '{$_SERVER['REMOTE_IP']}'");
			@$db->execute("DELETE FROM {$database['prefix']}SessionsData WHERE id = '$sid' AND address = '{$_SERVER['REMOTE_IP']}'");
			gcSession();
		}

		function gcSession($maxLifeTime = false) {
			global $database, $db, $service;
			@$db->execute("DELETE FROM {$database['prefix']}Sessions WHERE updated < (UNIX_TIMESTAMP() - {$service['timeout']})");
			@$db->execute("DELETE FROM {$database['prefix']}SessionsData WHERE updated < (UNIX_TIMESTAMP() - {$service['timeout']})");
			$result = @sessionQuery("SELECT DISTINCT v.id, v.address FROM {$database['prefix']}SessionVisits v LEFT JOIN {$database['prefix']}Sessions s ON v.id = s.id AND v.address = s.address WHERE s.id IS NULL AND s.address IS NULL");
			if ($result) {
				$gc = array();
				while ($g = $db->fetchRow())
					array_push($gc, $g);
				foreach ($gc as $g)
					@$db->execute("DELETE FROM {$database['prefix']}SessionVisits WHERE id = '{$g[0]}' AND address = '{$g[1]}'");
			}
			return true;
		}

		function getAnonymousSession() {
			global $database, $db;
			if (list($id) = $db->pick("SELECT id FROM {$database['prefix']}Sessions WHERE address = '{$_SERVER['REMOTE_IP']}' AND userid IS NULL AND preexistence IS NULL"))
				return $id;
			return false;
		}

		function newAnonymousSession() {
			global $database, $db;
			for ($i = 0; $i < 100; $i++) {
				if (($id = getAnonymousSession()) !== false)
					return $id;
				$id = makeSessionId();
				$db->execute("INSERT INTO {$database['prefix']}Sessions(id, address, created, updated) VALUES('$id', '{$_SERVER['REMOTE_IP']}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
				if ($db->affectedRows() > 0) {
					$db->execute("INSERT INTO {$database['prefix']}SessionsData(id, address, updated) VALUES ('$id', '{$_SERVER['REMOTE_IP']}', UNIX_TIMESTAMP())");
					return $id;
				}
			}
			return false;
		}

		function setSessionAnonymous($currentId) {
			$sid = getAnonymousSession();
			if ($sid !== false) {
				if ($sid != $currentId)
					session_id($sid);
				return true;
			}
			$sid = newAnonymousSession();
			if ($sid !== false) {
				session_id($sid);
				return true;
			}
			return false;
		}

		function newSession() {
			global $database, $db;
			for ($i = 0; ($i < 100) && !setSessionAnonymous(); $i++) {
				$sid = makeSessionId();
				$db->execute("INSERT INTO {$database['prefix']}SessionsData(id, address, updated) SELECT DISTINCT '$sid', '{$_SERVER['REMOTE_IP']}', UNIX_TIMESTAMP())");
				if (!$db->affectedRows())
					return false;
				$db->execute("INSERT INTO {$database['prefix']}Sessions(id, address, created, updated) SELECT DISTINCT '$sid', '{$_SERVER['REMOTE_IP']}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
				if ($db->affectedRows()) {
					session_id($sid);
					return true;
				}
			}
			return false;
		}

		function isSessionAuthorized($sid) {
			global $database, $db;
			$db->query("SELECT id FROM {$database['prefix']}Sessions WHERE id = '$sid' and address = '{$_SERVER['REMOTE_IP']}' and (userid IS NOT NULL OR preexistence iS NOT NULL)");
			return ($db->numRows() == 1) ? true : false;
		}

		function setSession() {
			$sid = empty($_COOKIE[session_name()]) ? '' : $_COOKIE[session_name()];
			if ((strlen($sid) < 16) || !isSessionAuthorized($sid))
				setSessionAnonymous($sid);
		}

		function authorizeSession($userid) {
			global $database, $db, $service, $session;
			if (!is_numeric($userid))
				return false;
			$session['id'] = $_SESSION['_app_id_'] = $userid;
			if (isSessionAuthorized(session_id()))
				return true;
			for ($i = 0; $i < 100; $i++) {
				$sid = makeSessionId();
				$db->execute("INSERT INTO {$database['prefix']}SessionsData(id, address, updated) VALUES('$sid', '{$_SERVER['REMOTE_IP']}', UNIX_TIMESTAMP())");
				if (!$db->affectedRows())
					return false;
				$db->execute("INSERT INTO {$database['prefix']}Sessions(id, address, userid, created, updated) VALUES('$sid', '{$_SERVER['REMOTE_IP']}', $userid, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
				if ($db->affectedRows()) {
					session_id($sid);
					header('Set-Cookie: S20_BLOGLOUNGE_SESSION='.$sid.'; path=/; domain='.((substr(strtolower($_SERVER['HTTP_HOST']), 0, 4) == 'www.') ? substr($_SERVER['HTTP_HOST'], 3) : $_SERVER['HTTP_HOST']));
					return true;
				}
			}
			return false;
		}

		function sessionQuery($sql) {
			global $database, $db, $sessionDBRepair;

			if ($db->query($sql) === false) {
				if (!isset($sessionDBRepair)) {		
					$db->execute("REPAIR TABLE {$database['prefix']}Sessions,{$database['prefix']}SessionsData");
					$sessionDBRepair = true;
					return $db->query($sql);
				}
			}
			return $db->lastResult();
		}

		function makeSessionId() {
			return substr(md5(str_pad(base_convert($_SERVER['REMOTE_IP'], 10, 16), 8, '0', STR_PAD_LEFT) . str_pad(base_convert(mt_rand(), 10, 8), 8, '0', STR_PAD_LEFT)), -16);
		}

?>