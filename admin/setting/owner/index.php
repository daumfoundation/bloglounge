<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();
	
	if (isset($_POST['leaveout']) && !empty($_POST['leaveoutpassword'])) {
		
		if (!$myPassword = User::get(getLoggedId(), 'password')) {
			//echo '<script type="text/javascript">alert("'._t('비밀번호 확인에 실패했습니다').'");</script>';				
		} else {
			if ($myPassword != Encrypt::hmac($_POST['loginid'], md5(md5($_POST['leaveoutpassword'])))) {
			//	echo '<script type="text/javascript">alert("'._t('비밀번호가 잘못되었습니다').'");</script>';				
			} else {
				User::delete(getLoggedId()); 
				logout();
				echo '<script type="text/javascript">alert("'._t('탈퇴했습니다. 안녕히가세요.').'"); document.location.replace("http://'.$_SERVER['HTTP_HOST'].$service['path'].'");</script>';
			}
		}
	} else if (isset($_POSt['leaveout']) && empty($_POST['leaveoutpassword'])) {
		// echo '<script type="text/javascript">alert("'._t('탈퇴 과정을 진행하려면 비밀번호 확인 입력을 해주세요').'");</script>';
	} else {
		if (isset($_POST['name'])) {
			$moArr = array("name"=>$_POST['name'], "email"=>$_POST['email']);
			if (!empty($_POST['password'])) {
				$moArr['password'] = Encrypt::hmac($_POST['loginid'], md5(md5($_POST['password'])));
				$moArr['plainpassword'] = $_POST['password'];
			}
			if (!User::edit($session['id'], $moArr, 'plainpassword')) {
				//echo '<script type="text/javascript">alert("'._t('회원정보를 수정할 수 없습니다').'");</script>';
			} else {
				$userInformation = getUsers();
				//echo '<script type="text/javascript">alert("'._t('회원정보를 수정했습니다').'");</script>';			
				addAppMessage(_t('수정 완료했습니다.'));
			}
		}
	}
	
	include ROOT. '/lib/piece/adminHeader.php';

?>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_user.css" type="text/css" />
<script type="text/javascript">
</script>

<div class="wrap title_wrap">
	<h3><?php echo _t("관리자설정");?></h3>
</div>

<div class="wrap user_wrap">
				<form method="post" action="">
				<dl>
					<dt><?php echo _t('아이디');?></dt>
					<dd class='text'>
						<?php echo $userInformation['loginid'];?>
						<input type="hidden" name="loginid" value="<?php echo $userInformation['loginid'];?>" />
					</dd>
				</dl>
				<dl>
					<dt><?php echo _t('이름');?></dt>
					<dd>
						<input type="text" name="name" value="<?php echo htmlspecialchars($userInformation['name']);?>" class="input faderInput"/>
					</dd>
				</dl>	
				<dl>
					<dt><?php echo _t('이메일');?></dt>
					<dd>
						<input type="text" name="email" value="<?php echo $userInformation['email'];?>" class="input faderInput"/>
					</dd>
				</dl>
				<dl>
					<dt><?php echo _t('비밀번호');?></dt>
					<dd>
						<input type="password" name="password" value="" class="input faderInput"/>
					</dd>
				</dl>
				<dl>
					<dt><?php echo _t('가입날짜');?></dt>
					<dd class='text'>
						<?php echo date('Y-m-d H:i:s', $userInformation['created']);?>
					</dd>
				</dl>	
				<dl>
					<dt><?php echo _t('로그인');?></dt>
					<dd class='text'>
						<?php if ($userInformation['lastLogin']) { ?><span class="rdate"><?php echo date('Y-m-d H:i:s', $userInformation['lastLogin']);?></span><?php } else {?><span style="letter-spacing:-0.5pt; color:#696969;"><?php echo _t('이전에 로그인 한 일이 없습니다');?></span><?php } ?>
					</dd>
				</dl>			
			<div class="grayline"></div>

			<span class="normalbutton"><input type="submit" value="<?php echo _t('이 정보를 수정합니다');?>" /></span>

		</form>
</div>
<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
