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
		$config = new Settings;
		$newSettings = array();

		foreach ($_POST as $key=>$value) {
			if (!Validator::enum($key, 'skin,title,description,logo,updateCycle,updateProcess,archivePeriod,totalVisit,filter,blackfilter,restrictJoin,restrictBoom,rankBy,rankPeriod,rankLife,welcomePack,language,boomDownReactor,boomDownReactLimit,useRssOut,countRobotVisit,cacheThumbnail,thumbnailLimit')) 
				continue;
			$newSettings[$key] = $db->escape($value);
		}

		if (!$config->setWithArray($newSettings)) {
			$response['error'] = 1 ;
			$response['message'] = $config->_error;
		}

		/*if (($response['error'] == 0) && (Validator::getBool($newSettings['cacheThumbnail']) === false)) { // cacheThumbnail 을 n 으로 껐을때
			$db->execute("UPDATE {$database['prefix']}FeedItems SET thumbnail=NULL");
			func::rmpath(ROOT.'/cache/thumbnail');
		}*/
	}

	func::printRespond($response);
?>