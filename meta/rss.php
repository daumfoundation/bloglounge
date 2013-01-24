<?php
	define('ROOT', '..');
	define('NO_SESSION', true);
	include ROOT . '/lib/include.php';
	
	if (!file_exists(ROOT . '/cache/rss/1.xml')) {
		requireComponent('Bloglounge.Data.RSSOut');
		RSSOut::refresh();
	}
	if (!file_exists(ROOT . '/cache/rss/1_focus.xml')) {
		requireComponent('Bloglounge.Data.RSSOut');
		RSSOut::refresh('focus');
	}
	if (!file_exists(ROOT . '/cache/rss/0.xml')) {
		requireComponent('Bloglounge.Data.RSSOut');
		RSSOut::stop();
	}
	
	$action = $accessInfo['action'];
	$config = new Settings;

	header('Content-Type: text/xml; charset=utf-8');

	if(Validator::getBool($config->useRssOut)===true) {
		switch($action) {
			case 'focus':
				$fp = fopen(ROOT . "/cache/rss/1_focus.xml", 'r+');
				$result = fread($fp, filesize(ROOT . "/cache/rss/1_focus.xml"));
				fclose($fp);
			break;
			default:
				$fp = fopen(ROOT . "/cache/rss/1.xml", 'r+');
				$result = fread($fp, filesize(ROOT . "/cache/rss/1.xml"));
				fclose($fp);
			break;
		}
	} else {
		$fp = fopen(ROOT . "/cache/rss/0.xml", 'r+');
		$result = fread($fp, filesize(ROOT . "/cache/rss/0.xml"));
		fclose($fp);
	}

	echo $result;
?>