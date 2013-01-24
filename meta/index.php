<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	if(!empty($searchType) && !empty($searchKeyword)) {
		if($accessInfo['controller'] != 'search') {
			header("Location: {$service['path']}/search/{$searchType}/" .func::encode($searchKeyword));
			exit;
		}
	}

	include ROOT . '/lib/begin.php';	
	$pageCount = $skinConfig->postList;
	list($posts, $totalFeedItems) = FeedItem::getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount);
	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);
	
	include ROOT . '/lib/piece/message.php';
	include ROOT . '/lib/piece/postlist.php';
	include ROOT . '/lib/end.php';
?>