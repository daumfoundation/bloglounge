<?php
	define('ROOT', '../..');
	include ROOT . '/lib/includeForAjax.php';

	requireStrictRoute();

	$response = array();
	$response['error'] = 0;
	$response['message'] = '';
	
	if (!isAdmin()) {
		$response['error'] = 1;
		$response['message'] = _t('관리자만이 이 기능을 사용할 수 있습니다.');
	} else {
		$arg = array();
		foreach ($_POST as $key=>$value) {
			if (!Validator::enum($key, 'postList,postListDivision,postListDirection,postTitleLength,postDescLength,postNewLife,feedList,feedOrder,feedTitleLength,boomList,boomTitleLength,feedListPage,feedListPageOrder,feedListPageTitleLength,feedListRecentFeedList,focusList,focusTitleLength,focusDescLength,tagCloudOrder,tagCloudLimit'))
				continue;
			$arg[$key] = $value;
		}

		$__s = new SkinSettings;
		if (!$__s->setWithArray($arg)) {
			$response['error'] = 1;
			$rseponse['message'] = $__s->_error;
		}
	}


	func::printRespond($response);
?>
