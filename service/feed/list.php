<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	if (!isLoggedIn()) {
		$response['error'] = 1;
		$response['message'] = _t('로그인 한 사람만 이 기능을 사용할 수 있습니다.');
	} else {
		$response['list'] = implode(',',Feed::getIdList());
	}

	func::printRespond($response);
?>