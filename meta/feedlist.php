<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	include ROOT . '/lib/begin.php';

	// 피드 목록

	$pageCount = $skinConfig->feedListPage;

	list($feeds, $totalFeeds) = Feed::getFeeds($page, $pageCount, $skinConfig->feedListPageOrder);
	$paging = Func::makePaging($page, $pageCount, $totalFeeds);

	include ROOT.'/lib/piece/feed_message.php';
	include ROOT.'/lib/piece/feedlist.php';
	include ROOT.'/lib/end.php';
?>
