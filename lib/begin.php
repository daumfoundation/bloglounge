<?php
	// 카운터는 하루에 한번만
	if (!isset($_COOKIE['visited'])) {
		requireComponent('Bloglounge.Data.Stats');
		Stats::visit($config->countRobotVisit);
		setcookie("visited", "bloglounge", time() + 86400, "/", ((substr(strtolower($_SERVER['HTTP_HOST']), 0, 4) == 'www.') ? substr($_SERVER['HTTP_HOST'], 3) : $_SERVER['HTTP_HOST']));
	}	
	
	$config = new Settings();
	$skinConfig = new SkinSettings();

	$event->on('Meta.begin');

	include_once(ROOT.'/lib/skin.begin.php');
?>