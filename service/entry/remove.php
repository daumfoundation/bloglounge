<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	$id = $_POST['id'];
	$admin_mode = isset($_POST['admin_mode'])?true:false;

	if(empty($id)) {
			$response['error'] = -1;
			$response['message'] = _t('잘못된 접근입니다.');
	} else {
		if (!isLoggedIn()) {
			$response['error'] = 1;
			$response['message'] = _t('로그인 한 사람만 이 기능을 사용할 수 있습니다.');
		} else {
			$ids = explode(',', $id);

			foreach($ids as $id) {
				$feedItem = FeedItem::getAll($id);
				$feed = Feed::getAll($feedItem['feed']);
				
				if(isAdmin() || $feed['owner'] == getLoggedId()) {
					FeedItem::delete($id);

					if($admin_mode) {
						include_once( ROOT . '/lib/admin.php' );
						addAppMessage(_t('선택하신 글을 삭제하였습니다.'));
					}
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
