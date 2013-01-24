<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	$id = $_POST['id'];
	$value = $_POST['value'];

	if(empty($id) || empty($value) || !is_numeric($value)) {
			$response['error'] = -1;
			$response['message'] = _t('잘못된 접근입니다.');
	} else {
		if (!isLoggedIn()) {
			$response['error'] = 1;
			$response['message'] = _t('로그인 한 사람만 이 기능을 사용할 수 있습니다.');
		} else {
			$ids = explode(',', $id);

			foreach($ids as $id) {	
				if(empty($id)) continue;

				$feedItem = FeedItem::getAll($id);
				$feed = Feed::getAll($feedItem['feed']);
				
				if(isAdmin() || $feed['owner'] == getLoggedId()) {
					Category::setItemCategory($id, $value);					
					Category::rebuildCount($value);					
					Category::rebuildCount($feedItem['category']);
				} else {
					$response['error'] = -1;
					$response['message'] = _t('잘못된 접근입니다.');
					break;
				}
			}
		}
	}

	func::printRespond($response);
?>