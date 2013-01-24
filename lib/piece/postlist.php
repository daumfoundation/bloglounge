<?php	
	$src_posts = $skin->cutSkinTag('postlist');
	if(isset($posts)) {
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
					window.location = "/admin";
				break;
				case 65: //A	
<?php
		if($accessInfo['page'] > 1) {
?>
					window.location = "<?php echo $accessInfo['fullpath'];?>/?page=<?php echo $accessInfo['page']-1;?>";
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
					window.location = "<?php echo $accessInfo['fullpath'];?>/?page=<?php echo $accessInfo['page']+1;?>";
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

			requireComponent('LZ.PHP.Media');

			$s_posts_rep = '';
			$src_post_rep = $skin->cutSkinTag('post_rep');

			if (count($posts)>0) {
				$index = 0;
				foreach($posts as $item) {
					$index ++;
					$item = $event->on('Data.post', $item);

					$item['thumbnail'] = '';
					if($media = Media::getMedia($item['thumbnailId'])) {
						$item['thumbnail'] = $media['thumbnail'];	
					}
					$src_thumbnail = $skin->cutSkinTag('cond_thumbnail');
					$thumbnailFile = Media::getMediaFile($item['thumbnail']);

					if(!empty($thumbnailFile)) {
						$s_thumbnail = (!Validator::is_empty($thumbnailFile)) ? $skin->parseTag('post_thumbnail', $thumbnailFile, $src_thumbnail) : '';
						$sp_posts = $skin->dressOn('cond_thumbnail', $src_thumbnail, $s_thumbnail, $src_post_rep);		
						$sp_posts = $skin->parseTag('post_thumbnail_exist', 'post_thumbnail_exist', $sp_posts);
					} else {
						$sp_posts = $skin->dressOn('cond_thumbnail', $src_thumbnail, '', $src_post_rep);
						$sp_posts = $skin->parseTag('post_thumbnail_exist', 'post_thumbnail_nonexistence', $sp_posts);
					}

					$sp_posts = $skin->parseTag('post_position', ($index==0?'firstItem':($index==count($posts)?'lastItem':'')), $sp_posts);

					$sp_posts = $skin->parseTag('post_id', $item['id'], $sp_posts);

					$sp_posts = $skin->parseTag('post_url',  $service['path'].'/go/'.$item['id'], $sp_posts);			
					$sp_posts = $skin->parseTag('post_permalink',  htmlspecialchars($item['permalink']), $sp_posts);
					
					$sp_posts = $skin->parseTag('post_visibility', ($item['visibility'] == 'n' ? 'hidden' : 'visible' ), $sp_posts);

					$sp_posts = $skin->parseTag('post_title', UTF8::clear($event->on('Text.postTitle', UTF8::lessen(func::stripHTML($item['title']), $skinConfig->postTitleLength))), $sp_posts);
					$sp_posts = $skin->parseTag('post_author', UTF8::clear($event->on('Text.postAuthor',$item['author'])), $sp_posts);

					list($post_category) = explode(',', UTF8::clear($item['tags']), 2);
					$sp_posts = $skin->parseTag('post_category', $post_category, $sp_posts);
					$sp_posts = $skin->parseTag('post_date', $event->on('Text.postDate',(Validator::is_digit($item['written']) ? date('Y-m-d h:i a', $item['written']) : $item['written'])), $sp_posts);
					$sp_posts = $skin->parseTag('post_view', $item['click'], $sp_posts);

					$item['description'] = func::stripHTML($item['description'].'>');
					if (substr($item['description'], -1) == '>') $item['description'] = substr($item['description'], 0, strlen($item['description']) - 1);
					$post_description = UTF8::lessenAsByte($item['description'], $skinConfig->postDescLength);
					if (strlen(trim($post_description)) == 0) $post_description = '<span class="empty">'._t('(글의 앞부분이 이미지 혹은 HTML 태그만으로 되어있습니다)').'</span>';

					$sp_posts = $skin->parseTag('post_description_slashed', addslashes($post_description), $sp_posts);
					$sp_posts = $skin->parseTag('post_description', $event->on('Text.postDescription', $post_description), $sp_posts);
					$sp_posts = $skin->parseTag('post_blogname', UTF8::clear(Feed::get($item['feed'], 'title')), $sp_posts);
					$sp_posts = $skin->parseTag('post_blogurl', htmlspecialchars(Feed::get($item['feed'], 'blogURL')), $sp_posts);
					//$sp_posts = $skin->parseTag('post_blogurl_search', htmlspecialchars('?blogURL='.Func::lastSlashDelete(str_replace('http://', '', Feed::get($item['feed'], 'blogURL')))), $sp_posts);
					$sp_posts = $skin->parseTag('post_bloglink', $service['path'].'/blog/'.Feed::get($item['feed'], 'id') , $sp_posts);


					$src_new = $skin->cutSkinTag('cond_new');
					$s_new = ($item['written'] > (time()-($skinConfig->postNewLife * 3600))) ? $skin->parseTag('post_newhours', $skinConfig->postNewLife, $src_new) : '';
					$sp_posts = $skin->dressOn('cond_new', $src_new, $s_new, $sp_posts);
					
					$sp_posts = $skin->parseTag('boom_rank', Boom::getRank($item['id']), $sp_posts);	
					$sp_posts = $skin->parseTag('boom_rank_id', 'boomRank'.$item['id'], $sp_posts);
					$sp_posts = $skin->parseTag('boom_rank_class', 'boom_rank_'.Boom::getRank($item['id']), $sp_posts);
					$sp_posts = $skin->parseTag('boomup_count', $item['boomUp'], $sp_posts);		
					$sp_posts = $skin->parseTag('boomdown_count', $item['boomDown'], $sp_posts);		

					$sp_posts = $skin->parseTag('boomup_onclick', 'boom(\''.$item['id'].'\',\'up\');', $sp_posts);
					$sp_posts = $skin->parseTag('boomdown_onclick', 'boom(\''.$item['id'].'\',\'down\');', $sp_posts);

					$sp_posts = $skin->parseTag('boomup_id', 'boomUp'.$item['id'], $sp_posts);
					$sp_posts = $skin->parseTag('boomdown_id', 'boomDown'.$item['id'], $sp_posts);

					$boomedUp = Boom::isBoomedUp($item['id']);
					$boomedDown = Boom::isBoomedDown($item['id']);
					
					$userid = $session['id'];
					$ip = $_SERVER['REMOTE_ADDR'];

					if (isLoggedIn()) {
						$boomedUp = Boom::isBoomedUp($item['id'], 'userid', $userid);	
						$boomedDown = Boom::isBoomedDown($item['id'], 'userid', $userid);
					} else {
						$boomedUp = Boom::isBoomedUp($item['id'], 'ip', $ip);	
						$boomedDown = Boom::isBoomedDown($item['id'], 'ip', $ip);
					}			

					$sp_posts = $skin->parseTag('boomup_class', (($boomedUp)?'isBoomedUp':'isntBoomedUp'), $sp_posts);
					$sp_posts = $skin->parseTag('boomdown_class', (($boomedDown)?'isBoomedDown':'isntBoomedDown'), $sp_posts);

					$tags = $event->on('Data.postTags', func::array_trim(explode(',', $item['tags'])));
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
						$sp_posts = $skin->dressOn('tags_rep', $src_tags, $s_tags, $sp_posts);
						$sp_posts = str_replace('<s_tags>', '', $sp_posts);
						$sp_posts = str_replace('</s_tags>', '', $sp_posts);
					} else {
						if ($skin->doesScopeExists('tags'))
							$sp_posts = $skin->dressOn('tags', $skin->cutSkinTag('tags'), '', $sp_posts);
					}

					$s_posts_rep .= $event->on('Text.post', $sp_posts);
					$sp_posts = '';
				}
			} else { // if query failed (or no article)
				$s_posts_rep = '';	
				$src_posts = '';
			}

			$src_posts = $skin->dressOn('post_rep', $src_post_rep, $s_posts_rep, $src_posts);

	} else {			
		$src_posts = '';
	}		

	$skin->dress('postlist', $src_posts);

?>