<?php

	Class User {

		function add($loginId, $password, $name, $email) {
			global $database, $db, $event;
			if (empty($loginId) || empty($password) || empty($name) || empty($email)) {
				return false;
			}

			$loginId = $db->escape($loginId);
			$mpassword = $db->escape(Encrypt::hmac($loginId, md5(md5($password))));
			$name = $db->escape($name);
			$email = $db->escape($email);
			$is_accepted = (Settings::get('restrictJoin') == 'y') ? 'n' : 'y';

			$input = array('loginid'=>$loginId, 'password'=>$password, 'name'=>$name, 'email'=>$email, 'is_accepted'=>Validator::getBool($is_accepted));
			if ($event->on('User.add', $input) === false)
				return false;
			if (!$db->execute('INSERT INTO '.$database['prefix'].'Users (loginid, name, password, email, created, is_accepted) VALUES ("'.$loginId.'","'.$name.'","'.$mpassword.'","'.$email.'",UNIX_TIMESTAMP(),"'.$is_accepted.'")')) {
				$event->on('User.add.rollback');
				return false;
			}
			return true;
		}

		function doesNameExists($name) {
			global $database, $db;
			if (!isset($name) || empty($name)) {
				return false;
			}
			$n = $db->queryCount('SELECT id FROM '.$database['prefix'].'Users WHERE name="'.$db->escape($name).'"');
			return Validator::getBool($n);
		}

		function doesLoginIdExists($loginid) {
			global $database, $db;
			if (!isset($loginid) || empty($loginid)) {
				return false;
			}
			$n = $db->queryCount('SELECT id FROM '.$database['prefix'].'Users WHERE loginid="'.$db->escape($loginid).'"');
			return Validator::getBool($n);
		}

		function doesEmailExists($email) {
			global $database, $db;
			if (!isset($email) || empty($email)) {
				return false;
			}
			$n = $db->queryCount('SELECT id FROM '.$database['prefix'].'Users WHERE email="'.$db->escape($email).'"');
			return Validator::getBool($n);
		}

		function getBlog($id) {
			global $database, $db;
			if (!preg_match("/^[0-9]+$/", $id)) {
				$id = User::loginid2id($id);
			}

			if (empty($id) || !isset($id)) {
				return false;
			}

			if ($db->query('SELECT * FROM '.$database['prefix'].'Feeds WHERE owner="'.$id.'" ORDER BY id ASC LIMIT 1')) {
				$data = $db->fetchArray();
				$db->free();
				return $data;
			}
			return false;
		}

		function getId($loginid) {
			global $database, $db;
			if (!isset($loginid) || empty($loginid)) {
				return false;
			}
			return $db->queryCell('SELECT id FROM '.$database['prefix'].'Users WHERE loginid="'.$db->escape($loginid).'"');;
		}

		function getIdByName($name) {
			global $database, $db;
			if (!isset($name) || empty($name)) {
				return false;
			}
			return $db->queryCell('SELECT id FROM '.$database['prefix'].'Users WHERE name="'.$db->escape($name).'"');;
		}

		function delete($id) {
			global $database, $db, $event;
			if (!preg_match("/^[0-9]+$/", $id)) {
				$id = User::loginid2id($id);
			}

			if (empty($id) || !isset($id)) {
				return false;
			}

			if (!$event->on('User.delete', array('id'=>$id)))
				return false;

			$db->query('SELECT id FROM '.$database['prefix'].'Feeds WHERE owner='.$id);
			while ($data = $db->fetch()) {
				Feed::delete($data->id);
			}
			$db->free();
			
			return $db->execute('DELETE FROM '.$database['prefix'].'Users WHERE id="'.$id.'"');
		}

		function edit($id, $arg, $drop = null) {
			global $database, $db, $event;
			if (!preg_match("/^[0-9]+$/", $id))
				$id = User::loginid2id($id);

			if (empty($id) || !isset($id)) 
				return false;

			$input = $arg;
			$input['id'] = $id;
			if (!$event->on('User.edit', $input))
				return false;

			$updateArr = array();
			$dropArr = (isset($drop)) ? explode(',', $drop) : array();
			foreach ($arg as $field=>$value) {			
				if (empty($value) || strlen(trim($value)) == 0) continue;
				if (in_array($field, $dropArr)) continue;
				array_push($updateArr, $field.'="'.$db->escape($value).'"');
			}
			$updateStr = implode(',', $updateArr);
			return $db->execute('UPDATE '.$database['prefix'].'Users SET '.$updateStr.' WHERE id='.$id);
		}

		function get($id, $field) {
			global $database, $db;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;
			$result = $db->queryCell('SELECT '.$field.' FROM '.$database['prefix'].'Users WHERE id="'.$db->escape($id).'"');
			return $result;
		}

		function getAll($id) {
			global $database, $db;
			if (empty($id) || !preg_match("/^[0-9]+$/", $id)) {
				return false;
			}
			$db->query('SELECT * FROM '.$database['prefix'].'Users WHERE id='.$id);
			return $db->fetchArray();
		}

		function getById($id) {
			global $database, $db;
			$db->query('SELECT * FROM '.$database['prefix'].'Users WHERE id="'.$db->escape($id).'"');
			$result = $db->fetchArray();
			$db->free();
			return $result;
		}

		function getByloginId($loginId) {
			global $database, $db;
			$db->query('SELECT * FROM '.$database['prefix'].'Users WHERE loginid="'.$db->escape($loginId).'"');
			$result = $db->fetchArray();
			$db->free();
			return $result;
		}

		function loginid2id($loginid) {
			global $database, $db;
			$id = $db->queryCell('SELECT id FROM '.$database['prefix'].'Users WHERE loginid="'.$db->escape($loginid).'"');
			return $id;
		}

		function id2loginid($id) {
			global $database, $db;
			$loginid = $db->queryCell('SELECT loginid FROM '.$database['prefix'].'Users WHERE id="'.$db->escape($id).'"');
			return $loginid;
		}

		function isUser($id) {
			global $database, $db;
			$n = $db->count('SELECT id FROM '.$database['prefix'].'Users WHERE id="'.$db->escape($id).'" OR loginid="'.$db->escape($id).'"');
			return Validator::getBool($n);
		}

		function getMemberCount($keyword='') {
			global $database, $db;
			$sQuery = !empty($keyword) ? ' WHERE loginid LIKE "%'.$db->escape($keyword).'%" OR name LIKE "%'.$db->escape($keyword).'%" OR email LIKE "%'.$db->escape($keyword).'%"' : '';

			if (!list($totalUsers) = $db->pick('SELECT count(id) FROM '.$database['prefix'].'Users'.$sQuery))
				$totalUsers = 0;

			return $totalUsers;
		}

		function getMembers($keyword, $page, $pageCount) {		
			global $database, $db;
			
			$sQuery = !empty($keyword) ? ' WHERE loginid LIKE "%'.$db->escape($keyword).'%" OR name LIKE "%'.$db->escape($keyword).'%" OR email LIKE "%'.$db->escape($keyword).'%"' : '';
		
			$pageStart = ($page-1) * $pageCount; // 처음페이지 번호
			$result = $db->queryAll('SELECT id, loginid, name, is_admin, is_accepted, created FROM '.$database['prefix'].'Users '.$sQuery.' ORDER BY created DESC LIMIT '.$pageStart.','.$pageCount);
			return $result;
		}

		function getAdminCount() {
			global $database, $db;
			return $db->queryCell("SELECT count(id) FROM {$database['prefix']}Users WHERE is_admin='y'");
		}
	}
?>
