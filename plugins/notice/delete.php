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
		if (!isAdmin()) {
			$response['error'] = 1;
			$response['message'] = _t('관리자만이 이 기능을 사용할 수 있습니다.');
		} else {
			$ids = explode(',', $id);

			foreach($ids as $id) {			
				$db->execute('INSERT INTO ' . $database['prefix'] . 'PluginNotice ( id, exceptFeeditem ) VALUES ( NULL, ' . $id . ')');			
			}
		}
	}

	func::printRespond($response);
?>
