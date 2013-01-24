<?php	
	$src_posts = $skin->cutSkinTag('postlist');
	if(isset($posts)) {
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

			requireComponent('LZ.PHP.Media');

			$postCutCount = $skinConfig->postListDivision;
			$s_posts = '';
			$s_posts_rep = '';
			$src_post_rep = $skin->cutSkinTag('post_rep');

			if (count($posts)>0) {
				$index = 0;
				$lastIndex = round(count($posts) / $postCutCount);
				
				if(count($posts) % $postCutCount != 0) $lastIndex ++;

				if($skinConfig->postListDirection == 'horizontal') {
					

					$new_posts = array();
					
					for($start=0;$start<$postCutCount;$start++) {
						$i = $start;
						$to = $lastIndex;

						while($to-->0) {
							if(isset($posts[$i])) {
								array_push($new_posts, $posts[$i]);
							}
							$i += $postCutCount;
						}
						array_push($new_posts,'line_break');
					}

					unset($posts);
					$posts = $new_posts;
				}

				foreach($posts as $item) {
					if($item == 'line_break') {
						$s_posts .= $skin->dressOn('post_rep', $src_post_rep, $s_posts_rep, $src_posts);		
						$s_posts_rep = '';
						$index = 0;
						continue;;
					}

					$index ++;
					$item = $event->on('Data.post', $item);

					$item['thumbnail'] = '';
					if($media = Media::getMedia($item['thumbnailId'])) {
						$item['thumbnail'] = $media['thumbnail'];	
					}
					$src_thumbnail = $skin->cutSkinTag('cond_thumbnail');
					$thumbnailFile =  $event->on('Text.postThumbnail', Media::getMediaFile($item['thumbnail']));

					if(!empty($thumbnailFile)) {
						$s_thumbnail = (!Validator::is_empty($thumbnailFile)) ? $skin->parseTag('post_thumbnail', $thumbnailFile, $src_thumbnail) : '';
						$sp_posts = $skin->dressOn('cond_thumbnail', $src_thumbnail, $s_thumbnail, $src_post_rep);		
						$sp_posts = $skin->parseTag('post_thumbnail_exist', 'post_thumbnail_exist', $sp_posts);
					} else {
						$sp_posts = $skin->dressOn('cond_thumbnail', $src_thumbnail, '', $src_post_rep);
						$sp_posts = $skin->parseTag('post_thumbnail_exist', 'post_thumbnail_nonexistence', $sp_posts);
					}

					list($feedId, $feedOwner, $feedTitle,$feedBlogUrl, $logoFile) = Feed::gets($item['feed'],'id,owner,title,blogURL,logo');

					$src_logo = $skin->cutSkinTag('cond_logo');

					$logoFile = $event->on('Text.postLogo', (!file_exists(ROOT . '/cache/feedlogo/' . $logoFile) || empty($logoFile)) ? '' : $service['path']. '/cache/feedlogo/'.$logoFile);
				
					if(!empty($logoFile)) {
						$s_logo = (!Validator::is_empty($logoFile)) ? $skin->parseTag('post_logo', $logoFile, $src_logo) : '';
						$sp_posts = $skin->dressOn('cond_logo', $src_logo, $s_logo, $sp_posts);		
						$sp_posts = $skin->parseTag('post_logo_exist', 'post_logo_exist', $sp_posts);
					} else {
						$sp_posts = $skin->dressOn('cond_logo', $src_logo, '', $sp_posts);
						$sp_posts = $skin->parseTag('post_logo_exist', 'post_logo_nonexistence', $sp_posts);
					}

					$sp_posts = $skin->parseTag('post_position', ($index==1?'firstItem':($index==$lastIndex?'lastItem':'')), $sp_posts);

					$sp_posts = $skin->parseTag('post_id', $item['id'], $sp_posts);
					
					$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$item['id'] : $service['path'].'/go/'.htmlspecialchars($item['permalink']);
					$sp_posts = $skin->parseTag('post_url',  $event->on('Text.postURL',(Validator::getBool($config->directView)?$service['path'].'/read/'.$item['id']:$link_url)), $sp_posts);	
					$sp_posts = $skin->parseTag('post_link_target',  (Validator::getBool($config->directView)?'_self':'_blank'), $sp_posts);					
					$sp_posts = $skin->parseTag('post_permalink',  htmlspecialchars($item['permalink']), $sp_posts);

					$sp_posts = $skin->parseTag('post_visibility', (($item['visibility'] == 'n' || $item['feedVisibility'] == 'n') ? 'hidden' : 'visible' ), $sp_posts);

					$sp_posts = $skin->parseTag('post_title', UTF8::clear($event->on('Text.postTitle', UTF8::lessen(func::stripHTML($item['title']), $skinConfig->postTitleLength))), $sp_posts);
					$sp_posts = $skin->parseTag('post_author', UTF8::clear($event->on('Text.postAuthor',$item['author'])), $sp_posts);

					list($post_category) = explode(',', UTF8::clear($item['tags']), 2);
					$sp_posts = $skin->parseTag('post_category', $post_category, $sp_posts);
					$sp_posts = $skin->parseTag('post_date', $event->on('Text.postDate',(Validator::is_digit($item['written']) ? date('Y-m-d h:i a', $item['written']) : $item['written'])), $sp_posts);
					$sp_posts = $skin->parseTag('post_view', $item['click'], $sp_posts);

					$post_description = func::stripHTML($item['description'].'>');
					if (substr($post_description, -1) == '>') $post_description = substr($post_description, 0, strlen($post_description) - 1);
					$post_description = UTF8::lessenAsByte(func::htmltrim($post_description), $skinConfig->postDescLength);
					if (strlen($post_description) == 0) $post_description = '<span class="empty">'._t('(글의 앞부분이 이미지 혹은 HTML 태그만으로 되어있습니다)').'</span>';					

					$post_description = $event->on('Text.postDescription', $post_description);

					if(!empty($searchKeyword) && in_array($searchType,array('title','description','title+description'))) {
						$keyword_pattern = "/([^<]*)".str_replace("\0","\\0",preg_quote($searchKeyword,"/"))."([^>]*)/i";
						$post_description = preg_replace($keyword_pattern, "\\1<span class=\"point\">" . $searchKeyword . "</span>\\2", $post_description);
					}

					$sp_posts = $skin->parseTag('post_description_slashed', addslashes($post_description), $sp_posts);
					$sp_posts = $skin->parseTag('post_description', $post_description, $sp_posts);

					$post_description = str_replace('/cache/images/',$service['path'] . '/cache/images/', $item['description']);

					$sp_posts = $skin->parseTag('post_description_original', $post_description, $sp_posts);
					
					$sp_posts = $skin->parseTag('post_blogname', $event->on('Text.postBlogTitle',UTF8::clear($feedTitle)), $sp_posts);
					$sp_posts = $skin->parseTag('post_blogurl', htmlspecialchars($feedBlogURL), $sp_posts);	
					//$sp_posts = $skin->parseTag('post_blogurl_search', htmlspecialchars('?blogURL='.Func::lastSlashDelete(str_replace('http://', '', Feed::get($item['feed'], 'blogURL')))), $sp_posts);
					$sp_posts = $skin->parseTag('post_bloglink', $event->on('Text.postBlogLink',$service['path'].'/blog/'.$feedId) , $sp_posts);
					$sp_posts = $skin->parseTag('post_authorlink', $event->on('Text.postAuthorLink',$service['path'].'/author/'.$item['author']) , $sp_posts);
					$sp_posts = $skin->parseTag('post_userlink', $event->on('Text.postUserLink',$service['path'].'/user/'.User::get($feedOwner,'loginid')) , $sp_posts);

					$src_new = $skin->cutSkinTag('cond_new');
					$s_new = ($item['written'] > (time()-($skinConfig->postNewLife * 3600))) ? $skin->parseTag('post_newhours', $skinConfig->postNewLife, $src_new) : '';
					$sp_posts = $skin->dressOn('cond_new', $src_new, $s_new, $sp_posts);
					
					$sp_posts = $skin->parseTag('boom_rank', Boom::getRank($item['id']), $sp_posts);	
					$sp_posts = $skin->parseTag('boom_rank_id', 'boomRank'.$item['id'], $sp_posts);
					$sp_posts = $skin->parseTag('boom_rank_class', 'boom_rank_'.Boom::getRank($item['id']), $sp_posts);
					$sp_posts = $skin->parseTag('boomup_count', $item['boomUp'], $sp_posts);		
					$sp_posts = $skin->parseTag('boomdown_count', $item['boomDown'], $sp_posts);

					$sp_posts = $skin->parseTag('boom_count_id', 'boomCount'.$item['id'], $sp_posts);
					$sp_posts = $skin->parseTag('boom_count', $item['boomUp'] - $item['boomDown'], $sp_posts);
					$sp_posts = $skin->parseTag('boom_count_class', 'boomCount boomCount'.($item['boomUp'] - $item['boomDown']), $sp_posts);

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
							$sp_tags = $skin->parseTag('tag_link', $event->on('Text.postTagLink',htmlspecialchars($service['path'].'/?tag='.urlencode(trim($tag)))), $src_tags);
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
			
			if(!empty($s_posts_rep)) {
				$s_posts .= $skin->dressOn('post_rep', $src_post_rep, $s_posts_rep, $src_posts);
			}

	} else {			
		$s_posts = '';
	}		

	$skin->dress('postlist', $s_posts);

?>