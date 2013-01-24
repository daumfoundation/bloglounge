<?php		
	define('ROOT', '..');
	include_once( ROOT . '/config.php');
	$targetURL = "http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/center/dashboard";
	header("Location: $targetURL");
?>