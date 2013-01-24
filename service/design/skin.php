<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	$name = $_POST['name'];
	$type = $_POST['type'];

	if(empty($type)) {
			$response['error'] = -1;
			$response['message'] = _t('잘못된 접근입니다.');
	} else {
		if (!isAdmin()) {
			$response['error'] = 1;
			$response['message'] = _t('관리자만이 이 기능을 사용할 수 있습니다.');
		} else {
				if(empty($name)) {
						Settings::set($type.'skin', '');
				} else {
					if (!file_exists(ROOT . '/skin/'.$type.'/'.$name.'/skin.html')) {
						$response['error'] = 1;
						$response['message'] = _t('올바른 스킨이 아닙니다');
					} else {
						Settings::set($type.'skin', $name);
					}
				}
		}
	}

	func::printRespond($response);
?>
