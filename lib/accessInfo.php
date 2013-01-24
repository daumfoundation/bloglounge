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
	
	$request_uri = str_replace('/?', '?', $request_uri);

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

	if(strpos($controller,'?') !== false) {
		$controller = substr($controller,0,strpos($controller,'?'));
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

	if(strpos($action,'?') !== false) {
		$action = substr($action,0,strpos($action,'?'));
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

	if(strpos($value,'?') !== false) {
		$value = substr($value,0,strpos($value,'?'));
	}

	$pass = array();
	if(!empty($controller)) {
		$pos_value = $controller;
		if(!empty($action)) {
			$pos_value .=  '/' . $action;
			if(!empty($value)) {
				$pos_value .=  '/' . $value;
			}
		}

		$i = 10; // 무한루프 예방 ( pass 최대 10 )
		while(--$i>0) {
			$pos = strpos($request_uri, $pos_value);
			if($pos !== false) {
				$str = substr($request_uri, $pos + strlen($pos_value));
				$end = strpos($str,'/');
				if($end === false) $end = strlen($str);

				$v = substr($str, 0, $end);
				$pos_value .= $v . '/';		
				if(empty($v)) continue;

				array_push($pass, $v);
			} else {
				break;
			}
		}
	}

	$page = !isset($_GET['page']) ? 1 : $_GET['page'];  // global	
	if (($page < 1) || !is_numeric($page) || !preg_match("/^\d+$/",$page)) {
		$page = 1;
	}

	$i = strpos($request_uri, '?');
	if($i !== false) {
		$request_uri = substr($request_uri, 0 , $i);
	}

	$urls = array();
	foreach($_GET as $k=>$v) {
		$urls[$k] = $v;
	}

	$accessInfo = array(
		'host'     => $_SERVER['HTTP_HOST'],
		'controller'	   => $controller,
		'action'	=> $action,
		'value'		=> $value,
		'pass'		=> $pass,
		'page'		=> $page,
		'url'		=> $urls,
		'subpath' =>  rtrim($request_uri, '/'),
		'position' => $_SERVER["SCRIPT_NAME"],
		'path'	   => $path,
		'address'	=> ''
	);
	if(strpos($accessInfo['subpath'],'http://')!==false) {
		$accessInfo['address'] = urldecode(str_replace('/'.$controller.'/','',$accessInfo['subpath']));
	}
?>