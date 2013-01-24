<?php
	define('ROOT', '..');
	define('NO_SESSION', true);
	include ROOT . '/lib/include.php';
	
	if (!file_exists(ROOT . '/cache/rss/1.xml')) {
		requireComponent('Bloglounge.Data.RSSOut');
		RSSOut::refresh();
	}
	if (!file_exists(ROOT . '/cache/rss/0.xml')) {
		requireComponent('Bloglounge.Data.RSSOut');
		RSSOut::stop();
	}

	$config = new Settings;

	header('Content-Type: text/xml; charset=utf-8');
	if(Validator::getBool($config->useRssOut)===true) {
		$fp = fopen(ROOT . "/cache/rss/1.xml", 'r+');
		$result = fread($fp, filesize(ROOT . "/cache/rss/1.xml"));
		fclose($fp);
	} else {
		$fp = fopen(ROOT . "/cache/rss/0.xml", 'r+');
		$result = fread($fp, filesize(ROOT . "/cache/rss/0.xml"));
		fclose($fp);
	}

	echo $result;
?>