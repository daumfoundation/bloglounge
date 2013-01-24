<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';
	
	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	
	requireComponent('Bloglounge.Data.Settings');

	$feeder = new Feed;
	$config = new Settings;

	$type = (isset($_GET['type'])&&!empty($_GET['type']))?$_GET['type']:$config->updateProcess;

	switch($type) {
		case 'random':	// 랜덤
			if (!$feeder->updateRandomFeed())
				$response['error'] = 1;
		break;
		case 'repeat': // 순차
		default:
			if (!$feeder->updateNextFeed())
				$response['error'] = 1;
		break;
	}
	

	// 매번 업데이트하는 피드를 업데이트 합니다.
	if (!$feeder->updateEveryTimeFeed())
		$response['error'] = 1;

	func::printRespond($response);
?>