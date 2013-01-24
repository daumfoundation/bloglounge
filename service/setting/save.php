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
			if (!Validator::enum($key, 'skin,title,description,logo,updateCycle,updateProcess,archivePeriod,totalVisit,filter,blackfilter,restrictJoin,restrictBoom,rankBy,rankPeriod,rankLife,welcomePack,language,boomDownReactor,boomDownReactLimit,useRssOut,countRobotVisit,thumbnailLimit,thumbnailSize,feeditemsOnRss,summarySave,filterType,blackfilterType,useVerifier,verifierType,verifier')) 
				continue;
			$newSettings[$key] = $db->escape($value);
		}

		if (!$config->setWithArray($newSettings)) {
			$response['error'] = 1 ;
			$response['message'] = $config->_error;
		}
	}

	func::printRespond($response);
?>