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
	var _now = '<?php echo $action;?>';
	var _lang = '<?php echo Locale::get();?>';
</script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/admin.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/modal.js"></script>

<script type="text/javascript">

	function updateGlobalFeed(type) {
		 addMessage("<?php echo _t('블로그 업데이트를 시작합니다.');?>");
		 $.ajax({
		  type: "POST",
		  url: _path +'/service/update/?type=' + type,
		  success: function(msg){
			 addMessage("<?php echo _t('블로그 업데이트가 완료되었습니다.');?>");
		  },
		  error: function(msg) {
		  }
		});
	};

	function menuAction() {
		$("#header .menus ul li a img").each( function() {
			$(this).mouseover( function() {
				if(this.name != _now) {
					$(this).attr("src", "<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/" + this.id + "_hover.gif");
				}
			}).mouseout( function() {	
				if(this.name != _now) {
					$(this).attr("src", "<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/" + this.id + ".gif");				
				}
			});
		});
	}

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
	<div id="header">
	<div class="wrap">
		<div class="logo">
			<a href="<?php echo $service['path'];?>/admin"><img src="<?php echo $service['path'];?>/images/admin/logo.gif" alt="<?php echo _t("로고");?>" /></a>
		</div>
		<div class="menus">
			<ul>
				<li id="menu_center"><a href="<?php echo $service['path'];?>/admin/"><img id="admin_menu1" name="center" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu1<?php echo $action=='center'?'_select':'';?>.gif" alt="<?php echo _t("센터");?>"/></a></li>
				<li id="menu_blog"><a href="<?php echo $service['path'];?>/admin/blog"><img id="admin_menu2" name="blog" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu2<?php echo $action=='blog'?'_select':'';?>.gif" alt="<?php echo _t("블로그");?>"/></a></li>
<?php
	if($is_admin) {
?>
				<li id="menu_design"><a href="<?php echo $service['path'];?>/admin/design"><img id="admin_menu3" name="design" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu3<?php echo $action=='design'?'_select':'';?>.gif" alt="<?php echo _t("디자인");?>"/></a></li>
				<li id="menu_plugin"><a href="<?php echo $service['path'];?>/admin/plugin"><img id="admin_menu4" name="plugin" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu4<?php echo $action=='plugin'?'_select':'';?>.gif" alt="<?php echo _t("플러그인");?>"/></a></li>
				<li id="menu_member"><a href="<?php echo $service['path'];?>/admin/member"><img id="admin_menu5" name="member" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu5<?php echo $action=='member'?'_select':'';?>.gif" alt="<?php echo _t("회원");?>"/></a></li>
				<li id="menu_setting"><a href="<?php echo $service['path'];?>/admin/setting"><img id="admin_menu6" name="setting" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu6<?php echo $action=='setting'?'_select':'';?>.gif" alt="<?php echo _t("설정");?>"/></a></li>
<?php
	} else {
?>			
				<li id="menu_user"><a href="<?php echo $service['path'];?>/admin/user"><img id="admin_menu7" name="user" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/admin_menu7<?php echo $action=='user'?'_select':'';?>.gif" alt="<?php echo _t("개인");?>"/></a></li>
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
?>
			<!-- center -->
			<ul class="submenu_center">
				<li class="lastChild <?php echo $value=='dashboard'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/center/dashboard"><?php echo _t("종합");?></a></span></li>
				<!--
				<li class="sep"></li>
				<li class="lastChild <?php echo $value=='statistics'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/center/statistics"><?php echo _t("통계");?></a></span></li>
				-->
<?php
				func::printPluginMenu('center',$value);
?>
			</ul>
<?php
			break;		
			case 'blog':
				if(empty($value)) $value = 'list';
?>
			<!-- blog -->
			<ul class="submenu_blog">
				<li class="<?php echo $value=='add'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/add"><?php echo _t("블로그추가");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='list'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/list"><?php echo _t("블로그목록");?></a></span></li>
				<li class="sep"></li>
				<li class="<?php echo $value=='entrylist'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/blog/entrylist"><?php echo _t("글목록");?></a></span></li>
				<li class="sep"></li>
<?php
	if($is_admin) {
?>
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
				
			break;			
			case 'design':
				if(empty($value)) $value = 'meta';
?>
			<!-- blog -->
			<ul class="submenu_design">
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
<?php
			break;			
			case 'plugin':
				if(empty($value)) $value = 'list';
?>
			<!-- blog -->
			<ul class="submenu_plugin">
				<li class="lastChild <?php echo $value=='list'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/plugin/list"><?php echo _t("플러그인 목록");?></a></span></li>
<?php
				func::printPluginMenu('plugin',$value);
?>
			</ul>
<?php
			break;			
			case 'member':
				if(empty($value)) $value = 'list';
?>
			<!-- blog -->
			<ul class="submenu_member">
				<li class="lastChild <?php echo $value=='list'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/member/list"><?php echo _t("회원 목록");?></a></span></li>
<?php
				func::printPluginMenu('member',$value);
?>
			</ul>
<?php
			break;		
			case 'setting':
				if(empty($value)) $value = 'basic';
?>
			<!-- blog -->
			<ul class="submenu_setting">
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
			break;		
			case 'user':
				if(empty($value)) $value = 'myinfo';
?>
			<!-- blog -->
			<ul class="submenu_user">
				<li class="lastChild <?php echo $value=='myinfo'?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/user/myinfo"><?php echo _t("내 정보수정");?></a></span></li>
<?php
				func::printPluginMenu('user',$value);
?>
			</ul>
<?php
			break;
		}
?>
	</div> <!-- wrap close -->
	</div> <!-- submenu close -->

	<div id="submenu2">
		<div class="wrap">
			<div id="project_message">
				<ul>
					<li><a href="#" onclick="updateGlobalFeed('repeat'); return false;"><?php echo _t('블로그 업데이트');?></a></li>
				</ul>
			</div>
			<div id="project_link">
				<a href="http://bloglounge.itcanus.net/" target="_blank"><?php echo _t("소개");?></a> <span class="sep">|</span> <a href="http://bloglounge.itcanus.net/download"  target="_blank"><?php echo _t("배포페이지");?></a> <span class="sep">|</span> <a href="http://bloglounge.itcanus.net/qa" target="_blank"><?php echo _t("Q&A");?></a>
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