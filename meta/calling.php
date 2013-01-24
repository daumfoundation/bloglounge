<?php	
	// for crontab
	if(!defined('ROOT')) {
		ini_set('display_errors','0');

		$root = $_SERVER['PHP_SELF'];
		$root = substr($root,0,strrpos($root,'/'));
		define('ROOT',$root . '/..');
		unset($root);
	}

	include ROOT . '/lib/include.php';
	
	// TODO :
	// Update
	$feeder = new Feed();
	$config = new Settings();

	switch($config->updateProcess) {
		case 'random':	// 랜덤
			$feeder->updateRandomFeed();
		break;
		case 'repeat': // 순차
		default:
			$feeder->updateNextFeed();
		break;
	}	
	
	// 매번 업데이트하는 피드를 업데이트 합니다.
	if (!$feeder->updateEveryTimeFeed())
		$response['error'] = 1;

	// Plugins
	$event->on('Api.calling');
?>