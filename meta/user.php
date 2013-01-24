<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	
	$searchFeedId = $accessInfo['action'];
	$searchType = 'user';
	if(is_numeric($searchFeedId)) {
		$user = User::getById($searchFeedId);
		$searchKeyword = $user['loginid'];	
		$searchExtraValue = $searchFeedId;
	} else {
		$searchKeyword = $searchFeedId;	
		$user = User::getByloginId($searchKeyword);
		$searchExtraValue = Feed::getIdListByOwner($user['id']);
	}

	include ROOT . '/lib/begin.php';

	$customQuery = $event->on('Query.feedItems', '');

	$pageCount = $skinConfig->postList; // 페이지갯수
	list($posts, $totalFeedItems) = FeedItem::getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount, false, 0, $customQuery);
	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);

	include ROOT . '/lib/piece/message.php';
	include ROOT . '/lib/piece/postlist.php';
	include ROOT . '/lib/end.php';
?>