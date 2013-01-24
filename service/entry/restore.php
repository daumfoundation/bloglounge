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
				if(empty($id)) continue;

				$feedItem = FeedItem::getAll($id);
				$feed = Feed::getAll($feedItem['feed']);
				
				if(isAdmin() || $feed['owner'] == getLoggedId()) {
					FeedItem::edit($id,'visibility', 'y');
					Feed::edit($feed['id'],array('feedCount'=>$feed['feedCount']+1));		
					if(!empty($feed['category'])) Catregory::rebuildCount($feed['category']);
					
					if($admin_mode) {
						include_once( ROOT . '/lib/admin.php' );
						addAppMessage(_t('선택하신 글을 복원하였습니다.'));
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
