<?php
 $action = $accessInfo['action'];
 if(empty($action)) $action = 'center'; 
 
 $userInformation = getUsers();
 $is_admin = isset($userInformation['is_admin'])?(($userInformation['is_admin']=='y')?true:false):false;

 $appMessage = readAppMessage();
 clearAppMessage();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGLOUNGE;?> :: <?php echo _t('관리페이지');?></title>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/box.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/modal.css" type="text/css" />
<link rel="shortcut icon" href="<?php echo $service['path'];?>/images/favicon.ico" />
<script type="text/javascript">
	var _path = '<?php echo $service['path'];?>';
	var _lang = '<?php echo Locale::get();?>';	
	
	var _nowid = 'menu_<?php echo $action;?>';
</script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/admin.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/modal.js"></script>

<script type="text/javascript">
/*
	function updateGlobalFeed(type) {
		 addMessage("<?php echo _t('블로그 업데이트를 시작합니다.');?>");
		 $.ajax({
		  type: "POST",
		  url: _path +'/service/update/?type=' + type,
		  success: function(msg){
			 error = $("response error", msg).text();
			 if(error != 1) {
				 addMessage("<?php echo _f('블로그 \""+%1+"\"의 업데이트를 완료했습니다.','$("response feed", msg).text()');?>");
			 } else {
				 addMessage($("response message", msg));
			 }
		  },
		  error: function(msg) {
		  }
		});
	};
*/
	function menuAction() {
		$("#header .leftmenus ul li").each( function() {
			$(this).mouseover( function() {
				$(this).addClass('hover');
			
			}).mouseout( function() {	
				$(this).removeClass('hover');
			});
		});

		$("#header .leftmenus ul li a").focus( function() {
			this.blur();
		});

		$("#header .menus ul li").each( function() {
			$(this).mouseover( function() {
				if(this.id != _nowid) {
					$(this).addClass('hover');
<?php
		if(!ADMIN_MENU_CLICK_VIEW) {
?>
					selectAction(this.id.substr(5)); // realtime mouse hover
<?php
		}
?>
				}
			}).mouseout( function() {	
				if(this.id != _nowid) {
					$(this).removeClass('hover');
				}
			});
		});

		$("#header .menus ul li a").focus( function() {
			this.blur();
		});
	}
	
	var lastMenu = null;
	function selectAction(action) {
		if("menu_"+action == _nowid) {
			return true;
		} else {
			$("#"+_nowid).removeClass('selected').removeClass('hover');
			$("#sub"+_nowid).removeClass('viewed')

			_nowid = "menu_" + action;
			$("#" + _nowid).addClass('selected');
			$("#sub" + _nowid).addClass('viewed');
			
			return false;
		}
	}

<?php
	if($is_admin) {
?>
	var blogIdList = [];
	var nowBlogUpdating = false;
	var nowBlogUpdatingCancel = false;

	function updateAll() {
		if(nowBlogUpdating) return;

		nowBlogUpdatingCancel = false;

		$("#updatingImg").show();
		$("#updateList").empty();
		$("#updateProgress").hide();
		$("#updateModalButton").text("<?php echo _t('로딩중');?>");

		makeModal("#updateModal", "", {overlayClickClose:false});
		fnModalCenter();

		$.ajax({
		  type: "POST",
		  url: _path +'/service/feed/list.php',
		  success: function(msg){
			blogIdList = $("response list", msg).text().split(",");
			
			$("#updateModalButton").text("<?php echo _t('업데이트 중지');?>");

			$("#updateProgressNow").text('1');
			$("#updateProgressMax").text(blogIdList.length);

			$("#updateProgress").show();

			nowBlogUpdating = true;
			updateAllWork(blogIdList[0], 0, updateAllWorkFinish);
		  },
		  error: function(msg) {
		  }
		});
	}

	function updateAllWork(id, index, onFinish) {
		if(nowBlogUpdatingCancel) return;

		$.ajax({
		  type: "POST",
		  url: _path +'/service/feed/update.php',
		  data: 'id=' + id,
		  dataType: 'xml',
		  success: function(msg){		
			if(typeof(onFinish) != 'undefined') {
				onFinish(index, $("response feed", msg).text(), $("response updated", msg).text());
			}
		  },
		  error: function(msg) {
		  }
		});
	}

	function updateAllWorkFinish(index, feed, updated) {	
		$("#updateProgressNow").text(index+1);

		$("#updateList").append('<li><?php echo _t('업데이트 완료');?> : ' + feed + ' <span class="cnt">(' + updated + ')</span></li>');
		$("#updateList").scrollTop(100000);

		if(index >= blogIdList.length-1) {
			nowBlogUpdating = false;
			$("#updatingImg").hide();		
			$("#updateModalButton").text("<?php echo _t('업데이트 완료');?>");
		} else if(!nowBlogUpdatingCancel) {
			updateAllWork(blogIdList[index+1], index+1, updateAllWorkFinish);
		}
	}

	function updateAllModalClose() {
		nowBlogUpdatingCancel = true;
	}

<?php
	}
?>

	$(window).ready( function() {
		menuAction();
<?php 
	if(!empty($appMessage)) {
?>
		addMessage('<?php echo $appMessage;?>');
<?php
}
?>
	});
</script>
</head>

<body>
	<div class="makeModal" id="updateModal">	
		<div class="modalTitle"><?php echo _t('블로그 업데이트');?></div>
		<div class="modalWrap">
			<div class="updateModalInput">
				<ul id="updateList">
				</ul>
				<div class="buttons">
					<a href="#" class="normalbutton boldbutton modalClose" onclick="updateAllModalClose();"><span id="updateModalButton" ><?php echo _t('업데이트 중지');?></span></a>
					<div id="updateProgress" class="progress">
						<img id="updatingImg" src="<?php echo $service['path'];?>/images/admin/ani_ajax_loading_small.gif" alt="loading.." align="absmiddle" />&nbsp;&nbsp;<span id="updateProgressNow">1</span> / <strong id="updateProgressMax">123</strong>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="header">
	<div class="wrap">
		<div class="leftmenus">
			<ul>
				<li id="leftmenu_blogadd"><span><span><a href="<?php echo $service['path'];?>/admin/blog/add"><?php echo _t('블로그추가');?></a></span></span></li>
			</ul>
		</div>

		<div class="menus">
			<ul>
				<li id="menu_center" <?php echo $action=='center'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin" onclick="return selectAction('center');"><?php echo _t('센터');?></a></span></span></li>
				<li id="menu_blog" <?php echo $action=='blog'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin/blog" onclick="return selectAction('blog');"><?php echo _t('블로그');?></a></span></span></li>
<?php
	if($is_admin) {
?>
				<li id="menu_design" <?php echo $action=='design'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin/design" onclick="return selectAction('design');"><?php echo _t('디자인');?></a></span></span></li>
				<li id="menu_plugin" <?php echo $action=='plugin'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin/plugin" onclick="return selectAction('plugin');"><?php echo _t('플러그인');?></a></span></span></li>
				<li id="menu_member" <?php echo $action=='member'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin/member" onclick="return selectAction('member');"><?php echo _t('회원');?></a></span></span></li>
				<li id="menu_setting" <?php echo $action=='setting'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin/setting" onclick="return selectAction('setting');"><?php echo _t('설정');?></a></span></span></li>
<?php
	} else {
?>				
				<li id="menu_user" <?php echo $action=='user'?' class="selected"':'';?>><span><span><a href="<?php echo $service['path'];?>/admin/user" onclick="return selectAction('user');"><?php echo _t('개인');?></a></span></span></li>
<?php
	}
?>
			</ul>
		</div>
		<div class="tools">
			<?php echo $userInformation['is_accepted']=='n'?'('._t('미인증회원').') ':'';?><?php echo _f("%1님 안녕하세요", htmlspecialchars($userInformation['name']));?> <span class="sep">|</span> <a href="<?php echo $service['path'];?>/"><?php echo _t("홈으로");?></a> <span class="sep">|</span> <a href="<?php echo $service['path'];?>/logout?returnURL=<?php echo $service['path'];?>/"><strong><?php echo _t("로그아웃");?></strong></a>
		</div>
		<div class="clear"></div>
	</div> <!-- wrap close -->
	</div> <!-- header close -->

	<div id="submenu">
	<div class="wrap">
<?php 
		$value = $accessInfo['value'];
		switch($action) {
			case 'center':
				if(empty($value)) $value = 'dashboard';
			break;		
			case 'blog':
				if(empty($value)) $value = 'bloglist';
				else if($value=='list') $value = 'bloglist';
			break;			
			case 'design':
				if(empty($value)) $value = 'meta';
			break;			
			case 'plugin':
				if(empty($value)) $value = 'pluginlist';
				else if($value=='list') $value = 'pluginlist';
			break;			
			case 'member':
				if(empty($value)) $value = 'memberlist';
				else if($value=='list') $value = 'memberlist';
			break;		
			case 'setting':
				if(empty($value)) $value = 'basic';
			break;		
			case 'user':
				if(empty($value)) $value = 'myinfo';
			break;
		}
?>
			<!-- center -->
			<ul id="submenu_center" class="submenu_center<?php echo $action=='center'?' viewed':'';?>">
				<li class="lastChild <?php echo $value=='dashboard'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/center/dashboard"><?php echo _t("종합");?></a></span></li>
				<!--
				<li class="sep"></li>
				<li class="lastChild <?php echo $value=='statistics'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/center/statistics"><?php echo _t("통계");?></a></span></li>
				-->
<?php
				func::printPluginMenu('center',$value);
?>
			</ul>

			<!-- blog -->
			<ul id="submenu_blog" class="submenu_blog<?php echo $action=='blog'?' viewed':'';?>">
				<li class="<?php echo $value=='add'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/add"><?php echo _t("블로그추가");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='bloglist'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/list"><?php echo _t("블로그목록");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='entrylist'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/entrylist"><?php echo _t("글목록");?></a></span></li>
				<li class="sep"></li>
<?php
			if($is_admin) {
?>
				<li class="<?php echo $value=='group'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/group"><?php echo _t("그룹");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='category'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/category"><?php echo _t("분류");?></a></span></li>
				<li class="sep"></li>
<?php
			}
?>
				<li class="lastChild <?php echo $value=='trash'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/trash"><?php echo _t("휴지통");?></a></span></li>

<?php
				func::printPluginMenu('blog',$value);
?>
			</ul>

<?php
	if($is_admin) {
?>
			<!-- design -->
			<ul id="submenu_design" class="submenu_design<?php echo $action=='design'?' viewed':'';?>">
				<li class="<?php echo $value=='meta'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/design/meta"><?php echo _t("메타스킨");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='link'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/design/link"><?php echo _t("링크스킨");?></a></span></li>
				<li class="sep"></li>
				<li class="lastChild <?php echo $value=='setting'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/design/setting"><?php echo _t("스킨설정");?></a></span></li>
			<!--
				<li class="sep"></li>
				<li class="lastChild <?php echo $value=='index'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/design/index"><?php echo _t("첫화면");?></a></span></li>
			-->
<?php
				func::printPluginMenu('design',$value);
?>
			</ul>

			<!-- plugin -->
			<ul id="submenu_plugin" class="submenu_plugin<?php echo $action=='plugin'?' viewed':'';?>">
				<li class="<?php echo $value=='pluginlist'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/plugin/list"><?php echo _t("플러그인");?></a></span></li>
				<li class="sep"></li>
				<li class="lastChild <?php echo $value=='export'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/plugin/export"><?php echo _t("익스포트");?></a></span></li>
<?php
				func::printPluginMenu('plugin',$value);
?>
			</ul>

			<!-- member -->
			<ul id="submenu_member" class="submenu_member<?php echo $action=='member'?' viewed':'';?>">
				<li class="lastChild <?php echo $value=='memberlist'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/member/list"><?php echo _t("회원 목록");?></a></span></li>
<?php
				func::printPluginMenu('member',$value);
?>
			</ul>

			<!-- setting -->
			<ul id="submenu_setting" class="submenu_setting<?php echo $action=='setting'?' viewed':'';?>">
				<li class="<?php echo $value=='basic'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/setting/basic"><?php echo _t("환경설정");?></a></span></li>
				<li class="sep"></li>	
				<li class="<?php echo $value=='owner'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/setting/owner"><?php echo _t("관리자설정");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='blind'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/setting/blind"><?php echo _t("블라인드");?></a></span></li>
				<li class="sep"></li>
				<li class="lastChild <?php echo $value=='etc'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/setting/etc"><?php echo _t("그외설정");?></a></span></li>
<?php
				func::printPluginMenu('setting',$value);
?>
			</ul>
<?php
} else  {
?>
			<!-- user -->
			<ul id="submenu_user" class="submenu_user<?php echo $action=='user'?' viewed':'';?>">
				<li class="lastChild <?php echo $value=='myinfo'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/user/myinfo"><?php echo _t("내 정보수정");?></a></span></li>
<?php
				func::printPluginMenu('user',$value);
?>
			</ul>
<?php
}
?>
	</div> <!-- wrap close -->
	</div> <!-- submenu close -->

	<div id="submenu2">
		<div class="wrap">
			<div id="project_message">
				<ul>
<?php
	if($is_admin) {
?>
					<li><a href="#" onclick="updateAll(); return false;"><?php echo _t('전체 업데이트');?></a></li>
<?php
	}
?>
				</ul>
			</div>
			<div id="project_link">
				<a href="http://bloglounge.itcanus.net/" target="_blank"><?php echo _t("블로그라운지 홈페이지");?></a>
			</div>
			<div class="clear"></div>
		</div>
	</div> <!-- submenu2 close -->

<?php
 if($userInformation['is_accepted'] == 'n') {
?>
	<div class="accept_wrap wrap">
		<div class="box2"><div class="box2_l"><div class="box2_r">
			<div class="accept_data">
				<?php echo _t('주의 : 회원님은 현재 관리자가 인증하지 않은 미인증 회원이십니다. 관리페이지의 사용이 일부 제한됩니다.');?>
			</div>
		</div></div></div>
	</div>
<?php
 }
?>