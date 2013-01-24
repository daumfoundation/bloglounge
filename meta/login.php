<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	if (empty($_GET['requestURI'])) {
		if(!empty($_SERVER['HTTP_REFERER'])) {
			header("Location: http://{$_SERVER['HTTP_HOST']}{$service['path']}/login?requestURI=" . rawurlencode($_SERVER['HTTP_REFERER']));
			exit;
		}
	}
	if (empty($_POST['requestURI']) && !empty($_GET['requestURI'])) {
		$_POST['requestURI'] = $_GET['requestURI'];
	}

	$errorMsg = '';

	if (isset($_POST['enci'])) {
		if (Validator::is_empty($_POST['enci']))
			$errorMsg = _t('아이디를 입력해주세요');
		if (Validator::is_empty($_POST['encp']))
			$errorMsg = _t('비밀번호를 입력해주세요');
	} else if (isset($_POST['useridin'])) { // for none-javascript environment
		if (Validator::is_empty($_POST['useridin']))
			$errorMsg = _t('아이디를 입력해주세요');
		if (Validator::is_empty($_POST['userpwin']))
			$errorMsg = _t('비밀번호를 입력해주세요');

		$_POST['enci'] = $_POST['useridin'];
		$_POST['encp'] = $_POST['userpwin'];
	}

	if (!empty($_POST['enci']) && !empty($_POST['encp'])) {
		if (!isset($_POST['saveId'])) $_POST['saveId'] = false;
		if (!login($_POST['enci'], $_POST['encp'], $_POST['saveId'])) {
			$errorMsg = _t('아이디 또는 비밀번호가 잘못되었습니다.');
		}
	}
	if (isLoggedIn()) {
		$targetURL = empty($_POST['requestURI']) ? "http://{$_SERVER['HTTP_HOST']}{$service['path']}/" : $_POST['requestURI'];
		header("Location: {$targetURL}");
		exit;
	}
	include ROOT . '/lib/piece/login.php';	
?>