<?php
	// ** 검색어 호환 처리
	if (isset($_GET['type']) && Validator::enum($_GET['type'], 'all,tag,blogURL,archive')) {
		switch (strtolower($_GET['type'])) {
			case 'tag':
				$_GET['tag'] = $_GET['keyword'];
				$_GET['keyword'] = '';
				break;
			case 'blogurl':
				$_GET['blogURL'] = $_GET['keyword'];
				$_GET['keyword'] = '';
				break;
			case 'archive':
				$_GET['archive'] = $_GET['keyword'];
				$_GET['keyword'] = '';
				break;
		}
	}	

	$searchType = 'all'; // global
	if (isset($_GET['tag']) && !empty($_GET['tag'])) $searchType = 'tag';
	else if (isset($_GET['blogURL']) && !empty($_GET['blogURL'])) $searchType = 'blogURL';
	else if (isset($_GET['archive']) && !empty($_GET['archive'])) $searchType = 'archive';
	
	$searchKeyword = ''; // global
	if (isset($_GET['keyword']) && !empty($_GET['keyword']))
		$searchKeyword = urldecode(trim($_GET['keyword']));
	else if (isset($_GET['tag']) && !empty($_GET['tag']))
		$searchKeyword = urldecode(trim($_GET['tag']));
	else if (isset($_GET['blogURL']) && !empty($_GET['blogURL']))
		$searchKeyword = urldecode(trim($_GET['blogURL']));
	else if (isset($_GET['archive']) && !empty($_GET['archive']))
		$searchKeyword = urldecode(trim($_GET['archive']));
	else if($accessInfo['controller'] == 'category') {
		$searchKeyword = 'category';
	}

	$searchExtraValue = NULL; // global
?>