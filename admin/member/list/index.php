<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	include ROOT. '/lib/piece/adminHeader.php';	

	$read = isset($_GET['read'])?$_GET['read']:0;	
	if (!preg_match("/^[0-9]+$/", $read)) {
		$read = 0;
	}	

	requireComponent('Bloglounge.Model.Users');
	
	if(!empty($read)) {
		$readUser = User::getAll($read);
	} else {
		$readUser = false;
	}

	// 정보수정
	$msg = '';
	if ($readUser && (isset($_POST['id']) || !empty($_POST['id']))) {
		if (preg_match("/^[0-9]+$/", $_POST['id'])) {
			$isAdmin = (isset($_POST['is_admin'])) ? 'y' : 'n';
			if($_POST['is_secede'] == '1') { // 탈퇴
				if($readUser['is_admin'] == 'y') {
					echo '<script type="text/javascript">alert("'._t('관리자 권한을 가지고 있는 회원은 탈퇴처리할 수 없습니다.').'");</script>';
				} else {
					User::delete($_POST['id']); 
				}
			} else {
				if (($readUser['is_admin'] == 'y') && ($isAdmin == 'n')) {
					$countAdmin = User::getAdminCount();
					if ($countAdmin <= 1) {
						echo '<script type="text/javascript">alert("'._t('한 명 이상의 관리자는 존재해야 합니다.').'");</script>';
						$isAdmin = 'y';
					}
				}
				$isAccepted = (isset($_POST['is_accepted'])) ? 'y' : 'n';
				$passw = (!empty($_POST['password'])) ? Encrypt::hmac($readUser['loginid'], md5(md5($_POST['password']))) : '';
				$moArr = array("name"=>$_POST['name'], "email"=>$_POST['email'], "password"=>$passw, "plainpassword"=>$_POST['password'], "is_admin"=>$isAdmin, "is_accepted"=>$isAccepted);
				if (!User::edit($_POST['id'], $moArr, 'plainpassword')) {
					$msg = _t('회원정보 수정 실패');
				} else {
					$msg = _t('회원정보 수정 성공');
				}
			}
		}		
		
		$readUser = User::getAll($read);
	}

	$pageCount = 15; // 페이지갯수
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if(!isset($page) || empty($page)) $page = 1;
	
	$memberCount = User::getMemberCount();
	$members = User::getMembers('',$page, $pageCount);
	$paging = Func::makePaging($page, $pageCount, $memberCount);
?>

<script type="text/javascript">
<?php
	if(!empty($read)) {
?>		
		function deleteMember() {
			if (!confirm('<?php echo _t('탈퇴처리를 한 회원의 글은 모두 삭제되며, 다시 복구 하실 수 없습니다.\n\n탈퇴처리 하시겠습니까?');?>')) {
				return false;
			}

			var form = $("#memberForm");
			$('#is_secede').val('1');

			form.submit();
			return true;
		}

		$(window).ready( function() {
			collectDiv("#read_item1", "#read_item2");
		});
<?php
	}
?>
</script>

<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_member.css" type="text/css" />
<div class="wrap title_wrap">
	<h3><?php echo _t("회원 목록");?> <span class="cnt">(<?php echo $memberCount;?>)</span></h3>
</div>

<?php
	if($readUser) {		
		$date = Func::dateToString($readUser['created']);			
		$date2 = Func::dateToString($readUser['lastLogin']);
		list($feeds, $totalFeeds) = Feed::getFeedsByOwner($readUser['id'], 1, 10);
		$totalFeedItems = 0;
		if($totalFeeds>0) {
			foreach($feeds as $feed) {
				$totalFeedItems += $feed['feedCount'];
			}
		}
?>
<div class="wrap">
	<div class="read_item read_item1">
		<?php echo drawAdminBoxBegin('item_wrap');?>
			<div id="read_item1" class="item">
				<h2><?php echo _f('%1 님의 회원정보',$readUser['name']);?></h2>
				<div class="extra">		
					<?php echo _t('아이디');?> : <span class="name"><?php echo $readUser['loginid'];?></span> <?php echo !empty($readUser['email'])?'<a href="mailto:'.$readUser['email'].'"><img src="'.$service['path'].'/images/admin/icon_email.gif" alt="email" align="absmiddle" class="icon_email" /></a>':'';?>
					&nbsp;
					<?php echo _t('등록한 블로그수');?> : <span class="count"><?php echo $totalFeedItems;?></span>

					<br />

					<?php echo _t('가입일');?> : <span class="date"><?php echo date('y.m.d H:i:s', $readUser['created']);?></span> <span class="date_text">(<?php echo _f($date[0],$date[1]);?>)</span> 
					&nbsp;			

					<?php echo _t('최근로그인');?> : <span class="date"><?php echo date('y.m.d H:i:s', $readUser['lastLogin']);?></span> <span class="date_text">(<?php echo _f($date2[0],$date2[1]);?>)</span> 
					
					<div class="data">	
						<div class="recent_feeds">
							<h2>최근 등록한 블로그</h2>
							<ul>
<?php
		if(count($feeds)>0) {
				foreacH($feeds as $feed) {		
?>
								<li><a href="<?php echo $service['path'];?>/admin/blog/list/?read=<?php echo $feed['id'];?>"><?php echo $feed['title'];?></a></li>
<?php
				}
		} else {
?>
								<li class="empty"><?php echo _t('등록한 블로그가 없습니다.');?></li>
<?php	} 
					if(count($feeds)<$totalFeeds) {
?>
								 <li class="more"><a href="<?php echo $service['path'];?>/admin/blog/list/?type=owner&keyword=<?php echo rawurlencode($readUser['loginid']);?>"><?php echo _t('전체보기..');?></a></li>
<?php
					}
?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php echo drawAdminBoxEnd();?>
	</div>
	<div class="read_item read_item2">		
		<?php echo drawAdminBoxBegin('item_wrap');?>
			<div id="read_item2" class="item">
				<form id="memberForm" method="post" action="">
					<input type="hidden" name="id" value="<?php echo $read;?>"/>
					<input type="hidden" id="is_secede" name="is_secede" value="0" />
<?php
				if ($readUser['is_accepted']=='y') { 
?>
					<input type="hidden" name="is_accepted" value="y"/>
<?php 
				} 
?>
					
					<dl>
						<dt class="wide"><?php echo _t('아이디');?></dt>
						<dd class="text"><?php echo $readUser['loginid'];?></dd>
					</dl>
					<dl>
						<dt class="wide"><?php echo _t('이름(별명)');?></dt>
						<dd><input type="text" name="name" value="<?php echo htmlspecialchars($readUser['name']);?>" class="input faderInput"/></dd>
					</dl>
					<dl>
						<dt class="wide"><?php echo _t('이메일');?></dt>
						<dd><input type="text" name="email" value="<?php echo $readUser['email'];?>" class="input faderInput"/></dd>
					</dl>
					<dl>
						<dt class="wide"><?php echo _t('비밀번호');?></dt>
						<dd><input type="password" name="password" value="" class="input faderInput"/></dd>
					</dl>
					<dl>
						<dt class="wide"><?php echo _t('관리자권한');?></dt>
						<dd>
							<input type="checkbox" id="is_admin" name="is_admin" value="y" <?php if ($readUser['is_admin']=='y') {?>checked="checked"<?php }?> onclick="return confirm('<?php echo _t('주의! 관리자 권한이 부여되면 데이터 삭제를 포함한 모든 관리 기능을 사용할 수 있습니다');?>.');"/>&nbsp;<label for="is_admin"><?php echo _t('이 회원에게 관리자 권한을 부여합니다');?></label>
						</dd>
					</dl>
<?php
			if (!Validator::getBool($readUser['is_accepted'])) {
?>
					<dl>
						<dt class="wide"><?php echo _t('가입승인');?></dt>
						<dd>
							<input type="checkbox" name="is_accepted" value="y" /> <label for="is_accepted"><?php echo _t('이 설정을 선택하면 이 회원의 가입을 승인합니다');?></label>
						</dd>
					</dl>
<?php		
			} 
?>
				
					<br />

					<div class="grayline"></div>

					<p class="button_wrap">
						<span class="normalbutton"><input type="submit" value="<?php echo _t('수정완료');?>" /></span>
<?php
					if($readUser['is_admin'] == 'n') {
?>
						<a href="#" class="normalbutton" onclick="deleteMember(); return false;"><span class="boldbutton"><?php echo _t('탈퇴');?></span></a>
<?php
					}
?>
					</p>				
				</form>
			</div>
		<?php echo drawAdminBoxEnd();?>
	</div>
	<div class="clear"></div>
</div>
<?php	
	}
?>


<div class="wrap">
<?php 
	$headers = array(array('title'=>_t('번호'),'class'=>'member_number','width'=>'60px'),
					array('title'=>_t('가입일'),'class'=>'member_created','width'=>'130px'),
					array('title'=>_t('아이디'),'class'=>'member_id','width'=>'100px'),
					array('title'=>_t('별명'),'class'=>'member_nickname','width'=>'140px'),
					array('title'=>_t('등록된 블로그'),'class'=>'member_blogs','width'=>'auto'),
					array('title'=>_t('수집한 글수'),'class'=>'member_count','width'=>'100px'));
	
	$datas = array();

	if(count($members)>0) {

		foreach($members as $member) {	
			$stringDate = Func::dateToString($member['created']);
			list($feeds, $totalFeeds) = Feed::getFeedsByOwner($member['id'], 'all');
			$totalFeedItems = 0;
			if($totalFeeds>0) {
				foreach($feeds as $feed) {
					$totalFeedItems += $feed['feedCount'];
				}
			}
			
			$data = array();

			$data['class'] = ($read==$member['id']?' list_item_select':'');
			
			$data['datas'] = array();
			
			// 멤버 번호
			array_push($data['datas'], array('class'=>'member_number','data'=> $member['id'] ));

			// 멤버 가입일	
			array_push($data['datas'], array('class'=>'member_created','data'=> date('y.m.d H:i:s', $member['created']) ));
			
			// 멤버 아이디	
			array_push($data['datas'], array('class'=>'member_id','data'=> '<a href="'.$service['path'].'/admin/member/list/?read='.$member['id'].'">'.$member['loginid'].'</a>' . (!Validator::getBool($member['is_accepted'])?(' <span class="not_accept">('._t('미인증').')</span>'):'') ));
		
			// 멤버 별명
			array_push($data['datas'], array('class'=>'member_nickname','data'=> $member['name'] ));

			// 멤버 블로그
			ob_start();

			if($totalFeeds > 0) {
				if($totalFeeds == 1) {
?>
					<a href="<?php echo $service['path'];?>/admin/blog/list/?read=<?php echo $feeds[0]['id'];?>"><?php echo $feeds[0]['title'];?></a>
<?php
				} else {
?>
					<?php echo _f('"%1" 외 %2 개의 블로그', '<a href="'.$service['path'].'/admin/blog/list/?read='.$feeds[0]['id'].'">'.$feeds[0]['title'].'</a>', $totalFeeds-1);?>
<?php
				}
			} else {
?>
			<span class="empty"><?php echo _t('등록된 블로그가 없습니다.');?></span>
<?php				
			}

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'member_blogs','data'=> $content ));
		
			// 멤버 글수
			array_push($data['datas'], array('class'=>'member_count','data'=> $totalFeedItems ));

			array_push($datas, $data);				
		}

	} else {
			array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>'회원이 존재하지 않습니다.') )) );
	}
	$footers = '';
	echo makeTableBox('memberlist', $headers, $datas, $footers);	
?>
</div>

<br />

<div class="wrap">
	<div class="paging">
		<?php echo outputPaging($paging);?>
	</div>
</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
