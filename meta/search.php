<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	// 글 목록

	$searchType = $accessInfo['action'];
	$searchKeyword = func::decode($accessInfo['value']);

	if ($searchType=='tag') {
    } else if ($searchType=='blogURL') { // 블로그주소			
		if(!empty($searchKeyword)) {
			$searchFeedId = Feed::blogURL2Id('http://'.str_replace('http://', '', $searchKeyword));
			$searchExtraValue = $searchFeedId;
		}
	} else if($searchType=='archive') { // 날짜..
		$targetDate = (!Validator::is_digit($searchKeyword) || strlen($searchKeyword) != 8) ? date("Ymd") : $searchKeyword;
		$tDate = substr($targetDate, 0, 4).'-'.substr($targetDate, 4, 2).'-'.substr($targetDate, 6, 2);
		$tStart = strtotime("$tDate 00:00:00");
		$searchExtraValue = $tStart;
	}
	
	include ROOT . '/lib/begin.php';

	$pageCount = $skinConfig->postList; // 페이지갯수
	list($posts, $totalFeedItems) = FeedItem::getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount);
	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);

	include ROOT . '/lib/piece/message.php';
	include ROOT . '/lib/piece/postlist.php';
	include ROOT . '/lib/end.php';
?>