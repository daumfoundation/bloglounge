<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireMembership();

	include ROOT. '/lib/piece/adminHeader.php';		
	requireComponent('Bloglounge.Data.Stats');
?>
<div class="wrap">
	<div class="sidebar">
		<!-- notice -->
		<div id="notice_sidebar" class="sidebar_item">
<?php
	list($feedItems, $totalFeedItems) = FeedItem::getFeedItems('blogURL','bloglounge.itcanus.net/bloglounge_notice',Feed::blogURL2Id('http://bloglounge.itcanus.net/bloglounge_notice'),1,10);
	if($totalFeedItems==0) { // 자동등록된 공지사항 피드를 삭제하였을경우 .. 동적으로 읽어 온다.
		list($status, $feed, $xml) = Feed::getRemoteFeed('http://bloglounge.itcanus.net/bloglounge_notice/rss');
		if($status == 0) {
			$feedItems = Feed::getFeedItems($xml);
			$totalFeedItems = count($feedItems);
		}
	}
?>
		<?php echo drawAdminBoxBegin('notice');?>
			<div class="title">
				<a href="http://bloglounge.itcanus.net/" target="_blank">공지사항</a>
			</div>
			<div class="line"></div>
			<div class="data">
				<ul>
<?php
	if(count($feedItems)>0) {
				foreach($feedItems as $feedItem) {
?>
					<li><span class="date"><?php echo date('y/m/d', $feedItem['written']);?></span> <a href="<?php echo $feedItem['permalink'];?>" title="<?php echo $feedItem['title'];?>" target="_blank"><?php echo $db->lessen($feedItem['title'],13);?></a></li>
<?php
				}
	} else {
?>
			<li class="empty">글이 없습니다.</li>
<?php
	}
?>
				</ul>
			</div>
			<?php echo drawAdminBoxEnd();?>
		</div>

		<!-- new version -->
		<div id="new_version_sidebar" class="sidebar_item">
<?php
	list($feedItems, $totalFeedItems) = FeedItem::getFeedItems('blogURL','bloglounge.itcanus.net/bloglounge_download',Feed::blogURL2Id('http://bloglounge.itcanus.net/bloglounge_download'),1,10);
	if($totalFeedItems==0) { // 자동등록된 공지사항 피드를 삭제하였을경우 .. 동적으로 읽어 온다.
		list($status, $feed, $xml) = Feed::getRemoteFeed('http://bloglounge.itcanus.net/bloglounge_download/rss');
		if($status == 0) {
			$feedItems = Feed::getFeedItems($xml);
			$totalFeedItems = count($feedItems);
		}
	}
?>
		<?php echo drawAdminBoxBegin('new_version');?>
			<div class="title">
				<a href="http://bloglounge.itcanus.net/bloglounge_download" target="_blank"><?php echo _t('다운로드');?></a> <span class="subtitle"><?php echo _t('현재버전');?> v<?php echo BLOGLOUNGE_VERSION;?></span>
			</div>
			<div class="line"></div>
			<div class="data">
				<ul>
<?php
	if(count($feedItems)>0) {
				foreach($feedItems as $feedItem) {
?>
					<li><span class="date"><?php echo date('y/m/d', $feedItem['written']);?></span> <a href="<?php echo $feedItem['permalink'];?>" title="<?php echo $feedItem['title'];?>" target="_blank"><?php echo trim(str_replace('블로그라운지','',$db->lessen($feedItem['title'])));?></a></li>
<?php
				}
	} else {
?>
			<li class="empty"><?php echo _t('글이 없습니다.');?></li>
<?php
	}
?>
				</ul>
			</div>
			<?php echo drawAdminBoxEnd();?>
		</div>

		<!-- tools -->
		<div id="tool_sidebar" class="sidebar_item">
<?php
?>
		<?php echo drawAdminBoxBegin('notice');?>
			<div class="title">
				<?php echo _t('바로가기');?>
			</div>
			<div class="line"></div>
			<div class="data">
				<ul>
					<li><a href="<?php echo $service['path'];?>/admin/blog/add"><?php echo _t('블로그추가');?></a></li>
				</ul>
			</div>
			<?php echo drawAdminBoxEnd();?>
		</div>
	</div> <!-- sidebar close -->

	<div class="contents">
		<div class="contents_item">
			<?php echo drawAdminBoxBegin('recent_feed_n_item');?>
				<!-- 최근 글.. -->
<?php
				if($is_admin) {
					$feedItems = FeedItem::getRecentFeedItems(10);
				} else {
					list($feeds, $totalFeedCount) = Feed::getFeedsByOwner(getLoggedId(), 'all');
					$feedIds = array();					
					foreach($feeds as $feed) {
						array_push($feedIds, $feed['id']);
					}
					$feedItems = FeedItem::getRecentFeedItemsByFeed($feedIds, 10);
				}
?>
				<div class="contents_in_item1">
					<div class="more_wrap">
						<div class="title"><?php echo _t('최근 글');?></div>
						<div class="more">
							<a href="<?php echo $service['path'];?>/admin/blog/entrylist"><?php echo _t('더보기..');?></a>
						</div>
						<div class="clear"></div>
					</div>					
					<div class="line"></div>
					<div class="data">
					<ul>
<?php
	if(count($feedItems)>0) {
				foreach($feedItems as $feedItem) {
						$title = func::stripHTML($feedItem['title']);
?>
						<li><span class="date"><?php echo date('y/m/d', $feedItem['written']);?></span> <a href="<?php echo $service['path'];?>/admin/blog/entrylist/?read=<?php echo $feedItem['id'];?>" title="<?php echo $title;?>"><?php echo $db->lessen($title,22);?></a> 
<?php
					if(!empty($feedItem['author'])) {
?>
						by <span class="author"><?php echo $db->lessen($feedItem['author'],4);?>
<?php
					}
?>
						</span></li>
<?php
				}
	} else {
?>
					<li class="empty"><?php echo _t('수집된 글이 없습니다.');?></span>
<?php
	}
				if($is_admin) {
					$feedItemCount = FeedItem::getFeedItemCount('WHERE i.visibility = "y"');
					$feedItemUpdate = Feed::getFeedLastUpdate('WHERE i.visibility = "y"');
				} else {
					$feedItemCount = FeedItem::getFeedItemCount('WHERE i.visibility = "y" AND i.feed IN ('.implode(',',$feedIds).')');
					$feedItemUpdate = Feed::getFeedLastUpdate('WHERE i.visibility = "y" AND i.feed IN ('.implode(',',$feedIds).')');
				}
				
				if(!empty($feedItemUpdate)) {
					$feedItemUpdateText = Func::dateToString($feedItemUpdate);
				} else {
					$feedItemUpdateText = array(_t('업데이트 없음'),'');
				}
?>
					</ul>
					</div>
					<div class="information">
						<span class="name"><?php echo _t("전체글");?></span> <span class="sep">|</span> <span class="count"><?php echo $feedItemCount;?></span>개 &nbsp;&nbsp; <span class="name"><?php echo _t('마지막 업데이트');?></span> <span class="sep">|</span> <span class="date"><?php echo empty($feedItemUpdate)?'':date('y.m.d H:i:s', $feedItemUpdate);?> (<?php echo _f($feedItemUpdateText[0], $feedItemUpdateText[1]);?>)</span> 
					</div>
				</div>	
				<!-- 최근 등록된 블로그.. -->
<?php
				if($is_admin) {
					$feeds = Feed::getRecentFeeds(10);
					$feedCount = Feed::getFeedCount('WHERE i.visibility="y"');
				} else {
					$feeds = Feed::getRecentFeedsByOwner(getLoggedId(), 10);
					$feedCount = Feed::getFeedCount('WHERE i.owner = ' . getLoggedId() . ' AND i.visibility="y"');
				}
?>
				<div class="contents_in_item2">
					<div class="more_wrap">
						<div class="title"><?php echo _t('최근 등록된 블로그');?></div>
						<div class="more">
							<a href="<?php echo $service['path'];?>/admin/blog/list"><?php echo _t('더보기..');?></a>
						</div>
						<div class="clear"></div>
					</div>
					<div class="line"></div>
					<div class="data">
					<ul>
<?php
			if(count($feeds) > 0) {
				foreach($feeds as $feed) {		
					$title = func::stripHTML($feed['title']);
?>
						<li><span class="date"><?php echo date('y/m/d', $feed['created']);?></span> <a href="<?php echo $service['path'];?>/admin/blog/list/?read=<?php echo $feed['id'];?>" title="<?php echo $title;?>"><?php echo $db->lessen($title,26);?></a></li>
<?php
				}
			} else {
?>
						<li class="empty"><?php echo _t('등록된 블로그가 없습니다.');?></li>
<?php
			}
?>
					</ul>
					</div>					
					<div class="information">
						<span class="name"><?php echo _t("전체블로그");?></span> <span class="sep">|</span> <span class="count"><?php echo $feedCount;?></span>개 
					</div>
				</div>
				<div class="clear"></div>
			<?php echo drawAdminBoxEnd();?>

			<br />


			<div class="contents_item1">
<?php
				if($is_admin) {
					$lastUpdate = Stats::getLatestUpdate();
?>

				<?php echo drawAdminBoxBegin('simple_stat');?>
					<div class="title">
						<?php echo _t('간략통계');?>
					</div>
					<div class="line"></div>
					<div class="data">					
						<ol>
							<li><div class="title"><?php echo _t('마지막 업데이트');?></div> <div class="data"><?php echo empty($lastUpdate)?_t('업데이트 없음'):date('Y-m-d H:i:s', $lastUpdate);?></div><div class="clear"></div></li>
							<li><div class="title"><?php echo _t('전체 블로그');?></div> <div class="data"><?php echo _f('%1 개', number_format(Stats::countFeeds()));?></div><div class="clear"></div></li>
							<li><div class="title"><?php echo _t('수집된 글수');?></div> <div class="data"><?php echo _f('%1 개', number_format(Stats::countFeedItems()));?></div><div class="clear"></div></li>
							<li><div class="title"><?php echo _t('오늘의 방문자');?></div> <div class="data"><?php echo _f('%1 명', number_format(Stats::getTodayVisits()));?> (<?php echo _f('전체 방문자 : %1 명', number_format(Stats::getVisits())); ?>)</div><div class="clear"></div></li>
						</ol>
						
					</div>
				<?php echo drawAdminBoxEnd('');?>
<?php
				}
?>
			</div> <!-- contents_item1 close -->
			<div class="contents_item2">
			</div> <!-- contents_item2 close -->
			<div class="clear"></div>

		</div><!-- contents_item close -->
	</div> <!-- contents close -->
	<div class="clear"></div>
</div>
<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
