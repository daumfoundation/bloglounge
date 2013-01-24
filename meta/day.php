<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	// 글 목록
	$action = func::decode($accessInfo['action']);

	$searchType = 'archive';
	
	if((Validator::is_digit($action) && strlen($action) == 8)) {
		$targetDate = $searchKeyword = $action;
		$tDate = substr($targetDate, 0, 4).'-'.substr($targetDate, 4, 2).'-'.substr($targetDate, 6, 2);
		$tStart = strtotime("$tDate 00:00:00");
		
		if(isset($accessInfo['value']) && Validator::is_digit($accessInfo['value']) && strlen($accessInfo['value']) == 8) {
			$targetDate = $accessInfo['value'];
			$tDate = substr($targetDate, 0, 4).'-'.substr($targetDate, 4, 2).'-'.substr($targetDate, 6, 2);
			$tEnd = strtotime("$tDate 00:00:00");
			$searchExtraValue = array('start'=>$tStart,'end'=>$tEnd);
		} else {
			$searchExtraValue = $tStart;
		}
	} else {
		switch($action) {
			case 'yesterday':		
				$searchKeyword = date('Ymd',mktime()-86400);
				$searchExtraValue = strtotime( $searchKeyword . ' 00:00:00');
			break;
			case 'today':	
			default:
				$searchKeyword = date('Ymd',mktime());
				$searchExtraValue = strtotime( $searchKeyword . ' 00:00:00');
			break;			
		}
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