<?php
	define('ROOT', '../..');
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
	// Plugins
	$event->on('Api.calling');
?>