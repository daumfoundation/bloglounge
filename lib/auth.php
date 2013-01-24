<?php
function login($userid, $userpw, $saveId = false) {
	global $database, $db, $service, $event;

	if (!((strlen($userpw) == 40) && preg_match('/[0-9a-f]/i', $userpw))) $forceRaw = true;		
	if (!isset($_SESSION['sslPublicKey']) && !$forceRaw) return false;
	if (Validator::getBool($forceRaw) === true) $userid = sha1($userid);
	
	$db->query('SELECT id, loginid, password, email FROM '.$database['prefix'].'Users WHERE SHA1(loginid)="'.$db->escape($userid).'"');
	if ($db->numRows() != 0) {
		list($uid, $loginid, $password, $email) = $db->fetchRow();
		$db->free();
		$input = array('loginid'=>$loginid, 'email'=>$email, 'saveId'=>$saveId);
		
		if ($password != getEncryptedPassword($loginid, $userpw))
			return false;

		authorizeSession($uid);
		@$db->query('UPDATE '.$database['prefix'].'Users SET lastLogin = UNIX_TIMESTAMP() WHERE loginid="'.$loginid.'"');
		if (!isset($saveId) || empty($saveId)) {
			setcookie('BLOGLOUNGE_LOGINID', '', time() - 31536000, $service['path'] . '/', '.'.$_SERVER['HTTP_HOST']);
		} else {
			setcookie('BLOGLOUNGE_LOGINID', $loginid, time() + 31536000, $service['path'] . '/', '.'.$_SERVER['HTTP_HOST']);
		}
		$event->on('Auth.login', $input);

		return true;
	} 
	return false;
}

function logout() {
	global $event, $session;
	$event->on('Auth.logout', array('id'=>$session['id']));
	session_destroy();
}

function isLoggedIn() {
	global $session;
	return !empty($session['id']);
}

function getLoggedId() {
	global $session;
	return $session['id'];
}

function requireLogin($msg='') {
	global $service;
	if (!isLoggedIn()) {
		header('Location: ' . $service['path'] . '/login/?requestURI=' . rawurlencode($_SERVER['REQUEST_URI']));
		exit;
	}

	if (isLoggedIn() && !empty($msg)) {
		header('Location: ' . $service['path'] . '/login/?requestURI=' . rawurlencode($_SERVER['REQUEST_URI']).'&msg='.base64_encode($msg));
		exit;
	}
}

function getUsers($id = null) {	
	global $database, $db, $session;
	if (!isset($id) && !empty($session['id']))
		$id = $session['id'];
	if (!isset($id)) return false;
	$cond = (preg_match("/^[0-9]+$/", $id))  ? 'id="'.$id.'"' : 'loginid="'.$id.'"';
	return $db->queryRow('SELECT * FROM '.$database['prefix'].'Users WHERE '.$cond);
}

function isAdmin($id = null) {
	global $database, $db, $session;
	if (!isset($id) && !empty($session['id']))
		$id = $session['id'];
	if (!isset($id)) return false;
	$cond = (preg_match("/^[0-9]+$/", $id))  ? 'id="'.$id.'"' : 'loginid="'.$id.'"';
	list($isAdmin) = $db->pick('SELECT is_admin FROM '.$database['prefix'].'Users WHERE '.$cond);
	return ($isAdmin == 'y') ? true : false;
}

function requireAdmin() {
	global $service, $session;
	if (isAdmin()) return true;
	if (empty($session['id']) || !isLoggedIn()) requireLogin();
	func::printError(_t('이 페이지에 접근할 권한이 없습니다.'));
	requireLogin();
}

function getNameById($id) {
	global $database, $db;
	if (preg_match("/^[0-9]+$/", $id)) {
		$db->query('SELECT name FROM '.$database['prefix'].'Users WHERE id="'.$id.'"');
		$result = $db->fetchArray();
		$db->free();
		return $result['name'];
	}
	return false;
}

function requireMembership() {
	global $session;
	if (!isLoggedIn()) requireLogin();
	if (!empty($session['id']) && isSessionAuthorized(session_id())) {
		return true;
	}
	return false;
}

function requireStrictRoute() {
		if (isset($_SERVER['HTTP_REFERER']) && ($url = parse_url($_SERVER['HTTP_REFERER'])) && ($url['host'] == $_SERVER['HTTP_HOST']))
				return;
		header('HTTP/1.1 412 Precondition Failed');
		header('Content-Type: text/html');
		header("Connection: close");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
		<title>Precondition Failed</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
		<h1>Precondition Failed</h1>
</body>
</html>
<?php
		exit;
}

function getEncryptedPassword($userid, $plainPassword) {
	return Encrypt::hmac($userid, md5(md5($plainPassword)));
}
?>
