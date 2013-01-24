<?php	
	$skin = new Skin;
	$skin->load('meta/'.$config->metaskin);

	$servicePath = func::lastSlashDelete($service['path']);

	// 베이스 출력
	$adminURL = (isLoggedIn() && !isAdmin()) ? $servicePath.'/mypage' : $service['path'].'/admin';

	$event->on('Meta.skinBegin');

	$headerScript = '
<script type="text/javascript">
//<![CDATA[
	[##_bloglounge_autoinput_script_##]
	$(window).ready( function() {
			updateFeed();
	});
//]]></script></head>';

	$skin->output = str_replace('</head>', func::printHeadHTML(), $skin->output);	
	$skin->output = str_replace('</head>', $headerScript, $skin->output);
	$skin->output = str_replace('</head>', $event->on('Disp.head')."\n</head>\n", $skin->output);
	$skin->output = str_replace('<body>', "\n<body>\n".$event->on('Disp.body'), $skin->output);


	// *** 기본 정보
	$skin->replace('title', $event->on('Text.title', UTF8::clear($config->title)));
	$skin->replace('description', $event->on('Text.description', UTF8::clear($config->description)));
	$skin->replace('base_url', empty($service['path'])?'/':$servicePath.'/');
	$skin->replace('index_url', $servicePath);
	$skin->replace('rss_url', $servicePath.'/rss');
	$skin->replace('focus_rss_url', $servicePath.'/rss/focus');

	$skin->replace('base_domain', $_SERVER['HTTP_HOST'].$servicePath);
	$skin->replace('bloglounge_name', BLOGLOUNGE);
	$skin->replace('bloglounge_version', BLOGLOUNGE_NAME);

	$skin->replace('random_blog', $service['path'].'/random');

	$skin->replace('position', empty($accessInfo['controller'])?'index':$accessInfo['controller']);

	// ** 갯수 정보
	$skin->replace('feed_count', Feed::getFeedCount());
	$skin->replace('feeditem_count', FeedItem::getFeedItemCount());
	$skin->replace('tag_count', Tag::getTagCount());

	// *** 로고
	$s_logo = $skin->cutSkinTag('logo_image');
	$s_logo = $skin->parseTag('logo_url', $servicePath.'/cache/logo/'.$config->logo, $s_logo);
	$skin->dress('logo_image', $s_logo);

	if(!empty($config->logo) && file_exists(ROOT.'/cache/logo/'.$config->logo)) {
		$skin->replace('logo_image_exist', 'logo_image_exist');
	} else {
		$skin->replace('logo_image_exist', 'logo_image_nonexistence');
	}
	
	$s_logo = $skin->cutSkinTag('logo_text');
	$skin->dress('logo_text', $s_logo);

	// *** 회원 메뉴 영역
	if (!isLoggedIn()) { // 로그인 되어있지 않은 비회원(손님)
		$s_guest = $skin->cutSkinTag('guest');
		$s_guest = $skin->parseTag('join_onclick', 'javascript:return join(this,\'' ._t("회원 가입페이지로 이동하시겠습니까?").'\');', $s_guest);
		$s_guest = $skin->parseTag('join_url', $servicePath.'/join', $s_guest);
		$s_guest = $skin->parseTag('login_onclick', 'javascript:return login(this,\'' ._t("로그인 페이지로 이동하시겠습니까?").'\');', $s_guest);
		$s_guest = $skin->parseTag('login_url', $servicePath.'/login', $s_guest);

		$skin->dress('guest', $s_guest);
	} else { // 로그인 되어있는 회원
		$s_member = $skin->cutSkinTag('member');
		$s_member = $skin->parseTag('logout_url', $servicePath.'/logout', $s_member);		
		$s_member = $skin->parseTag('logout_onclick', 'javascript:return logout(this,\'' ._t("로그아웃 하시겠습니까?").'\');', $s_member);

		$s_member = $skin->parseTag('member_name', htmlspecialchars(User::get($session['id'], 'name')), $s_member);
		$s_member = $skin->parseTag('member_welcome', msg::makeWelcomeMsg($config->welcomePack), $s_member);
		
		$s_member = $skin->parseTag('admin_url', $servicePath.'/admin', $s_member);		

		// 관리자
		if (!isAdmin()) { 		
			$s_admin = $skin->cutSkinTag('admin');
			$s_admin = $skin->parseTag('admin_url', $servicePath.'/admin', $s_admin);
			$s_member = $skin->dressTag('admin', $s_admin, $s_member);
		} else {
		}

		$skin->dress('member', $s_member);
	}

	// 카테고리 ( 분류 )

	$src_category = $skin->cutSkinTag('category');
	$categories = Category::getList();
	if(count($categories) > 0) {
		$sp_category = "<ul>\n";
			foreach($categories as $category) {
				$sp_category .= "<li><a href=\"{$servicePath}/category/".func::encode($category['name'])."\">{$category['name']}</a><span class=\"count count_class_{$category['count']}\">({$category['count']})</span>\n";
			}
		$sp_category .= "</ul>\n";

		$s_category = $skin->parseTag('category_list',$sp_category, $src_category);
	} else {
		$s_category = '';
	}
	$skin->dress('category', $s_category);


	// 그룹

	requireComponent('Bloglounge.Data.Groups');	

	$src_group = $skin->cutSkinTag('group');
	$groups = Group::getList();
	if(count($groups) > 0) {
		$sp_group = "<ul>\n";
			foreach($groups as $group) {
				$sp_group .= "<li><a href=\"{$servicePath}/group/".func::encode($group['name'])."\">{$group['name']}</a><span class=\"count count_class_{$group['count']}\">({$group['count']})</span>\n";
			}
		$sp_group .= "</ul>\n";

		$s_group = $skin->parseTag('group_list',$sp_group, $src_group);
	} else {
		$s_group = '';
	}
	$skin->dress('group', $s_group);

	// ** 피드 목록
	$src_feeds = $skin->cutSkinTag('feed');
	$result = Feed::getRecentFeeds($skinConfig->feedList, $skinConfig->feedOrder);
	if(count($result) > 0) {
		$s_feed_rep = '';
		$src_feed_rep = $skin->cutSkinTag('feed_rep');
		foreach($result as $item) {
			$src_thumbnail = $skin->cutSkinTag('cond_feed_logo');
			
			$thumbnailFile = (file_exists(ROOT . '/cache/feedlogo/' . $item['logo'])) ? $accessInfo['path'] . '/cache/feedlogo/' . $item['logo']:'';

			if(!empty($thumbnailFile)) {
				$s_thumbnail = (!Validator::is_empty($thumbnailFile)) ? $skin->parseTag('feed_logo', $thumbnailFile, $src_thumbnail) : '';
				$sp_feed = $skin->dressOn('cond_feed_logo', $src_thumbnail, $s_thumbnail, $src_feed_rep);		
				$sp_feed = $skin->parseTag('feed_logo_exist', 'feed_logo_exist', $sp_feed);
			} else {
				$sp_feed = $skin->dressOn('cond_feed_logo', $src_thumbnail, '', $src_feed_rep);
				$sp_feed = $skin->parseTag('feed_logo_exist', 'feed_logo_exist_nonexistence', $sp_feed);
			}

			$sp_feed = $skin->parseTag('feed_blogURL', htmlspecialchars($item['blogURL']), $sp_feed);
			$sp_feed = $skin->parseTag('feed_link_url', $servicePath.'/blog/'.$item['id'], $sp_feed);
			$sp_feed = $skin->parseTag('feed_title', UTF8::clear(UTF8::lessenAsByte($item['title'],$skinConfig->feedTitleLength)), $sp_feed);
			$s_feed_rep .= $sp_feed;
			$sp_feed = '';
		}	
		$s_feeds = $skin->dressOn('feed_rep', $src_feed_rep, $s_feed_rep, $src_feeds);
	} else {
		$s_feeds = '';
	}
	$skin->dress('feed', $s_feeds);
	$skin->replace('feedlist_url', $servicePath.'/feedlist/');	

	// 포커스 : ncloud	
	
	requireComponent('LZ.PHP.Media');

	$src_focuses = $skin->cutSkinTag('focus');
	$result = FeedItem::getRecentFocusFeedItems($skinConfig->focusList);
	if(count($result) > 0) {	
		$s_focus_rep = '';
		$src_focus_rep = $skin->cutSkinTag('focus_rep');

		foreach($result as $item) {	
			$item['thumbnail'] = '';
			if($media = Media::getMedia($item['thumbnailId'])) {
				$item['thumbnail'] = $media['thumbnail'];	
			}
			$src_thumbnail = $skin->cutSkinTag('cond_focus_thumbnail');
			$thumbnailFile = Media::getMediaFile($item['thumbnail']);

			if(!empty($thumbnailFile)) {
				$s_thumbnail = (!Validator::is_empty($thumbnailFile)) ? $skin->parseTag('focus_thumbnail', $thumbnailFile, $src_thumbnail) : '';
				$sp_focus = $skin->dressOn('cond_focus_thumbnail', $src_thumbnail, $s_thumbnail, $src_focus_rep);		
				$sp_focus = $skin->parseTag('focus_thumbnail_exist', 'focus_thumbnail_exist', $sp_focus);
			} else {
				$sp_focus = $skin->dressOn('cond_focus_thumbnail', $src_thumbnail, '', $src_focus_rep);
				$sp_focus = $skin->parseTag('focus_thumbnail_exist', 'focus_thumbnail_nonexistence', $sp_focus);
			}
			
			$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$item['id'] : $service['path'].'/go/'.$item['permalink'];

			$sp_focus = $skin->parseTag('focus_url', htmlspecialchars($item['permalink']), $sp_focus);
			$sp_focus = $skin->parseTag('focus_link_url', $link_url, $sp_focus);
			$sp_focus = $skin->parseTag('focus_title', UTF8::clear(UTF8::lessenAsByte(func::stripHTML($item['title']),$skinConfig->focusTitleLength)), $sp_focus);		

			$sp_focus = $skin->parseTag('focus_description', UTF8::clear(UTF8::lessenAsByte(func::htmltrim(func::stripHTML($item['description'])),$skinConfig->focusDescLength)), $sp_focus);
			$sp_focus = $skin->parseTag('focus_author', UTF8::clear($item['author']), $sp_focus);
			$sp_focus = $skin->parseTag('focus_date', (Validator::is_digit($item['written']) ? date('Y-m-d', $item['written']) : $item['written']), $sp_focus);

			$s_focus_rep .= $sp_focus;
			$sp_focus = '';
		}			
		$s_focuses = $skin->dressOn('focus_rep', $src_focus_rep, $s_focus_rep, $src_focuses);
	} else {
		$s_focuses = '';
	}
	$skin->dress('focus', $s_focuses);
	$skin->replace('focuslist_url', $servicePath.'/focus/');

	// 인기글
	$src_booms = $skin->cutSkinTag('boom');

	$result = FeedItem::getTopFeedItemsByLastest($skinConfig->boomList,$config->rankBy);
	if(count($result) > 0) {	

		$s_booms_rep = '';
		$src_booms_rep = $skin->cutSkinTag('boom_rep');

		foreach($result as $item) {		
			$item['thumbnail'] = '';
			if($media = Media::getMedia($item['thumbnailId'])) {
				$item['thumbnail'] = $media['thumbnail'];	
			}
			$src_thumbnail = $skin->cutSkinTag('cond_boom_thumbnail');
			$thumbnailFile = Media::getMediaFile($item['thumbnail']);

			if(!empty($thumbnailFile)) {
				$s_thumbnail = (!Validator::is_empty($thumbnailFile)) ? $skin->parseTag('boom_thumbnail', $thumbnailFile, $src_thumbnail) : '';
				$sp_booms = $skin->dressOn('cond_boom_thumbnail', $src_thumbnail, $s_thumbnail, $src_booms_rep);		
				$sp_booms = $skin->parseTag('boom_thumbnail_exist', 'boom_thumbnail_exist', $sp_booms);
			} else {
				$sp_booms = $skin->dressOn('cond_boom_thumbnail', $src_thumbnail, '', $src_booms_rep);
				$sp_booms = $skin->parseTag('boom_thumbnail_exist', 'boom_thumbnail_nonexistence', $sp_booms);
			}

			$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$item['id'] : $service['path'].'/go/'.$item['permalink'];

			$sp_booms = $skin->parseTag('boom_url', htmlspecialchars($item['permalink']), $sp_booms);		
			$sp_booms = $skin->parseTag('boom_link_url', $link_url , $sp_booms);
			$sp_booms = $skin->parseTag('boom_title', UTF8::clear(UTF8::lessenAsByte(func::stripHTML($item['title']), $skinConfig->boomTitleLength)), $sp_booms);

			$sp_booms = $skin->parseTag('boom_description', UTF8::clear(UTF8::lessenAsByte(func::htmltrim(func::stripHTML($item['description'])),$skinConfig->boomDescLength)), $sp_booms);
			$sp_booms = $skin->parseTag('boom_author', UTF8::clear($item['author']), $sp_booms);
			$sp_booms = $skin->parseTag('boom_date', (Validator::is_digit($item['written']) ? date('Y-m-d', $item['written']) : $item['written']), $sp_booms);

			$s_booms_rep .= $sp_booms;
			$sp_booms = '';
		}		
		$s_booms = $skin->dressOn('boom_rep', $src_booms_rep, $s_booms_rep, $src_booms);
	} else {
		$s_booms = '';
	}
	$skin->dress('boom', $s_booms);

	// ** 태그 클라우드
	$skin->dress('tagcloud', SkinElement::getTagCloud($skinConfig->tagCloudOrder, $skinConfig->tagCloudLimit));

	// ** 달력
	$skin->replace('calendar', SkinElement::getCalendarView((($searchType=='archive')&&!empty($searchKeyword)) ? substr($searchKeyword, 0, 6) : null));

	// ** 검색
	$searchTypeSelector = '<select name="type"><option value="all">'._t('전체').'</option><option value="tag"'.(($searchType=='tag')?' selected="selected"':'').'>'._t('태그').'</option><option value="blogURL"'.(($searchType=='blogURL')?' selected="selected"':'').'>'._t('블로그주소').'</option><option value="archive"'.(($searchType=='archive')?' selected="selected"':'').'>'._t('날짜지정').'</option></select>';
	$skin->replace('search_typeselect', $searchTypeSelector);
	$skin->replace('search_keyword', $searchKeyword);
	$src_search = $skin->cutSkinTag('search');

	$s_search = '<form action="'.$servicePath.'/" enctype="application/x-www-form-urlencoded" method="get">'.$src_search.'</form>';
	$skin->dress('search', $s_search);

	$skin->output = $skin->parseTagWithCondition('search_keyword', Korean::doesHaveFinalConsonant(UTF8::bring($searchKeyword)), '<span class="searchKeyword">"'.$searchKeyword.'"</span>', $skin->output);
	if(Validator::is_empty($searchKeyword)) $skin->dress('cond_search', '');			

?>