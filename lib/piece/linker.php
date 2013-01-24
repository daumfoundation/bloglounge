<?php
	if(isset($linker_post)) {
		requireComponent('LZ.PHP.Media');

		$linker_post = $event->on('Data.linker.post', $linker_post);
		
		$skin->replace('title', $event->on('Text.linker.title', UTF8::clear($linker_post['title'])));
		$skin->replace('author', $event->on('Text.linker.author', UTF8::clear($linker_post['author'])));
		
		if(isset($linker_feed['xmlURL']) && !empty($linker_feed['xmlURL'])) {
			if($linker_feed['xmlType'] == 'rss') { // rss
				$src_rss = $skin->cutSkinTag('rss');	
					$s_rss = $skin->parseTag('rss_url', $linker_feed['xmlURL'], $src_rss);
				$skin->dress('rss', $s_rss);
			} else if($linker_feed['xmlType'] == 'atom') { // atom
				$src_atom = $skin->cutSkinTag('atom');	
					$s_atom = $skin->parseTag('atom_url', $linker_feed['xmlURL'], $src_atom);
				$skin->dress('atom', $s_atom);
			}
		}

		$linker_post['thumbnail'] = '';

		if($media = Media::getMedia($linker_post['thumbnailId'])) {
			$linker_post['thumbnail'] = $media['thumbnail'];	
		}

		$thumbnailFile = '';

		$src_thumbnail = $skin->cutSkinTag('cond_thumbnail');
		if ((substr($linker_post['thumbnail'], 0, 7) != 'http://')) {
			if (!is_file(ROOT . '/cache/thumbnail/' . $linker_post['thumbnail'])) { 
				$thumbnailFile = '';
			} else {
				$thumbnailFile = str_replace('/cache/thumbnail//', '/cache/thumbnail/', $service['path']. '/cache/thumbnail/'.$linker_post['thumbnail']);			
			}
		}

		if(!empty($thumbnailFile)) {
			$s_thumbnail = (!Validator::is_empty($thumbnailFile)) ? $skin->parseTag('thumbnail', $thumbnailFile, $src_thumbnail) : '';
			$skin->dress('cond_thumbnail', $s_thumbnail);		
			$skin->replace('thumbnail_exist', 'thumbnail_exist');
		} else {
			$skin->dress('cond_thumbnail', '');
			$skin->replace('thumbnail_exist', 'thumbnail_nonexistence');
		}

		$skin->replace('id', $linker_post['id']);		
		$skin->replace('permalink',  htmlspecialchars($linker_post['permalink']));
		
		$skin->replace('tags', $event->on('Text.linker.tags',UTF8::clear($linker_post['tags'])));

		$skin->replace('date', $event->on('Text.linker.date',(Validator::is_digit($linker_post['written']) ? date('Y-m-d h:i a', $linker_post['written']) : $linker_post['written'])));
		$skin->replace('view', $linker_post['click']);


		$linker_post['description'] = func::stripHTML($linker_post['description'].'>');
		if (substr($linker_post['description'], -1) == '>') $linker_post['description'] = substr($linker_post['description'], 0, strlen($linker_post['description']) - 1);
		$description = $linker_post['description'];
		if (strlen(trim($description)) == 0) $description = _t('(글의 앞부분이 이미지 혹은 HTML 태그만으로 되어있습니다)');

		$skin->replace('description_slashed', addslashes($post_description));
		$skin->replace('description', $event->on('Text.linker.description', $post_description));
		$skin->replace('blogname', UTF8::clear(Feed::get($linker_post['feed'], 'title')));
		$skin->replace('blogurl', htmlspecialchars(Feed::get($linker_post['feed'], 'blogURL')));
	
		$skin->replace('boom_rank', Boom::getRank($linker_post['id']));				
		$skin->replace('boom_rank_id', 'boomRank'.$linker_post['id']);
		$skin->replace('boomup_count', $linker_post['boomUp']);		
		$skin->replace('boomdown_count', $linker_post['boomDown']);		

		$skin->replace('boomup_onclick', 'javascript: boom(\''.$linker_post['id'].'\',\'up\');');
		$skin->replace('boomdown_onclick', 'javascript: boom(\''.$linker_post['id'].'\',\'down\');');

		$skin->replace('boomup_id', 'boomUp'.$linker_post['id']);
		$skin->replace('boomdown_id', 'boomDown'.$linker_post['id']);

		$boomedUp = Boom::isBoomedUp($linker_post['id']);
		$boomedDown = Boom::isBoomedDown($linker_post['id']);

		$skin->replace('boomup_class', (($boomedUp)?'isBoomedUp':'isntBoomedUp'));
		$skin->replace('boomdown_class', (($boomedDown)?'isBoomedDown':'isntBoomedDown'));

		$tags = $event->on('Data.linker.tags', func::array_trim(explode(',', $linker_post['tags'])));
		if (count($tags) > 1) {
			$s_tags = '';
			$src_tags = $skin->cutSkinTag('tags_rep');
			foreach ($tags as $tag) {
				if ($tag == $category) continue;
				$sp_tags = $skin->replace('tag_link', htmlspecialchars($service['path'].'/?tag='.urlencode(trim($tag))), $src_tags);
				$sp_tags = $skin->replace('tag_name', UTF8::clear($tag), $sp_tags);
				$s_tags .= $sp_tags;
				$sp_tags = '';
			}
			$skin->dress('tags_rep', $src_tags, $s_tags);
		} else {
		}
	}
?>