<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	if (empty($_GET['requestURI'])) {
		if(!empty($_SERVER['HTTP_REFERER'])) {
			header("Location: http://{$_SERVER['HTTP_HOST']}{$service['path']}/join?requestURI=" . rawurlencode($_SERVER['HTTP_REFERER']));
			exit;
		}
	}
	if (empty($_POST['requestURI']) && !empty($_GET['requestURI'])) {
		$_POST['requestURI'] = $_GET['requestURI'];
	}

	$errors = array();
	if (isset($_POST['posted'])) {
		if (empty($_POST['username'])) {
			$errors['username'] = _t('이름을 입력해주세요.');
		}
		if (empty($_POST['userid'])) {
			$errors['userid1'] = _t('아이디를 입력해주세요.');
		} else {			
			if (strlen($_POST['userid']) < 3) {
				$errors['userid3'] = _t('아이디는 3자 이상이 되어야 합니다.');
			}
		}

		if (empty($_POST['userpw'])) {
			$errors['userpw1'] = _t('비밀번호를 입력해주세요.');
		}
		else if (empty($_POST['userpw2'])) {
			$errors['userpw2'] =  _t('비밀번호를 입력해주세요.');
		} else {
			if (strlen($_POST['userpw']) < 4) {
				$errors['userpw3'] = _t('비밀번호는 4자 이상으로 해 주세요.');
			}
		}

		if (empty($_POST['useremail'])) {
			$errors['useremail1'] =  _t('이메일 주소를 입력해주세요.');
		}
		
	}

	if (!empty($_POST['userid']) && !empty($_POST['userpw']) && !empty($_POST['useremail'])) {
		if (!Validator::is_alnum($_POST['userid'])) {
			$errors['userid2'] = _t('아이디에 잘못된 문자가 포함되어 있습니다.');
		}
		if (!Validator::is_email($_POST['useremail'])) {
			$errors['useremail2'] =  _t('이메일 주소가 잘못되었습니다.');
		}
		if ($_POST['userpw'] != $_POST['userpw2']) {
			$errors['userpw4'] = _t('두 비밀번호가 일치하지 않습니다.');
		}

		if (count($errors) == 0) {
			requireComponent('Bloglounge.Model.Users');
			if (User::doesLoginIdExists($_POST['userid'])) {
				$errors['userid4'] = _t('이미 존재하는 아이디입니다.');
			} else {
				if (User::add($_POST['userid'], $_POST['userpw'], $_POST['username'], $_POST['useremail'])) {
					login($_POST['userid'], $_POST['userpw'], false);
				} else {
					$errors['usererror'] = _t('회원가입에 실패했습니다.');
				}
			}
		}
		if (count($errors) == 0) {
			$targetURL = empty($_POST['requestURI']) ? "http://{$_SERVER['HTTP_HOST']}{$service['path']}/" : $_POST['requestURI'];
			header("Location: {$targetURL}");
			exit;
		}
	}

	include ROOT . '/lib/piece/join.php';	
?>