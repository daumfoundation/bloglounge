<?php
	define('ROOT', '..');
	define('NO_SESSION', true);
	include ROOT . '/lib/include.php';
	
	requireComponent('Bloglounge.Data.RSSOut');

	if (!file_exists(ROOT . '/cache/rss/0.xml')) {
		RSSOut::stop();
	}

	$action = $accessInfo['action'];
	$config = new Settings;

	header('Content-Type: text/xml; charset=utf-8');

	if(Validator::getBool($config->useRssOut)===true) {
		switch($action) {
			case 'focus':
				RSSOut::refresh('focus', false);
			break;
			case 'category':
				
				requireComponent('Bloglounge.Data.Category');
				$category = Category::getByName(urldecode($accessInfo['value']));
				RSSOut::refresh('category', false, $category);
			break;
			default: // recent
				RSSOut::refresh('recent',false);
			break;
		}
	} else {
		// error
		$fp = fopen(ROOT . "/cache/rss/0.xml", 'r+');
		$result = fread($fp, filesize(ROOT . "/cache/rss/0.xml"));
		fclose($fp);
	}
?>