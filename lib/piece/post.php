<?php	
	if($post) {
		$src_post = $skin->cutSkinTag('post');
		$sp_post = '';
		if($src_post) {
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
					window.location = "<?php echo $service['path'];?>/admin";
				break;
				case 65: //A	
<?php
		if($paging['pagePrev'] < $paging['page']) {
?>
					window.location = "<?php echo $service['path'].$paging['pageDatas'][$paging['pagePrev']];?>";
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
		if($paging['page'] < $paging['totalPages']) {
?>
					window.location = "<?php echo $service['path'].$paging['pageDatas'][$paging['pageNext']];?>";
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

			$post = $event->on('Data.post', $post);

			$src_logo = $skin->cutSkinTag('cond_logo');
			$logoFile = Feed::get($post['feed'], 'logo');
			$logoFile = $event->on('Text.postLogo', (!file_exists(ROOT . '/cache/feedlogo/' . $logoFile) || empty($logoFile)) ? '' : $service['path']. '/cache/feedlogo/'.$logoFile);
		
			if(!empty($logoFile)) {
				$s_logo = (!Validator::is_empty($logoFile)) ? $skin->parseTag('post_logo', $logoFile, $src_logo) : '';
				$sp_post = $skin->dressOn('cond_logo', $src_logo, $s_logo, $src_post);		
				$sp_post = $skin->parseTag('post_logo_exist', 'post_logo_exist', $sp_post);
			} else {
				$sp_post = $skin->dressOn('cond_logo', $src_logo, '', $src_post);
				$sp_post = $skin->parseTag('post_logo_exist', 'post_logo_nonexistence', $sp_post);
			}

			$sp_post = $skin->parseTag('post_id', $post['id'], $sp_post);
			
			$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$post['id'] : $service['path'].'/go/'.htmlspecialchars($post['permalink']);
			$sp_post = $skin->parseTag('post_url',  $event->on('Text.postURL',$link_url), $sp_post);	
			$sp_post = $skin->parseTag('post_permalink',  htmlspecialchars($post['permalink']), $sp_post);

			$sp_post = $skin->parseTag('post_visibility', (($post['visibility'] == 'n' || $post['feedVisibility'] == 'n') ? 'hidden' : 'visible' ), $sp_post);

			$sp_post = $skin->parseTag('post_title', UTF8::clear($event->on('Text.postTitle', func::stripHTML($post['title']))), $sp_post);
			$sp_post = $skin->parseTag('post_author', UTF8::clear($event->on('Text.postAuthor',$post['author'])), $sp_post);

			list($post_category) = explode(',', UTF8::clear($post['tags']), 2);
			$sp_post = $skin->parseTag('post_category', $post_category, $sp_post);
			$sp_post = $skin->parseTag('post_date', $event->on('Text.postDate',(Validator::is_digit($post['written']) ? date('Y-m-d h:i a', $post['written']) : $post['written'])), $sp_post);
			$sp_post = $skin->parseTag('post_view', $post['click'], $sp_post);

			$post_description = $event->on('Text.postDescription', $post['description']);
			$post_description = str_replace('/cache/images/',$service['path'] . '/cache/images/', $post_description);

			$sp_post = $skin->parseTag('post_description', $post_description, $sp_post);
			$sp_post = $skin->parseTag('post_blogname', UTF8::clear(Feed::get($post['feed'], 'title')), $sp_post);
			$sp_post = $skin->parseTag('post_blogurl', htmlspecialchars(Feed::get($post['feed'], 'blogURL')), $sp_post);
			$sp_post = $skin->parseTag('post_bloglink', $service['path'].'/blog/'.Feed::get($post['feed'], 'id') , $sp_post);
			
			$sp_post = $skin->parseTag('boom_rank', Boom::getRank($post['id']), $sp_post);	
			$sp_post = $skin->parseTag('boom_rank_id', 'boomRank'.$post['id'], $sp_post);
			$sp_post = $skin->parseTag('boom_rank_class', 'boom_rank_'.Boom::getRank($post['id']), $sp_post);
			$sp_post = $skin->parseTag('boomup_count', $post['boomUp'], $sp_post);		
			$sp_post = $skin->parseTag('boomdown_count', $post['boomDown'], $sp_post);

			$sp_post = $skin->parseTag('boom_count_id', 'boomCount'.$post['id'], $sp_post);
			$sp_post = $skin->parseTag('boom_count', $post['boomUp'] - $post['boomDown'], $sp_post);
			$sp_post = $skin->parseTag('boom_count_class', 'boomCount boomCount'.($post['boomUp'] - $post['boomDown']), $sp_post);

			$sp_post = $skin->parseTag('boomup_onclick', 'boom(\''.$post['id'].'\',\'up\');', $sp_post);
			$sp_post = $skin->parseTag('boomdown_onclick', 'boom(\''.$post['id'].'\',\'down\');', $sp_post);

			$sp_post = $skin->parseTag('boomup_id', 'boomUp'.$post['id'], $sp_post);
			$sp_post = $skin->parseTag('boomdown_id', 'boomDown'.$post['id'], $sp_post);

			$boomedUp = Boom::isBoomedUp($post['id']);
			$boomedDown = Boom::isBoomedDown($post['id']);
			
			$userid = $session['id'];
			$ip = $_SERVER['REMOTE_ADDR'];

			if (isLoggedIn()) {
				$boomedUp = Boom::isBoomedUp($post['id'], 'userid', $userid);	
				$boomedDown = Boom::isBoomedDown($post['id'], 'userid', $userid);
			} else {
				$boomedUp = Boom::isBoomedUp($post['id'], 'ip', $ip);	
				$boomedDown = Boom::isBoomedDown($post['id'], 'ip', $ip);
			}			

			$sp_post = $skin->parseTag('boomup_class', (($boomedUp)?'isBoomedUp':'isntBoomedUp'), $sp_post);
			$sp_post = $skin->parseTag('boomdown_class', (($boomedDown)?'isBoomedDown':'isntBoomedDown'), $sp_post);

			$tags = $event->on('Data.postTags', func::array_trim(explode(',', $post['tags'])));
			if (count($tags) > 1) {
				$s_tags = '';
				$src_tags = $skin->cutSkinTag('tags_rep');
				foreach ($tags as $tag) {
					if ($tag == $post_category) continue;
					$sp_tags = $skin->parseTag('tag_link', htmlspecialchars($service['path'].'/?tag='.urlencode(trim($tag))), $src_tags);
					$sp_tags = $skin->parseTag('tag_name', UTF8::clear(trim($tag)), $sp_tags);
					$s_tags .= $sp_tags;
					$sp_tags = '';
				}
				$sp_post = $skin->dressOn('tags_rep', $src_tags, $s_tags, $sp_post);
				$sp_post = str_replace('<s_tags>', '', $sp_post);
				$sp_post = str_replace('</s_tags>', '', $sp_post);
			} else {
				if ($skin->doesScopeExists('tags'))
					$sp_post = $skin->dressOn('tags', $skin->cutSkinTag('tags'), '', $sp_post);
			}

			$skin->dress('post',  $event->on('Text.post', $sp_post));
		} else {
			ob_start();
?>
			<link rel="stylesheet" type="text/css" href="<?php echo $service['path'];?>/style/admin_content_error.css" />
			<div class="admin_content_post_error">
				<div class="error">
					<span><?php echo _t('현재 사용하고 있는 스킨은 바로보기가 지원되지 않습니다.');?></span>
				</div>
				<div class="message">
					<h3><?php echo $post['title'];?></h3>
					<div class="url"><a href="<?php echo $post['permalink'];?>" target="_blank"><?php echo $post['permalink'];?></a></div>
				</div>
				<div class="buttons">
					<a href="#" onclick="history.back(1); return false;"><span>뒤로가기</span></a>
				</div>
			</div>
<?php
			$error = ob_get_contents();
			ob_end_clean();

			$skin->dress('content', $error);
		}
	}	
?>