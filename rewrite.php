<?php
	define('ROOT','.');

	include_once(ROOT.'/lib/accessInfo.php');

	if (($accessInfo['controller']!='setup') && (!file_exists(ROOT . '/config.php') || !file_exists(ROOT.'/remove.lock'))) {
		Header("Location: " . ROOT . "/setup"); exit;
	}

	$part = (($qpos = strpos($request_uri, '?')) !== false) ? substr($request_uri, 0, $qpos) : $request_uri;
	switch($accessInfo['controller']) {
		case '':
			include_once(ROOT.'/meta/index.php');
		break;
		case 'setup':
		case 'rss':
		case 'feedlist':
		case 'go':
		case 'blog':
		case 'login':	
		case 'logout':		
		case 'join':
		case 'random':
		case 'search':
		case 'focus':
		case 'category':
		case 'day':
		case 'calling':
		case 'group':
		case 'read':
			include_once(ROOT.'/meta/'.$accessInfo['controller'].'.php');
		break;
		case 'admin':
			include_once(ROOT.'/admin/custom/page.php');
		break;
		case 'e':
		case 'export':
			include_once(ROOT.'/meta/export.php');
		break;
		default:
			// error or custom page ╩Пют
			include_once(ROOT.'/meta/custom.php');
		break;
	}

?>