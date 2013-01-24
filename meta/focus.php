<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	include ROOT . '/lib/begin.php';
	
	$pageCount = $skinConfig->postList; // 페이지갯수
	list($posts, $totalFeedItems) = FeedItem::getFeedItems('focus', 'y', '', $page, $pageCount);
	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);

	include ROOT . '/lib/piece/message.php';
	include ROOT . '/lib/piece/postlist.php';
	include ROOT . '/lib/end.php';
?>