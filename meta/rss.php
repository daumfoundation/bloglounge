<?php
	define('ROOT', '..');
	define('NO_SESSION', true);
	include ROOT . '/lib/include.php';
	
	if (!file_exists(ROOT . '/cache/rss/1.xml')) {
		requireComponent('Bloglounge.Data.RSSOut');
		RSSOut::refresh();
	}

	header('Content-Type: text/xml; charset=utf-8');
	$fp = fopen(ROOT . "/cache/rss/1.xml", 'r+');
	$result = fread($fp, filesize(ROOT . "/cache/rss/1.xml"));
	fclose($fp);

	echo $result;
?>