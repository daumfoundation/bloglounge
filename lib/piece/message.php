<?php			
	$src_condMessage = $skin->cutSkinTag('cond_message');
	$condMessage = false;
	if(!isset($posts) || (count($posts)==0)) {		

		if (!Validator::is_empty($searchKeyword)) {
			$src_condSearchEmpty = $skin->cutSkinTag('cond_search_empty');	
			$src_condMessage = $skin->dressOn('cond_search_empty', $src_condSearchEmpty, $src_condSearchEmpty,$src_condMessage);
			$src_condMessage = $event->on('Text.searchEmpty', $src_condMessage);

			$condMessage = true;
		} else {
			$src_condSearchEmpty = $skin->cutSkinTag('cond_empty');	
			$src_condMessage = $skin->dressOn('cond_empty', $src_condSearchEmpty, $src_condSearchEmpty,$src_condMessage);
			$src_condMessage = $event->on('Text.empty', $src_condMessage);

			$condMessage = true;
		}
	} else {
		if (!Validator::is_empty($searchKeyword)) {
			if(!isset($totalFeedItems) || empty($totalFeedItems)) {
				$totalFeedItems = 0;
			}

			if($searchType == 'all') {
				$src_condSearchAll = $skin->cutSkinTag('cond_search_all');

				$s_condSearchAll = $skin->parseTag('search_all', $searchKeyword, $src_condSearchAll);
				$s_condSearchAll = $skin->parseTag('search_count', $totalFeedItems, $s_condSearchAll);

				$src_condMessage = $skin->dressOn('cond_search_all', $src_condSearchAll, $s_condSearchAll, $src_condMessage);
				$src_condMessage = $event->on('Text.searchAll', $src_condMessage);
				$condMessage = true;
			}
			else if ($searchType=='tag') {			// 태그검색
				$src_condSearchTag = $skin->cutSkinTag('cond_search_tag');	
				
				$s_condSearchTag = $skin->parseTag('search_tag', $searchKeyword, $src_condSearchTag);
				$s_condSearchTag = $skin->parseTag('search_count', $totalFeedItems, $s_condSearchTag);
				$src_condMessage = $skin->dressOn('cond_search_tag', $src_condSearchTag, $s_condSearchTag, $src_condMessage);
				$src_condMessage = $event->on('Text.searchTag', $src_condMessage);
				$condMessage = true;

			} else if ($searchType=='blogURL') { // 블로그주소
				
				$src_condSearchBlogURL = $skin->cutSkinTag('cond_search_blogurl');
				$searchblog = array();
				list($searchblog['title'], $searchblog['description'], $searchblog['logo'], $searchblog['feedCount']) = Feed::gets($searchExtraValue, 'title, description, logo, feedCount');
				$sp_blogurl = $skin->parseTag('search_blogurl', $searchKeyword, $src_condSearchBlogURL);
				$sp_blogurl = $skin->parseTag('search_blogtitle', $searchblog['title'], $sp_blogurl);
				$sp_blogurl = $skin->parseTag('search_blogdescription', $searchblog['description'], $sp_blogurl);
				
				$src_sblogo = $skin->cutSkinTag('cond_search_bloglogo');
				if (!empty($searchblog['logo'])) {
					$sp_blogurl = $skin->parseTag('search_bloglogo', $service['path'].'/cache/feedlogo/'.$searchblog['logo'], $sp_blogurl);
					$sp_blogurl = str_replace('<s_cond_search_bloglogo>', '', $sp_blogurl);
					$sp_blogurl = str_replace('</s_cond_search_bloglogo>', '', $sp_blogurl);
				} else {
					$sp_blogurl = $skin->dressOn('cond_search_bloglogo', $src_sblogo, '', $sp_blogurl);
				}
				$sp_blogurl = $skin->parseTag('search_blogcount', $searchblog['feedCount'], $sp_blogurl);
				$src_condMessage = $skin->dressOn('cond_search_blogurl', $src_condSearchBlogURL, $sp_blogurl, $src_condMessage);
				$src_condMessage = $event->on('Text.searchBlogURL', $src_condMessage);

				$condMessage = true;
			} else if ($searchType=='archive') { // date
				$src_condSearchDate = $skin->cutSkinTag('cond_search_date');			
				
				if(is_array($searchExtraValue)) {
					$sp_date = $skin->parseTagWithArgument('search_date', 'date', "#1,{$searchExtraValue['start']}/#2,{$searchExtraValue['end']}", $src_condSearchDate, 'Y-m-d');
				} else {
					$sp_date = $skin->parseTagWithArgument('search_date', 'date', "#1,$searchExtraValue", $src_condSearchDate, 'Y-m-d');
				}

				$sp_date = $skin->parseTag('search_count', $totalFeedItems, $sp_date);

				$src_condMessage = $skin->dressOn('cond_search_date', $src_condSearchDate, $sp_date, $src_condMessage);
				$src_condMessage = $event->on('Text.searchDate', $src_condMessage);

				$condMessage = true;
			} else if ($searchType=='category') {	// category
				$src_condSearchCategory = $skin->cutSkinTag('cond_search_category');	
				
				$s_condSearchCategory = $skin->parseTag('search_category', $searchKeyword, $src_condSearchCategory);
				$s_condSearchCategory = $skin->parseTag('search_count', $totalFeedItems, $s_condSearchCategory);
				$s_condSearchCategory = $skin->parseTag('search_rss', $servicePath.'/rss/category/'.$searchKeyword, $s_condSearchCategory);

				$src_condMessage = $skin->dressOn('cond_search_category', $src_condSearchCategory, $s_condSearchCategory, $src_condMessage);
				$src_condMessage = $event->on('Text.searchCategory', $src_condMessage);
				$condMessage = true;
			}else if ($searchType=='group') {
				$src_condSearchGroup = $skin->cutSkinTag('cond_search_group');	
				
				$s_condSearchGroup = $skin->parseTag('search_group', $searchKeyword, $src_condSearchGroup);
				$s_condSearchGroup = $skin->parseTag('search_count', $totalFeedItems, $s_condSearchGroup);
//				$s_condSearchGroup = $skin->parseTag('search_rss', $servicePath.'/rss/group/'.$searchKeyword, $s_condSearchGroup);

				$src_condMessage = $skin->dressOn('cond_search_group', $src_condSearchGroup, $s_condSearchGroup, $src_condMessage);
				$src_condMessage = $event->on('Text.searchGroup', $src_condMessage);
				$condMessage = true;
			}
		} else {
			if($accessInfo['controller'] == 'focus') {
				$src_condFocuslist = $skin->cutSkinTag('cond_focuslist');	
				$src_condMessage = $skin->dressOn('cond_focuslist', $src_condFocuslist, $src_condFocuslist,$src_condMessage);
				$src_condMessage = $event->on('Text.focuslist', $src_condMessage);
			} else {
				$src_condPostlist = $skin->cutSkinTag('cond_postlist');	
				$src_condMessage = $skin->dressOn('cond_postlist', $src_condPostlist, $src_condPostlist,$src_condMessage);
				$src_condMessage = $event->on('Text.postlist', $src_condMessage);
			}

			$condMessage = true;
		}
	}
	
	if($condMessage) {
		$skin->dress('cond_message', $src_condMessage);
	}
?>