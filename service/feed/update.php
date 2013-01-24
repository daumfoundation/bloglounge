<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	$id = $_POST['id'];

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
				$feed = Feed::getAll($id);
				
				if(isAdmin() || $feed['owner'] == getLoggedId()) {
					$feeder = new Feed;
					$feeder->updateFeed($feed['xmlURL']);
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
