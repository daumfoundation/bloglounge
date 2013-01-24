<?php
	define('ROOT', '../../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGROUNGE;?> :: <?php echo _t('관리페이지');?></title>
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
	flush();
	if($_POST['importType']=='upload'){ // OPML 업로드인 경우
		if (empty($_FILES['importFile']['tmp_name'])) {
			echo '<script type="text/javascript">alert("'._t('업로드 할 파일을 선택하지 않았습니다.').'");</script>';
		} else {
			if (preg_match("/(htm|php|inc|cgi|pl|perl|py|asp|jsp|exe|com|bat|dll|sh)/i", func::getExt($_FILES['importFile']['name']))) {
				echo '<script type="text/javascript">alert("'._f('%1는 잘못된 형식의 파일입니다.', $_FILES['importFile']['name']).'");</script>';
				$_FILES = null;
				exit;
			}

			$opmlCacheDir = ROOT . '/cache/opml';
			$tmpFilename = md5($_SERVER['REMOTE_ADDR'].time()).'.xml';
			if (!is_dir($opmlCacheDir)) func::mkpath($opmlCacheDir);
			if (!is_writable($opmlCacheDir) || !move_uploaded_file($_FILES['importFile']['tmp_name'], $opmlCacheDir.'/'.$tmpFilename)) {
				echo '<script type="text/javascript">alert("'._t('파일 가져오기에 실패했습니다.\n날개가 설치된 폴더와 cache 폴더에 쓰기 권한이 있는지 확인해주세요.').'");</script>';
				exit;
			}

			$xmls = new XMLStruct();
			$xmls->openFile($opmlCacheDir.'/'.$tmpFilename, true);
			$xmlURLs = func::multiarray_values($xmls->selectNodes("/opml/body/outline"), 'xmlUrl');

			if (count($xmlURLs)==0) {
				echo '<script type="text/javascript">alert("'._t('바른 형식의 OPML 파일이 아닙니다.').'");</script>';
				exit;
			}

			echo '<script type="text/javascript">"'._t('피드를 추가하고 있습니다').'";</script>';
			flush();
			$_feeder = new Feed;
			foreach($xmlURLs as $xmlURL) {		
				if (empty($xmlURL)) continue; 
				$_feeder->add($xmlURL);			
			}

			@unlink($opmlCacheDir.'/'.$tmpFilename);
		}
	} else { // URL 로부터 가져올 경우
		requireComponent('LZ.PHP.HTTPRequest');
		$request = new HTTPRequest;
		if (!$cont = $request->getPage($_POST['importURL'])) {
			echo '<script type="text/javascript">alert("'._t('파일을 가져올 수 없습니다.\n정확한 주소가 맞는지 확인해 주세요.').'");</script>';
			exit;
		}

		$xmls = new XMLStruct();
		$xmls->open($cont, true);
		if (!$n = $xmls->getNodeCount("/opml/body/outline")) {
			echo '<script type="text/javascript">alert("'._t('바른 형식의 OPML 파일이 아닙니다.').'");</script>';
			exit;
		}

		echo '<script type="text/javascript">"'._t('피드를 추가하고 있습니다').'";</script>';
		flush();

		$_feeder = new Feed;
		for ($i=1; $i <= $n; $i++) {
			$xmlURL = $xmls->getAttribute("/opml/body/outline[$i]", "xmlUrl");
			if (empty($xmlURL)) continue;		
			$_feeder->add($xmlURL);
		}
	}
?>

	<script type="text/javascript">
		parent.document.location.reload();
	</script>

</body>
</html>