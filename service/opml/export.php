<?php
	define('ROOT', '../../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	requireComponent('Bloglounge.Model.Users');
	$owner = User::getById($session['id']);

	header("Content-type: application");
	header("Content-Disposition: attachment; filename=".BLOGLOUNGE."Feeds_" . date("Ymd") . ".opml");
	header("Content-Description: PHP4 Generated Data");

	requireComponent('LZ.PHP.XMLWriter');
	
	$xml = new XMLFile('stdout');
	$xml->startGroup('opml', array('version'=>'1.0'));

	$xml->startGroup('head');
	$xml->write('title', BLOGLOUNGE.' '.BLOGLOUNGE_NAME.' '._t('피드 목록'));
	$xml->write('ownerName', $owner['name']);
	$xml->write('ownerEmail', $owner['email']);
	$xml->endGroup();

	$xml->startGroup('body');
	$db->query("SELECT title, description, blogURL, xmlURL FROM {$database['prefix']}Feeds");
	while ($item = $db->fetchArray()) {
		$xml->write('outline', '', false, array('text'=>$item['title'], 'description'=>$item['description'], 'htmlUrl'=>$item['blogURL'], 'title'=>$item['title'], 'type'=>'rss', 'version'=>'RSS', 'xmlUrl'=>$item['xmlURL']));
	}
	$db->free();
	$xml->endGroup();

	$xml->close();
?>