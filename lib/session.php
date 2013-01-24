<?php
	requireComponent('LZ.PHP.Session');
	session_name('S20_BLOGLOUNGE_SESSION');

	setSession();
	session_set_save_handler('openSession', 'closeSession', 'readSession', 'writeSession', 'destroySession', 'gcSession');
	session_cache_expire(1);
	session_set_cookie_params(0, '/', ((substr(strtolower($_SERVER['HTTP_HOST']), 0, 4) == 'www.') ? substr($_SERVER['HTTP_HOST'], 3) : $_SERVER['HTTP_HOST']));
	register_shutdown_function('session_write_close');
	if (session_start() !== true) {
		header('HTTP/1.1 503 Service Unavailable');
	}	

	$session = array();
	$session['id'] = isset($_SESSION['_app_id_'])?$_SESSION['_app_id_']:0;
	$session['message'] = isset($_SESSION['_app_message_'])?$_SESSION['_app_message_']:'';
?>
