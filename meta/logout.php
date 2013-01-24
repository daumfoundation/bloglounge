<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';

	logout();

	$returnURL = isset($_GET['returnURL']) ? $_GET['returnURL'] : '';
	if (empty($_SERVER['HTTP_REFERER']) && empty($returnURL)) {
		$returnURL = $service['path'];
	}
	$returnLocation = (empty($returnURL) ? $_SERVER['HTTP_REFERER'] : rawurldecode($returnURL));
	header('Location: '.$returnLocation);
?>