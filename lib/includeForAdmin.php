<?php
	include ROOT . '/config.php';
	include ROOT . '/lib/config.php';

	include_once(ROOT.'/lib/accessInfo.php');

	include ROOT . '/lib/init.php';
	if (!defined('NO_SESSION')) include_once(ROOT. '/lib/session.php'); 
	include_once(ROOT. '/lib/auth.php');

	include_once(ROOT. '/lib/components/admin.php');
	include_once(ROOT.'/lib/admin.php');
	include_once(ROOT.'/lib/functions.php');
?>