<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGLOUNGE;?> :: <?php echo _t('로그인');?></title>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/login.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/box.css" type="text/css" />

<script type="text/javascript">var _path = '<?php echo $service['path'];?>';</script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/login.js"></script>
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
						
						<div id="login_wrap">
							<img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/login_title.gif" alt="<?php echo _t('회원로그인');?>" />
								<form method="post">
									<table cellspacing="0" cellpadding="0">
										<tr>
											<td class="input_td">
												<input type="text" id="member_id" name="useridin" tabindex="1" value="<?php echo htmlspecialchars(empty($_POST['userid']) ? (empty($_COOKIE['BLOGLOUNGE_LOGINID']) ? '' : $_COOKIE['BLOGLOUNGE_LOGINID']) : $_POST['userid']);?>" class="input faderInput" />
											</td>
											<td class="bt_td" rowspan="2">
												<input type="image" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/login_bt.gif" alt="<?php echo _t('로그인');?>" tabindex="3" />
											</td>
										</tr>
										<tr>
											<td class="input_td"><input type="password" id="member_password" name="userpwin" tabindex="2"  class="input faderInput" /></td>
										</tr>
									</table>

									<div class="save_id">
										<input type="checkbox" name="saveId" id="saveId" value="y" <?php echo (empty($_COOKIE['BLOGLOUNGE_LOGINID']) ? '' : 'checked="checked"');?>/><label for="saveId"><?php echo _t('아이디 저장');?></label>
									</div>
								</form>

								<div class="join_message">
									<a href="<?php echo $service['path'];?>/join/"><?php echo _t('지금 회원가입 하실 수 있습니다.');?></a>
								</div>
						</div>

						<div id="temp_images">
							<a href="http://itcanus.net" target="_blank"><img src="<?php echo $service['path'];?>/images/admin/login_itcanus.gif" alt="ITcanus" /></a>
						</div>

						<div class="clear"></div>

					</div>
				</div></div></div></div>
			</div></div></div></div>
		</div>
	</div>
</body>
</html>