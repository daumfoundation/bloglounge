<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	include ROOT . '/lib/link.begin.php';	

	if(isset($accessInfo['action'])) {
		$id = $accessInfo['action'];
		$linker_post = FeedItem::getFeedItem($id);
		$linker_feed = Feed::getAll($linker_post['feed']);
		
		FeedItem::edit($linker_post['id'], 'click', $linker_post['click']+1);
	}

	if(!empty($config->linkskin)) {	

		$skin = new Skin;	
		$skin->load('link/'.$config->linkskin);

		include ROOT.'/lib/link/skin.begin.php';
			include ROOT . '/lib/piece/linker.php';	
		include ROOT.'/lib/link/skin.end.php';

	} else {
		if(isset($linker_post)) {	
			header("Location: {$linker_post['permalink']}");
		}
	}
	
	include ROOT . '/lib/link.end.php';

?>