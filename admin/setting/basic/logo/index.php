<?php
	define('ROOT', '../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGLOUNGE;?> :: <?php echo _t('관리페이지');?></title>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/common.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin.css" type="text/css" />
<link rel="shortcut icon" href="<?php echo $service['path'];?>/images/favicon.ico" />
<script type="text/javascript">
	var isAdministratorMode = true;
</script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/admin.js"></script>
</head>

<body style="background:transparent;">
<?php
	$config = new Settings;
	$requests = array();
	$requests['title'] = $db->escape($_POST['title']);
	$requests['description'] = $db->escape($_POST['description']);
	if (isset($_POST['delLogo'])) {
		$requests['logo'] = '';
		@unlink(ROOT.'/cache/logo/'.$config->logo);
	}

	$config->setWithArray($requests);

	if(!empty($_FILES['logoFile']['tmp_name']) && !isset($_POST['delLogo'])){
		if (!Validator::enum(func::getExt($_FILES['logoFile']['name']), 'gif,jpg,png')) {
			echo '<script type="text/javascript">parent.addMessage("'._t('로고는 GIF, JPG, PNG 형식의 파일만 가능합니다').'");</script>';
			exit;
		} else {
			$path = ROOT . '/cache/logo';
			if (!is_dir($path)) {
				mkdir($path);
				if (!is_dir($path)) {
					echo '<script type="text/javascript">parent.addMessage("'._t('로고 이미지를 업로드 할 수 없었습니다').'");</script>';
					exit;
				}
				@chmod($path, 0777);
			}

			if (file_exists($path . '/'. basename($_FILES['logoFile']['name']))) {
				$filename = substr(md5(time()), -1, 8).$_FILES['logoFile']['name'];
			} else {
				$filename = $_FILES['logoFile']['name'];
			}

			if (!move_uploaded_file($_FILES['logoFile']['tmp_name'], $path.'/'.$filename)) {
				echo '<script type="text/javascript">parent.addMessage("'._t('로고를 변경할 수 없었습니다').'");</script>';
				exit;
			} else {
				$config->set('logo', $filename);
				@unlink($path.'/'.$config->logo);
			}
		}
	}

	$nowLogo = $config->get('logo');
	$logoURL = (!empty($nowLogo)) ? '/cache/logo/'.$nowLogo : '/images/noimage.jpg';
	echo '<script type="text/javascript">parent.addMessage("'._t('수정 완료했습니다.').'"); parent.document.getElementById("myLogo").src = "'.$service['path'].$logoURL.'";</script>';

?>
</body>
</html>