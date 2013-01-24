<?php
	// save configuration
	define('ROOT', '../../../..');
	include ROOT . '/init/init.php';

	requireAdmin();
	requireStrictRoute();

	$response = array();
	$response['error'] = 1;

	$index=0;
	$fields = array();
	$pluginName = $_POST['pluginName'];
	$types = explode('|', $_POST['fieldTypes']);
	foreach ($_POST as $key=>$value) {
		if (Validator::enum($key, 'fieldTypes,pluginName')) continue;
		$type = $types[$index];
		array_push($fields, array('name'=>$key, 'value'=>$value, 'type'=>$type, 'isCDATA'=>(strtolower($type)=='textarea')?true:false));
		$index++;
	}

	if (Plugin::setConfig($pluginName, $fields))
		$response['error'] = 0;

	func::printRespond($response);
?>