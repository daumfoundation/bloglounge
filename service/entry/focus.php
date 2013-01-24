<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	$id = $_POST['id'];
	$focus = $_POST['focus'];

	if(empty($id) || empty($focus) || !in_array($focus, array('y','n'))) {
			$response['error'] = -1;
			$response['message'] = _t('잘못된 접근입니다.');
	} else {
		if (!isAdmin()) {
			$response['error'] = 1;
			$response['message'] = _t('관리자만이 이 기능을 사용할 수 있습니다.');
		} else {
			$ids = explode(',', $id);

			foreach($ids as $id) {				
				if(empty($id)) continue;

				$feedItem = FeedItem::getAll($id);
				
				if($feedItem) {
					FeedItem::edit($id,'focus', $focus);
				} else {
					$response['error'] = -1;
					$response['message'] = _t('잘못된 접근입니다.');
					break;
				}
			}	
			
			if (Validator::getBool(Settings::get('useRssOut'))) {
				requireComponent('Bloglounge.Data.RSSOut');
				RSSOut::refresh('focus');
			}
		}
	}

	func::printRespond($response);
?>
