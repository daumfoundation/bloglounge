<?php	
	define('ROOT', '../..');
	include_once( ROOT . '/config.php');
	$targetURL = "http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/blog/list";
	header("Location: $targetURL");
?>
