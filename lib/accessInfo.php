<?php
	$request_uri = str_replace('index.php', '', $_SERVER["REQUEST_URI"]);
	
	if(isset($service['path'])) {
		$path = $service['path'];
	} else {
		if(strpos($_SERVER['PHP_SELF'],'rewrite.php') === false) {
			$path = substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF']) - 10);
		} else {
			$path = substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF']) - 12);
		}
	}

	if(substr($request_uri,0,strlen($path)) == $path) {
		$request_uri = substr($request_uri,strlen($path));
	}

	$controller = '';
	if(!empty($request_uri) && ($request_uri != '/')) {
		$start = strpos($request_uri,'/');
		if($start === false) $start = 0;
		else $start += 1;
		$qpos = strpos($request_uri, '?');
		$end = strpos($request_uri,'/',2);

		if($end === false) $end = strlen($request_uri);
		else $end -= 1;
		
		if($qpos !== false && $end > $qpos)	$end = $qpos - 1;

		$controller = substr($request_uri, $start, $end);
	}

	$action = '';
	if(!empty($controller)) {
		$pos = strpos($request_uri, $controller . '/');
		if($pos !== false) {
			$str = substr($request_uri, $pos + strlen($controller . '/'));
			$end = strpos($str,'/');
			if($end === false) $end = strlen($str);

			$action = substr($str, 0, $end);
		}
	}

	$value = '';
	if(!empty($controller) && !empty($action)) {
		$pos = strpos($request_uri, $controller . '/' . $action . '/');
		if($pos !== false) {
			$str = substr($request_uri, $pos + strlen($controller . '/' . $action . '/'));
			$end = strpos($str,'/');
			if($end === false) $end = strlen($str);

			$value = substr($str, 0, $end);
		}
	}
	
	$page = 1;
	if(isset($_GET['page'])) {
		$page = $_GET['page'];
	}

	$i = strpos($request_uri, '?');
	if($i !== false) {
		$request_uri = substr($request_uri, 0 , $i);
	}

	$accessInfo = array(
		'host'     => $_SERVER['HTTP_HOST'],
		'controller'	   => $controller,
		'action'	=> $action,
		'value'		=> $value,
		'page'		=> $page,
		'fullpath' =>  rtrim($request_uri, '/'),
		'position' => $_SERVER["SCRIPT_NAME"],
		'path'	   => $path
	);

?>