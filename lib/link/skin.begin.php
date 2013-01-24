<?php	
	$headerScript = '
<script type="text/javascript">
//<![CDATA[
//]]></script></head>';

	$skin->output = str_replace('</head>', func::printLinkerHeadHTML(), $skin->output);	
	$skin->output = str_replace('</head>', $headerScript, $skin->output);
	$skin->output = str_replace('</head>', $event->on('Disp.linker.head')."\n</head>\n", $skin->output);
	$skin->output = str_replace('<body>', "\n<body>\n".$event->on('Disp.linker.body'), $skin->output);

	// 기본
	$skin->replace('bloglounge_name', BLOGLOUNGE);
	$skin->replace('bloglounge_version', BLOGLOUNGE_NAME);
	
	$skin->replace('meta_url', empty($service['path'])?'/':$service['path']);
	$skin->replace('meta_title', $event->on('Text.linker.meta_title', UTF8::clear($config->title)));
	$skin->replace('meta_description', $event->on('Text.linker.meta_description', UTF8::clear($config->description)));

	// *** 회원 메뉴 영역
	if (!isLoggedIn()) { // 로그인 되어있지 않은 비회원(손님)
		$s_guest = $skin->cutSkinTag('guest');
		$s_guest = $skin->parseTag('join_onclick', 'javascript: return join(this,\'' ._t("회원 가입페이지로 이동하시겠습니까?").'\');', $s_guest);
		$s_guest = $skin->parseTag('join_url', $service['path'].'/join/', $s_guest);
		$s_guest = $skin->parseTag('login_onclick', 'javascript: return  login(this,\'' ._t("로그인 페이지로 이동하시겠습니까?").'\');', $s_guest);
		$s_guest = $skin->parseTag('login_url', $service['path'].'/login/', $s_guest);

		$skin->dress('guest', $s_guest);
	} else { // 로그인 되어있는 회원
		$s_member = $skin->cutSkinTag('member');
		$s_member = $skin->parseTag('mypage_url', $service['path'].'/mypage/', $s_member);
		$s_member = $skin->parseTag('logout_url', $service['path'].'/logout/', $s_member);
		$s_member = $skin->parseTag('logout_onclick', 'javascript: return  logout(this,\'' ._t("로그아웃 하시겠습니까?").'\');', $s_member);

		$s_member = $skin->parseTag('member_name', htmlspecialchars(User::get($session['id'], 'name')), $s_member);
		$s_member = $skin->parseTag('member_welcome', msg::makeWelcomeMsg($config->welcomePack), $s_member);
		
		// 관리자
		if (!isAdmin()) { 
			$s_member = $skin->dressTag('admin', '', $s_member);
		} else {
			$s_admin = $skin->cutSkinTag('admin');
			$s_admin = $skin->parseTag('admin_url', $service['path'].'/admin/', $s_admin);
			$s_member = $skin->dressTag('admin', $s_admin, $s_member);
		}
		$skin->dress('member', $s_member);
	}
?>