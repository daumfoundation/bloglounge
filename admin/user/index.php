<?php	
	define('ROOT', '../..');
	include_once( ROOT . '/config.php');
	$targetURL = "http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/user/myinfo";
	header("Location: $targetURL");
?>
