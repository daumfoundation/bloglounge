<?php
	if(isset($feeds)) {
		$s_feeds = '';
		$src_feeds = $skin->cutSkinTag('feedlist');	
		$subpath = empty($accessInfo['subpath'])?'':'/'.func::firstSlashDelete($accessInfo['subpath']);
		ob_start();

?>		
		$(document).keydown( function(event) {
			if (event.altKey || event.ctrlKey)
				return;
			switch (event.target.nodeName) {
				case "INPUT":
				case "SELECT":
				case "TEXTAREA":
					return;
			}		
			switch (event.keyCode) {
				case 81: //Q
					window.location = "<?php echo $accessInfo['path'];?>/admin";
				break;
				case 65: //A	
<?php
		if($accessInfo['page'] > 1) {
?>
					window.location = "<?php echo $accessInfo['path'];?><?php echo $subpath;?>/?page=<?php echo $accessInfo['page']-1;?>";
<?php
		} else {
?>	
					alert("<?php echo _t('이전 페이지가 없습니다.');?>");
<?php
		}
?>
				break;
				case 83: //S
<?php
		if($accessInfo['page'] < $paging['totalPages']) {
?>
					window.location = "<?php echo $accessInfo['path'];?><?php echo $subpath;?>/?page=<?php echo $accessInfo['page']+1;?>";
<?php
		} else {
?>	
					alert("<?php echo _t('다음 페이지가 없습니다.');?>");
<?php
		}
?>				break;
			}
		});
<?php		
		$shortCutCode = ob_get_contents();
		ob_end_clean();
		$skin->addJavascriptCode($shortCutCode);

		if(count($feeds) > 0) {
			$s_feeds_rep = '';
			$src_feed_rep = $skin->cutSkinTag('feedlist_rep');		
			$index = 0;
			foreach ($feeds as $feed) {	
				$index ++;
				$feed = $event->on('Data.feed', $feed);
				$sp_feeds = $skin->parseTag('feeds_title', $event->on('Text.feedTitle', UTF8::lessenAsByte($feed['title'], $skinConfig->feedListPageTitleLength)), $src_feed_rep);

				$src_feedlogo = $skin->cutSkinTag('cond_feedlogo');
				$feedlogoFile = (!file_exists(ROOT . '/cache/feedlogo/' . $feed['logo']) || empty($feed['logo'])) ? '' : $service['path']. '/cache/feedlogo/'.$feed['logo'];
				$s_feedlogo = (!Validator::is_empty($feed['logo'])) ? $skin->parseTag('feeds_logo', $feedlogoFile, $src_feedlogo) : '';
				$sp_feeds = $skin->dressOn('cond_feedlogo', $src_feedlogo, $s_feedlogo, $sp_feeds);
			
				if(!empty($feedlogoFile)) {
					$sp_feeds = $skin->parseTag('feed_logo_exist', 'feed_logo_exist', $sp_feeds);
				} else {
					$sp_feeds = $skin->parseTag('feed_logo_exist', 'feed_logo_nonexistence', $sp_feeds);
				}
				
				$s_feedrecent = '';
				$src_feedrecent = $skin->cutSkinTag('feedrecent');
				$src_feedrecent_rep = $skin->cutSkinTag('feedrecent_rep');
				$s_feedrecent_rep = '';

					if ($recents = FeedItem::getRecentFeedItemsByFeed($feed['id'], $skinConfig->feedListRecentFeedList)) {	
						$sp_feedrecent_rep = '';
						foreach($recents as $recent) {
							$s_feedrecent_rep = $skin->parseTag('feeds_recent_url', $recent['permalink'], $src_feedrecent_rep);
							$s_feedrecent_rep = $skin->parseTag('feeds_recent_linkurl', $service['path'].'/go/'.$recent['id'], $src_feedrecent_rep);
							$s_feedrecent_rep = $skin->parseTag('feeds_recent_title', $recent['title'], $s_feedrecent_rep);
							$s_feedrecent_rep = $skin->parseTag('feeds_recent_date', date('Y-m-d H:i',$recent['written']), $s_feedrecent_rep);
							$sp_feedrecent_rep .= $s_feedrecent_rep;
						}								
						
						$s_feedrecent .= $skin->dressOn('feedrecent_rep', $src_feedrecent_rep, $sp_feedrecent_rep, $src_feedrecent);

					} else {
						$s_feedrecent = '';
					}

				$sp_feeds = $skin->parseTag('feed_position', ($index==0?'firstItem':($index==count($feeds)?'lastItem':'')), $sp_feeds);

				$sp_feeds = $skin->dressOn('feedrecent', $src_feedrecent, $s_feedrecent, $sp_feeds);

				$sp_feeds = $skin->parseTag('feeds_desc', $event->on('Text.feedDescription', UTF8::lessenAsByte($feed['description'], 200)), $sp_feeds);
				$sp_feeds = $skin->parseTag('feeds_blogurl', $feed['blogURL'], $sp_feeds);
				$sp_feeds = $skin->parseTag('feeds_created', $event->on('Text.feedCreated', (Validator::is_digit($feed['created']) ? date('Y-m-d H:i', $feed['created']) : $feed['created'])), $sp_feeds);
				$sp_feeds = $skin->parseTag('feeds_lastupdate', $event->on('Text.feedLastupdate', (Validator::is_digit($feed['lastUpdate']) ? date('Y-m-d H:i', $feed['lastUpdate']) : $feed['lastUpdate'])), $sp_feeds);
				//$sp_feeds = $skin->parseTag('feeds_search_url', $service['path'].'/?blogURL='.str_replace('http://','',Func::lastSlashDelete($feed['blogURL'])), $sp_feeds);
				$sp_feeds = $skin->parseTag('feeds_linkurl', $service['path'].'/blog/'.$feed['id'], $sp_feeds);

				$s_feeds_rep .= $event->on('Text.feed', $sp_feeds);
				$sp_feeds = '';
			}		

			$s_feeds = $skin->dressOn('feedlist_rep', $src_feed_rep, $s_feeds_rep, $src_feeds);

		} else {
			$s_feeds_rep = '<div class="no_article">'._t("블로그 목록이 비어있습니다.").'</div>';	
			$s_feeds = $skin->dressOn('feedlist_rep', $src_feed_rep, $s_feeds_rep, $src_feeds);
		}

		$skin->dress('feedlist', $s_feeds);
	}
?>