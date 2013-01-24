<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	if (!isAdmin()) {
		$response['error'] = 1;
		$response['message'] = _t('관리자만이 이 기능을 사용할 수 있습니다.');
	} else {
		$response['error'] = 1;

		$pluginName = $_POST['plugin'];
		$ting = (isset($_POST['ting']) && !empty($_POST['ting'])) ? Validator::getBool($_POST['ting']) : null;

		if (!preg_match('/^[A-Za-z0-9 _-]+$/', $pluginName)) {
			$response['message'] = _t('잘못된 플러그인 이름입니다');
			func::printRespond($response);
		}

		if (!is_dir(ROOT . '/plugins/'.$pluginName)) {
			$response['message'] = _t('플러그인이 존재하지 않습니다');
			func::printRespond($response);
		}

		if (!file_exists(ROOT . '/plugins/'.$pluginName.'/index.xml')) {
			$response['message'] = _t('플러그인 정보를 찾을 수 없습니다');
			func::printRespond($response);
		}

		if (Plugin::activate($pluginName, $ting))
			$response['error'] = 0;
	}


	func::printRespond($response);
?>
