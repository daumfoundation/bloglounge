<?php
	function getMagazineFocus($input, $config) {	
		global $database, $db, $skin, $event, $service, $accessInfo;

		// 첫페이지에만 보이기 ..
		/*
		if(!(empty($accessInfo['controller']) && ($accessInfo['page']==1))) {
			return $input;
		}
		*/

		$pluginURL = $event->pluginURL;
		requireComponent('LZ.PHP.Media');

		if(!isset($config['tabDelay'])) {
			$config['tabDelay'] = 0;
		}
		
		ob_start();
?>
		<style type="text/css">
			.magazineFocusWrap {  padding-top:10px; }
				.magazineFocusTable { width:100%; border:5px solid #b2c941; }
				.magazineFocusTable .leftTab { width:166px; overflow:hidden; background:url("<?php echo $pluginURL;?>/images/bg_left_tab.gif") repeat-y right #fafafa; vertical-align:top; }
					.magazineFocusTable .leftTab ul { list-style:none; margin:0; padding:0; }
						.magazineFocusTable .leftTab ul li { color:#989898; padding-left:20px; padding-top:8px; padding-bottom:8px; margin-right:1px; background:url("<?php echo $pluginURL;?>/images/bg_left_line.gif") repeat-x top; cursor:pointer; font-size:13px; }
						
						.magazineFocusTable .leftTab ul li a { color:#989898; text-decoration:none; }

						.magazineFocusTable .leftTab ul li.first { background-image:none; }
						.magazineFocusTable .leftTab ul li.selected { background-color:#ffffff; margin-right:0; color:#333; font-weight:bold; }
						.magazineFocusTable .leftTab ul li.selected span { background:url("<?php echo $pluginURL;?>/images/bg_left_select.gif") no-repeat right; padding-right:14px; }

						.magazineFocusTable .leftTab ul li.selected a { color:#333; }

						.magazineFocusTable .leftTab ul li.dummy { cursor:default; }
				
				.magazineFocusTable .mainData { vertical-align:top; }

					.magazineFocusTable .mainData ul.item { list-style:none; padding:10px; padding-right:0; padding-bottom:0px; margin:0; display:none; overflow:hidden;  }
						.magazineFocusTable .mainData ul.viewed { display:block; }
					.magazineFocusTable .mainData ul.item li { width:430px; height:68px; padding-bottom:5px; margin-bottom:10px; overflow:hidden; border-bottom:1px solid #ececec; }
						.magazineFocusTable .mainData ul.item li.empty { color:#aaa; font-size:11px; padding-bottom:14px; }
						
						.magazineFocusTable .mainData ul.item li .thumbnail { float:left; margin-right:10px; width:62px; }
							.magazineFocusTable .mainData ul.item li .thumbnail img { width:62px; }

						.magazineFocusTable .mainData ul.item li .data { float:left; width:350px; }
							.magazineFocusTable .mainData ul.item li .data h3 { font-size:14px; color:#595959; font-weight:bold; margin:0; margin-bottom:2px; }
								.magazineFocusTable .mainData ul.item li .data h3 a { color:#4e4e4e; text-decoration:none; }
								.magazineFocusTable .mainData ul.item li .data h3 a:hover { text-decoration:underline; }
							
							.magazineFocusTable .mainData ul.item li .permalink { font-size:11px; margin-bottom:8px; height:12px; line-height:12px; overflow:hidden; }
								.magazineFocusTable .mainData ul.item li .permalink a { color:#909090; text-decoration:none; }
								.magazineFocusTable .mainData ul.item li .permalink a:hover { text-decoration:underline; }

							.magazineFocusTable .mainData ul.item li .data .desc { color:#5d5d5d; line-height:14px; font-size:11px; }

						.magazineFocusTable .mainData ul.item li .data2 { width: 430px; }

						.magazineFocusTable .mainData ul.item li.title_only { padding-bottom:0; border:0; height:14px; background:url("<?php echo $pluginURL;?>/images/bg_li.gif") no-repeat 0px 6px; padding-left:8px; letter-spacing:0px; }
							.magazineFocusTable .mainData ul.item li.title_only a { color:#4e4e4e; text-decoration:none; }
							.magazineFocusTable .mainData ul.item li.title_only a:hover { text-decoration:underline; }

							.magazineFocusTable .mainData ul.item li.title_only .sep { color:#eee; }
							.magazineFocusTable .mainData ul.item li.title_only .feedTitle { color:#999; font:11px Dotum; }
			
				.magazineFocusTable .focusImageWrap { padding:5px;  }
				.magazineFocusTable .focusImageWrap .focusImageDataWrap { padding:10px; padding-bottom:0px; background:url("<?php echo $pluginURL;?>/images/bg.gif") repeat-x; }
				.magazineFocusTable .focusImageWrap .focusImageDatas { float:left; width: 300px; height:164px; position: relative; overflow: hidden; }
				
					.magazineFocusTable .focusImageWrap #focusImageData { position:absolute; z-index:98; }
					
						.magazineFocusTable .focusImageWrap .focusImage { position:absolute; width: 300px; height: 160px; overflow:hidden; }
							.magazineFocusTable .focusImageWrap .focusImage img { width:300px; height: 344px; }

						.magazineFocusTable .focusImageWrap .focusShadow { position:absolute;  top:160px; width:300px; height:4px; background:url("<?php echo $pluginURL;?>/images/bg_image_shadow.gif") repeat-x; font-size:0; line-height:0; z-index:102; }
						
						.magazineFocusTable .focusImageWrap .focusTitleBG { width:300px; position:absolute; height:40px; background:#000000; opacity:0.3; filter: alpha(opacity = 30); z-index: 100; }
						
						.magazineFocusTable .focusImageWrap .focusImageTitle { width: 300px; color:#ffffff;  position:absolute; top:137px; padding:5px; z-index: 101; font-size:13px; line-height:15px; }

							.magazineFocusTable .focusImageWrap .focusImageTitle a { color:#ffffff; text-decoration:none; font-weight:bold; }
							.magazineFocusTable .focusImageWrap .focusImageTitle a:hover { text-decoration:underline; }	

							.magazineFocusTable .focusImageWrap .focusImageTitle .blogtitle { font-size:11px; color:#cdcdcd; }

				.magazineFocusTable .focusImageWrap .focusImageNav { float:left; margin-left:14px; }
					.magazineFocusTable .focusImageWrap .focusImageNav ul { list-style:none; margin:0; padding:0; }
					.magazineFocusTable .focusImageWrap .focusImageNav ul li { margin-bottom:6px; }
					.magazineFocusTable .focusImageWrap .focusImageNav ul li .thumbnail { width:30px; height:30px; overflow:hidden; border:1px solid #000; }
					.magazineFocusTable .focusImageWrap .focusImageNav ul li .thumbnail img { height:30px; }

					.magazineFocusTable .focusImageWrap .focusImageNav ul li.selected .thumbnail { opacity:0.5; filter: alpha(opacity = 50); }

					.magazineFocusTable .focusImageWrap .focusImageNav ul li .shadow { width:32px; height:4px; background:url("<?php echo $pluginURL;?>/images/bg_image_shadow.gif") repeat-x; font-size:0; line-height:0; }

					.magazineFocusTable .focusImageEmpty { text-align: center; color: #999; }					
		</style>
<?php
		$css = ob_get_contents();
		ob_end_clean();

		$skin->css($css);

		ob_start();
?>
	<script type="text/javascript"> 		
		var magazineFocusTabIntervalId = 0;

		function magazineFocusMouseOverMenu(id) {	
<?php
	if($config['tabDelay']>0) {
?>
			if(magazineFocusTabIntervalId!=0) {
				clearInterval(magazineFocusTabIntervalId);
				magazineFocusTabIntervalId = 0;
			}

			magazineFocusTabIntervalId = setInterval( function() {
<?php
	}
?>
				var menu = $("#"+id+"_menu");
				var item = $("#"+id+"_item");

				$('._magazineFocus_menu').each( function() {
					$(this).removeClass('selected');
				});
				$('._magazineFocus_item').each( function() {
					$(this).removeClass('viewed');
				});

			menu.addClass('selected');
			item.addClass('viewed');	
<?php
		if($config['tabDelay']>0) {
?>				
				clearInterval(magazineFocusTabIntervalId);
				magazineFocusTabIntervalId = 0;
			},<?php echo $config['tabDelay'];?>);
<?php
		}
?>
		}
		function magazineFocusMouseOut() {		
			if(magazineFocusTabIntervalId!=0) {
				clearInterval(magazineFocusTabIntervalId);
				magazineFocusTabIntervalId = 0;
			}
		}
		var lastSelectFocusImage = null;
		function moveFocusImagePosition(objId,pos) {
		//	$("#focusImageData").animate({'top':pos+'px'},'fast');
			$("#focusImageData").css('top',pos+'px');

			var obj = $("#"+objId);
			if(lastSelectFocusImage!=null) {
				lastSelectFocusImage.removeClass('selected');
			}
			obj.addClass('selected');
			lastSelectFocusImage = obj;
		}
	</script>
<?php
		$javascript = ob_get_contents();
		ob_end_clean();
		
		$skin->javascript($javascript);
		
		// 포커스이미지
		$count = 4;

		$focusImages = $db->queryAll('SELECT fi.id,fi.feed,fi.permalink,fi.title,fi.description,fi.author,fi.written,m.source FROM '.$database['prefix'].'FeedItems fi LEFT JOIN '.$database['prefix'].'Medias m ON ( m.feeditem = fi.id ) WHERE fi.focus = "y" AND fi.visibility = "y" AND m.width >= 300 GROUP BY fi.id ORDER BY fi.written DESC LIMIT '. $count);
		
		$path = ROOT . '/cache/thumbnail/m_focus';
		if (!is_dir($path)) {
			mkdir($path);
			@chmod($path, 0777);
		}
		
		$media = new Media;
		foreach($focusImages as $item) {
			if(!file_exists($path.'/'.$item['id'].'.jpg')) {
				$media->getThumbnail($item['source'], 350, 160, $path, $item['id'], 'crop');
			}
		}
		
		// 이슈태그
		
		if($config['issueType'] == 'auto') {
			$issueTags = Tag::getIssueTags($config['issueCount']);
		} else {
			$issueTags = explode(',', $config['issueTag']);
			foreach($issueTags as $key=>$tag) {
				$issueTags[$key] = array('name'=>trim($tag));
			}
		}
	
		// 이슈태그

		ob_start();
?>
		<div class="magazineFocusWrap">
			<table class="magazineFocusTable" cellpadding="0" cellspacing="0">
				<tr>
					<td class="leftTab">
						<ul>
<?php
			$index = 0;
			foreach($issueTags as $key=>$tag) {
				list($issueTags[$key]['feedItems'], $totalFeedItemCount) = FeedItem::getFeedItems('tag', $tag['name'], null, 1, $config['issueFeedCount']);
				$index++;
?>
							<li id="_magazineFocus_<?php echo $index;?>_menu" class="<?php echo ($index==1)?'first selected ':'';?>_magazineFocus_menu" onclick="goto('<?php echo $service['path'];?>/search/tag/<?php echo rawurlencode($tag['name']);?>'); return false;" onmouseover="magazineFocusMouseOverMenu('_magazineFocus_<?php echo $index;?>');" title="클릭하시면 태그검색이 가능합니다."><span><?php echo UTF8::lessen($tag['name'],10);?></span></li>
<?php
		}
?>							<li class="dummy"></li>
						</ul>
					</td>
					<td class="mainData">
<?php
	// 이슈태그 내용
	$index = 0;
	foreach($issueTags as $tag) {
		$index ++;
?>
						<ul id="_magazineFocus_<?php echo $index;?>_item" class="item _magazineFocus_item<?php echo ($index==1)?' viewed':'';?>">
<?php
	if(count($tag['feedItems'])>0) {
			$feedItem = current($tag['feedItems']);

			$thumbnailFile = '';
			if($media = Media::getMedia($feedItem['thumbnailId'])) {
				$thumbnailFile = Media::getMediaFile($media['thumbnail']);
			}			
			
			$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$feedItem['id'] : $service['path'].'/go/'.$feedItem['permalink'];

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
									<h3><a href="<?php echo $link_url;?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($feedItem['title']),60);?></a></h3>
									<div class="permalink">
										<a href="<?php echo $feedItem['permalink'];?>" target="_blank"><?php echo $feedItem['permalink'];?></a>
									</div>
									<div class="desc">
										<?php echo UTF8::lessenAsByte(func::stripHTML($feedItem['description']),140);?>
									</div>
								</div>

								<div class="clear"></div>
							</li>
<?php
			if(count($tag['feedItems'])>1) {
					for($i=1;$i<count($tag['feedItems']);$i++) {
						$tagItem = $tag['feedItems'][$i];

							$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$tagItem['id'] : $service['path'].'/go/'.$tagItem['permalink'];

?>
							<li class="title_only">
								<a href="<?php echo $link_url;?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($tagItem['title']),60);?></a> <span class="sep">|</span> <span class="feedTitle"><?php echo Feed::get($tagItem['feed'],'title');?></span>
							</li>
<?php			
					}
			} else {
?>				
							<li class="title_only"></li>
<?php
			}
	}
?>
						</ul>
<?php
}
?>
					</td>
					<td class="focusImageWrap">
<?php
	if(count($focusImages)) {
?>
					<div class="focusImageDataWrap">
						<div class="focusImageDatas">

						

							<div id="focusImageData">
<?php	
		$i = 1;		
		foreach($focusImages as $focusImage) {
								$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$focusImage['id'] : $service['path'].'/go/'.$focusImage['permalink'];

?>
								<div class="focusImage" style="top:<?php echo ($i-1)*160;?>px; background:url('<?php echo $service['path'];?>/cache/thumbnail/m_focus/<?php echo $focusImage['id'];?>.jpg') no-repeat top center;">
									<a href="<?php echo $link_url;?>" target="_blank"><img src="<?php echo $pluginURL;?>/images/empty.gif" alt="" /></a>
								</div>

								<div class="focusTitleBG" style="top:<?php echo ($i-1)*160 + 120;?>px;"></div>
								<div class="focusImageTitle" style="top:<?php echo ($i-1)*160 + 120;?>px;">	
									<a href="<?php echo $link_url;?>" target="_blank"><?php echo UTF8::lessenAsByte($focusImage['title'],60);?></a><br />
									<span class="blogtitle"><?php echo Feed::get($focusImage['feed'],'title');?></span>
								</div>
<?php
			$i ++;
		}

?>
							</div>		
							<div class="focusShadow">&nbsp;</div>

						</div>
						<div class="focusImageNav">
							<ul>
<?php
		$i = 1;
		foreach($focusImages as $focusImage) {
?>
								<li id="thumbnail<?php echo $i;?>">
									<div class="thumbnail">
										<a href="#" onmouseover="moveFocusImagePosition('thumbnail<?php echo $i;?>',-<?php echo ($i-1)*160;?>);" onclick="return false;"><img src="<?php echo $service['path'];?>/cache/thumbnail/m_focus/<?php echo $focusImage['id'];?>.jpg" alt="썸네일" /></a>
									</div>
									<div class="shadow">&nbsp;</div>
								</li>
<?php		
			$i ++;
		}
?>
							</ul>
						</div>
						<div class="clear"></div>
					</div> <!-- focusImageDataWrap close -->
					
<?php		
	} else {
?>
					<div class="focusImageEmpty">
						포커스로 지정된 글이 없습니다. 
					</div>
<?php	
	}
?>
					</td>
				</tr>
			</table>
		</div>
<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $input . $result;
	}
?>