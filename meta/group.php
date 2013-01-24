<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	$searchType = 'group';
	$searchKeyword = func::decode($accessInfo['action']);
	if(!empty($accessInfo['value'])) $searchExtraValue = $accessInfo['value'];

	include ROOT . '/lib/begin.php';

	// 글 목록

	$pageCount = $skinConfig->postList; // 페이지갯수
	list($posts, $totalFeedItems) = FeedItem::getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount);
	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);
	
	$group = Group::getByName($searchKeyword);
	$groupCategories = GroupCategory::getList($group['id']);
	
	if(count($groupCategories)>0) {
		$src_group = $skin->cutSkinTag('group_category');
		$groups = Group::getList();
		if(count($groups) > 0) {
			$sp_group = "<ul>\n";
				foreach($groupCategories as $groupCategory) {
					$sp_group .= "<li><a href=\"{$servicePath}/group/".func::encode($group['name'])."/".func::encode($groupCategory['name'])."\">{$groupCategory['name']}</a>\n";
				}
			$sp_group .= "</ul>\n";

			$s_group = $skin->parseTag('group_category_list',$sp_group, $src_group);
		} else {
			$s_group = '';
		}
		$skin->dress('group_category', $s_group);
	}

	include ROOT . '/lib/piece/message.php';
	include ROOT . '/lib/piece/postlist.php';
	include ROOT . '/lib/end.php';
?>