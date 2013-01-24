<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	$feed = Feed::getRandomFeed();
	header("Location: {$feed['blogURL']}");
	exit;
?>