<?php
	function getIssueFocus($input, $config) {
		global $database, $db, $skin, $event, $service, $accessInfo;

		// 첫페이지에만 보이기 ..
		if(!(empty($accessInfo['controller']) && ($accessInfo['page']==1))) {
			return $input;
		}

		$pluginURL = $event->pluginURL;
		requireComponent('LZ.PHP.Media');

		// css
		ob_start();
?>
<style type="text/css"> 
		.issueFocus { border:1px solid #dbdbdb; }
			.issueFocus ul.menu { width:100%; list-style:none; padding:0; margin:0; color:#999999; letter-spacing:2px;background:url("<?php echo $pluginURL;?>/images/bg_dot.gif") repeat-x bottom #fdfdfd; height:28px;  }
			.issueFocus ul.menu li { float:left; padding:8px; padding-left:10px; padding-right:10px; border-right:1px solid #eee; height:11px; cursor:pointer; }
				.issueFocus ul.menu li.selected {font-weight:bold; color:#0f0f0f; background:#ffffff; border-right:1px solid #dbdbdb; height:12px; }
		
			.issueFocus ul.item { list-style:none; padding:10px; padding-bottom:0px; margin:0; display:none; overflow:hidden;  }
				.issueFocus ul.viewed { display:block; }
			.issueFocus ul.item li { padding-top:5px; padding-bottom:5px; }
				.issueFocus ul.item li.empty { color:#aaa; font-size:11px; padding-bottom:14px; }
				
				.issueFocus ul.item li .thumbnail { float:left; margin-right:10px; width:60px; }
					.issueFocus ul.item li .thumbnail img { width:50px; border:1px solid #ddd; padding:2px; }

				.issueFocus ul.item li .data { float:left; width:670px; padding-top:3px; }
					.issueFocus ul.item li .data h3 { font-size:13px; color:#595959; font-weight:bold; margin:0; margin-bottom:6px; }
						.issueFocus ul.item li .data h3 a { color:#595959; text-decoration:none; }
						.issueFocus ul.item li .data h3 a:hover { text-decoration:underline; }

					.issueFocus ul.item li .data .desc { color:#aaa; line-height:16px; font-size:11px; }

				.issueFocus ul.item li .data2 { width: 745px; }
		
		.issueFocus_shadow { height:0px; font-size:0; border-top:1px solid #f5f5f5; margin-bottom:15px; }
</style>
<?php
		$css = ob_get_contents();
		ob_end_clean();
		
		$skin->css($css);

		// js
		ob_start();
?>
<script type="text/javascript"> 
		function issueFocusMouseOverMenu(id) {			
			var menu = $("#"+id+"_menu");
			var item = $("#"+id+"_item");

			$('._issueFocus_menu').each( function() {
				$(this).removeClass('selected');
			});
			$('._issueFocus_item').each( function() {
				$(this).removeClass('viewed');
			});

			menu.addClass('selected');
			item.addClass('viewed');
		}
</script>
<?php
		$javascript = ob_get_contents();
		ob_end_clean();
		
		$skin->javascript($javascript);

		if($config['issueType'] == 'auto') {
			$issueTags = Tag::getIssueTags($config['issueCount']);
		} else {
			$issueTags = explode(',', $config['issueTag']);
			foreach($issueTags as $key=>$tag) {
				$issueTags[$key] = array('name'=>trim($tag));
			}
		}

		ob_start();
?>
		<div class="issueFocus">
			<ul class="menu">
<?php
		// 포커스

		if($config['useFocus']) {
			$focusFeedItems = FeedItem::getRecentFocusFeedItems($config['focusCount']);
?>
				<li id="_issueFocus_focus_menu" class="selected _issueFocus_menu" onclick="goto('<?php echo $service['path'];?>/focus'); return false;" onmouseover="issueFocusMouseOverMenu('_issueFocus_focus');">포커스</li>
<?php
		}
		
		// 이슈태그

		$index = 0;
		foreach($issueTags as $key=>$tag) {
			list($issueTags[$key]['feedItems'], $totalFeedItemCount) = FeedItem::getFeedItems('tag', $tag['name'], null, 1, $config['issueFeedCount']);
			$index++;
?>	
			<li id="_issueFocus_<?php echo $index;?>_menu" class="<?php echo (!$config['useFocus']&&($index==1))?'selected ':'';?>_issueFocus_menu" onclick="goto('<?php echo $service['path'];?>/search/tag/<?php echo rawurlencode($tag['name']);?>'); return false;" onmouseover="issueFocusMouseOverMenu('_issueFocus_<?php echo $index;?>');" title="클릭하시면 태그검색이 가능합니다."><?php echo $tag['name'];?></li>
<?php
		}
?>
			</ul>

			<div class="clear"></div>
<?php
		// 포커스 내용
		if($config['useFocus']) {
?>
			<ul id="_issueFocus_focus_item" class="item _issueFocus_item viewed">
<?php
	if(count($focusFeedItems)>0) {
		foreach($focusFeedItems as $feedItem) {
			$thumbnailFile = '';
			if($media = Media::getMedia($feedItem['thumbnailId'])) {
				$thumbnailFile = Media::getMediaFile($media['thumbnail']);
			}
?>
			<li>
<?php
			if(!empty($thumbnailFile)) {
?>
				<div class="thumbnail">
					<img src="<?php echo $thumbnailFile;?>" alt="미리보기" />
				</div>
<?php
			}
?>
				<div class="data <?php echo empty($thumbnailFile)?'data2':'';?>">
					<h3><a href="<?php echo $service['path'];?>/go/<?php echo $feedItem['id'];?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($feedItem['title']),$config['issueTitleLength']);?></a></h3>
					<div class="desc">
						<?php echo UTF8::lessenAsByte(func::stripHTML($feedItem['description']),$config['issueDescLength']);?>
					</div>
				</div>

				<div class="clear"></div>
			</li>
<?php
		}
	} else {
?>
			<li class="empty">포커스로 지정된 글이 없습니다.</li>
<?php
	}
?>
			</ul>
<?php
		}

		// 이슈태그 내용

		$index = 0;
		foreach($issueTags as $tag) {
			$index ++;
?>
			<ul id="_issueFocus_<?php echo $index;?>_item" class="item _issueFocus_item<?php echo (!$config['useFocus']&&($index==1))?' viewed':'';?>">
<?php
	if(count($tag['feedItems'])>0) {
		foreach($tag['feedItems'] as $feedItem) {
			$thumbnailFile = '';
			if($media = Media::getMedia($feedItem['thumbnailId'])) {
				$thumbnailFile = Media::getMediaFile($media['thumbnail']);
			}
?>
			<li>
<?php
			if(!empty($thumbnailFile)) {
?>
				<div class="thumbnail">
					<img src="<?php echo $thumbnailFile;?>" alt="미리보기" />
				</div>
<?php
			}
?>
				<div class="data <?php echo empty($thumbnailFile)?'data2':'';?>">
					<h3><a href="<?php echo $service['path'];?>/go/<?php echo $feedItem['id'];?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($feedItem['title']),60);?></a></h3>
					<div class="desc">
						<?php echo UTF8::lessenAsByte(func::stripHTML($feedItem['description']),$config['issueDescLength']);?>
					</div>
				</div>

				<div class="clear"></div>
			</li>
<?php
		}
	}
?>
			</ul>
<?php
		}
?>
		</div>
		<div class="issueFocus_shadow"></div>
<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $input . $result;
	}

?>
