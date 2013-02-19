<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	include ROOT . '/lib/begin.php';	

	if(isset($accessInfo['action'])) {
		$id = $accessInfo['action'];
		$post = FeedItem::getFeedItem($id);
		FeedItem::edit($post['id'], 'click', $post['click']+1);	
		
		$page = FeedItem::getPageFromWritten($post['written']);

		if(!isAdmin()) {
			$filter = ' WHERE  (i.visibility = "y") AND (i.feedVisibility = "y") ';
		} else {
			$filter = ' WHERE  (i.visibility != "d") ';
		}
		
		$pageCut = 5;
		$pageCount = 1;		
		$paging = Func::makePaging($page, $pageCount, FeedItem::getFeedItemCount($filter), $pageCut);

		$pageCount = $paging['pageEnd'] - $paging['pageStart'] + 1;
		$result = FeedItem::getIdListFromPage($paging['pageStart'], $filter  ,$pageCount);

		$pageDatas = array();
		$start = $paging['pageStart']-1;
		if($start <= 0) $start = 1;

		for($i=0;$i<count($result);$i++) {			
			$item = $result[$i];
			$pageDatas[$start++] = '/read/'.$item['id'];
		}

		$paging['pageDatas'] = $pageDatas;
	}

	if($post["visibility"]!="y") $post = array();
	
	include ROOT . '/lib/piece/post.php';
	include ROOT . '/lib/end.php';
?>
