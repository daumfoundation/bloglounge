<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	
	$searchFeedId = $accessInfo['action'];
	$searchType = 'blogURL';
	if(is_numeric($searchFeedId)) {
		$searchKeyword = 'http://'.str_replace('http://', '', Feed::get($searchFeedId, 'blogURL'));	
		$searchExtraValue = $searchFeedId;
	} else {
		$searchKeyword = 'http://'.str_replace('http://', '', $accessInfo['address']);
		$searchExtraValue = Feed::blogURL2Id('http://'.str_replace('http://', '', $searchKeyword));
	}

	include ROOT . '/lib/begin.php';

	$pageCount = $skinConfig->postList; // 페이지갯수
	list($posts, $totalFeedItems) = FeedItem::getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount);
	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);

	include ROOT . '/lib/piece/message.php';
	include ROOT . '/lib/piece/postlist.php';
	include ROOT . '/lib/end.php';
?>