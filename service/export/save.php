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
		$index=0;
		$fields = array();
		$domainName = $_POST['domainName'];
		$types = explode('|', $_POST['fieldTypes']);
		foreach ($_POST as $key=>$value) {
			if (Validator::enum($key, 'fieldTypes,domainName')) continue;
			$type = $types[$index];
			$key = substr($key, 1, strlen($key)-1);
			array_push($fields, array('name'=>$key, 'value'=>$value, 'type'=>$type, 'isCDATA'=>(strtolower($type)=='textarea')?true:false));
			$index++;
		}
		
		requireComponent('Bloglounge.Model.Exports');

		if (Export::setConfig($domainName, $fields))
			$response['error'] = 0;
	}


	func::printRespond($response);
?>