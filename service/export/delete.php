<?php
	// save configuration
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 1;
	$response['message'] = '';
	
	if (!isAdmin()) {
		$response['error'] = 1;
		$response['message'] = _t('관리자만이 이 기능을 사용할 수 있습니다.');
	} else {
		$domainName = $_POST['domainName'];
		
		requireComponent('Bloglounge.Model.Exports');
		$export = new Export;

		if ($export->delete($domainName))
			$response['error'] = 0;
	}

	func::printRespond($response);
?>