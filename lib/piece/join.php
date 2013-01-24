<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGLOUNGE;?> :: <?php echo _t('회원가입');?></title>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/join.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/box.css" type="text/css" />

<script type="text/javascript">var _path = '<?php echo $service['path'];?>';</script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/join.js"></script>
<script type="text/javascript">
	function onJoin(form) {
		var username = $(form.username);
		var userid = $(form.userid);
		var userpw = $(form.userpw);
		var userpw2 = $(form.userpw2);
		var useremail = $(form.useremail);
		
		if(username.val() == "") {
			alert('<?php echo _t('이름을 입력해주세요.');?>');
			username.focus();
			return false;
		}
		if(userid.val() == "") {
			alert('<?php echo _t('아이디를 입력해주세요.');?>');
			userid.focus();
			return false;
		}
		if(userid.val().length() < 3) {
			alert('<?php echo _t('아이디는 3자이상 입력해주세요.');?>');
			userid.focus();
			return false;
		}
		if(userpw.val() == "") {
			alert('<?php echo _t('비밀번호를 입력해주세요.');?>');
			userpw.focus();
			return false;
		}
		if(userpw2.val() == "") {
			alert('<?php echo _t('비밀번호 확인을 입력해주세요.');?>');
			userpw2.focus();
			return false;
		}
		if(userpw.val().length() < 4) {
			alert('<?php echo _t('비밀번호는 4자이상 입력해주세요.');?>');
			userpw.focus();
			return false;
		}
		if(userpw.val().length() < 4) {
			alert('<?php echo _t('비밀번호는 4자이상 입력해주세요.');?>');
			userpw2.focus();
			return false;
		}

		if(useremail.val() == "") {
			alert('<?php echo _t('이메일주소를 입력해주세요.');?>');
			useremail.focus();
			return false;
		}

		return true;
	}
</script>
</head>

<body>
	<div id="container">
		<div class="box">
			<div class="box_l"><div class="box_r"><div class="box_t"><div class="box_b">
				<div class="box_lt"><div class="box_rt"><div class="box_lb"><div class="box_rb">
					<div class="box_data">
												
						<div id="logo">
							<a href="<?php echo $service['path'];?>/"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/login_bloglounge_logo.gif" alt="<?php echo BLOGLOUNGE;?>" /></a>
						</div>

						<hr class="line" />

						<form method="post" action="" onsubmit="return onJoin(this);">
							<div id="join_wrap">							
 <img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/join_title.gif" alt="<?php echo _t('회원가입');?>" align="absmiddle" />
									<input type="hidden" name="posted" value="true"/>
									<?php if (!empty($_GET['requestURI'])) {?><input type="hidden" name="requestURI" value="<?php echo $_GET['requestURI'];?>"><?}?>

									<table class="join_table" cellspacing="0" cellpadding="0">
<?php
	$is_error = array_keys_exist(array('username'), $errors);
?>
										<tr>
											<td class="title"><label for="username"><?php echo _t('이름');?></label></td>
											<td>
												<input type="text" id="username" name="username" tabindex="1" class="input <?php echo $is_error?'errorInput':'faderInput';?>" value="<?php echo isset($_POST['username'])?$_POST['username']:'';?>" />
											</td>
										</tr>
<?php 
			if($is_error) {
?>
										<tr class="comment alert">
											<td></td>
											<td>	
												<ol>
													<?php echo isset($errors['username'])?'<li>'.$errors['username'].'</li>':'';?>
												</ol>
											</td>
										</tr>
<?php
			}
	$is_error = array_keys_exist(array('userid1','userid2','userid3','userid4'), $errors);
?>
										<tr>
											<td class="title"><label for="userid"><?php echo _t('아이디');?></label></td>
											<td><input type="text" id="userid" name="userid" tabindex="2" class="input <?php echo $is_error?'errorInput':'faderInput';?>" value="<?php echo isset($_POST['userid'])?$_POST['userid']:'';?>" /></td>
										</tr>
<?php 
			if($is_error) {
?>
										<tr class="comment alert">
											<td></td>
											<td>	
												<ol>
													<?php echo isset($errors['userid1'])?'<li>'.$errors['userid1'].'</li>':'';?>								
													<?php echo isset($errors['userid2'])?'<li>'.$errors['userid2'].'</li>':'';?>								
													<?php echo isset($errors['userid3'])?'<li>'.$errors['userid3'].'</li>':'';?>								
													<?php echo isset($errors['userid4'])?'<li>'.$errors['userid4'].'</li>':'';?>								
												</ol>
											</td>
										</tr>
<?php
			} else {
?>
										<tr class="comment">
											<td></td>
											<td><?php echo _t('아이디는 3글자 이상으로 입력해주세요.');?></td>
										</tr>
<?php
			}
			$is_error = array_keys_exist(array('userpw1','userpw2','userpw3','userpw4'), $errors);
?>
										<tr>
											<td class="title"><label for="userpw"><?php echo _t('비밀번호');?></label></td>
											<td><input type="password" id="userpw" name="userpw" tabindex="3" class="input <?php echo $is_error?'errorInput':'faderInput';?>" /> </td>
										</tr>
										<tr>
											<td class="title"><label for="userpw2"><?php echo _t('비밀번호 확인');?></label></td>
											<td> <input type="password" id="userpw2" name="userpw2" tabindex="4" class="input <?php echo $is_error?'errorInput':'faderInput';?>"></td>
										</tr>
<?php 
			if($is_error) {
?>
										<tr class="comment alert">
											<td></td>
											<td>	
												<ol>
													<?php echo isset($errors['userpw1'])?'<li>'.$errors['userpw1'].'</li>':'';?>								
													<?php echo isset($errors['userpw2'])?'<li>'.$errors['userpw2'].'</li>':'';?>								
													<?php echo isset($errors['userpw3'])?'<li>'.$errors['userpw3'].'</li>':'';?>								
													<?php echo isset($errors['userpw4'])?'<li>'.$errors['userpw4'].'</li>':'';?>								
												</ol>
											</td>
										</tr>
<?php
			} else {
?>
										<tr class="comment">
											<td></td>
											<td><?php echo _t('비밀번호는 4글자 이상으로 2개의 비밀번호를 동일하게 입력해주세요.');?></td>
										</tr>
<?php
			}
			$is_error = array_keys_exist(array('useremail1','useremail2'), $errors);
?>
										<tr>
											<td class="title"><label for="useremail"><?php echo _t('이메일주소');?></label></td>
											<td><input type="text" id="useremail" name="useremail" tabindex="5" class="input <?php echo $is_error?'errorInput':'faderInput';?>"  value="<?php echo isset($_POST['useremail'])?$_POST['useremail']:'';?>"/></td>
										</tr>
<?php 
			if($is_error) {
?>
										<tr class="comment alert">
											<td></td>
											<td>	
												<ol>
													<?php echo isset($errors['useremail1'])?'<li>'.$errors['useremail1'].'</li>':'';?>								
													<?php echo isset($errors['useremail2'])?'<li>'.$errors['useremail2'].'</li>':'';?>							
												</ol>
											</td>
										</tr>
<?php
			}
?>
			
									</table>
									
									<div class="buttons">
										<input type="image" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_join.gif" alt="<?php echo _t('회원가입');?>" />
										<a href="<?php echo $service['path'];?>/"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_cancel.gif" alt="<?php echo _t('취소');?>" /></a>

									</div>	
							</div>

							<div id="temp_images">
								<a href="http://itcanus.net" target="_blank"><img src="<?php echo $service['path'];?>/images/admin/login_itcanus.gif" alt="ITcanus" /></a>
							</div>

							<div class="clear"></div>					
													
						</form>

					</div>
				</div></div></div></div>
			</div></div></div></div>
		</div>
</body>
</html>