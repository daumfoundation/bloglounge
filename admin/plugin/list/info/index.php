<?php
	// show plugin info
	define('ROOT', '../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	$pluginName = $_GET['pluginName'];

	
	if (!is_dir(ROOT . '/plugins/'.$pluginName)) {
		func::alert(_t('플러그인이 존재하지 않습니다'), 'dialog');
	}

	if (!file_exists(ROOT .'/plugins/'.$pluginName.'/index.xml')) {
		func::alert(_t('플러그인 정보를 찾을 수 없습니다'), 'dialog');
	}

	requireComponent('LZ.PHP.XMLStruct');
	$xmls = new XMLStruct;
	if (!$xmls->openFile(ROOT . '/plugins/'.$pluginName.'/index.xml')) {
		func::alert(_t('플러그인 정보를 읽을 수 없습니다'), 'dialog');
	}

	$pluginInfo = array();
	$pluginInfo['name'] = $pluginName;
	$pluginInfo['title'] = func::filterJavascript($xmls->getValue('/plugin/information/name[lang()]'));
	$pluginInfo['description'] = func::filterJavascript($xmls->getValue('/plugin/information/description[lang()]'));
	$pluginInfo['license'] = func::filterJavascript($xmls->getValue('/plugin/information/license[lang()]'));
	$pluginInfo['version'] = func::filterJavascript($xmls->getValue('/plugin/information/version'));
	$pluginInfo['author'] = func::filterJavascript($xmls->getValue('/plugin/information/author[lang()]'));
	$pluginInfo['email'] = func::filterJavascript($xmls->getAttribute('/plugin/information/author[lang()]', 'email'));
	$pluginInfo['homepage'] = func::filterJavascript($xmls->getAttribute('/plugin/information/author[lang()]', 'link'));
	$pluginInfo['config'] = $xmls->selectNode('/plugin/config[lang()]');
	$pluginInfo['status'] = Validator::getBool($db->queryCell("SELECT status FROM {$database['prefix']}Plugins WHERE name='{$pluginName}'"));

	$pluginInfo['tags'] = array();
	$sNode = $xmls->selectNode('/plugin/binding');
	if(isset($sNode['tag'])) {
		foreach($sNode['tag'] as $tag) {
			array_push($pluginInfo['tags'], '[##_'.$tag['.attributes']['name'].'_##]');
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo UTF8::clear($pluginInfo['title']);?></title>
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/common.css" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/modal.css" />
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/common.js"></script>
<script type="text/javascript">
	$(window).ready( function() {
		parent.$("#pluginDetailFrame").height($(document.body).height());
	});
</script>
</head>
<body>
	<div class="modal_title">
		<?php echo $pluginInfo['title'];?> <?php echo _t('정보');?>
	</div>
	<div class="modal_container">
		<ul class="modal_tabs">
			<li class="selected"><a href="<?php echo $service['path'];?>/admin/plugin/info/?pluginName=<?php echo $pluginInfo['name'];?>"><?php echo _t('이 플러그인에 대해');?></a></li>
<?php
		if (!is_null($pluginInfo['config']) && $pluginInfo['status']) { 
?>
			<li><a href="<?php echo $service['path'];?>/admin/plugin/list/config/?pluginName=<?php echo $pluginInfo['name'];?>"><?php echo _t('플러그인 설정');?></a></li>
<?php	
		}
?>
		</ul>

		<div class="clear"></div>

		<div class="modal_description">	
			<fieldset>
				<legend><?php echo $pluginInfo['title'];?></legend>
				<div class="info">
					<dl>
						<dt><?php echo _t('제작');?> :</dt>
						<dd>
							<?php echo $pluginInfo['author'];?>
						</dd>
					</dl>
					<dl>
						<dt><?php echo _t('버전');?> :</dt>
						<dd>
							<?php echo $pluginInfo['version'];?>
						</dd>
					</dl>
<?php if(!empty($pluginInfo['email'])) { ?>
					<dl>
						<dt><?php echo _t('이메일주소');?> :</dt>
						<dd>
							<a href="mailto:<?php echo $pluginInfo['email'];?>"><?php echo $pluginInfo['email'];?></a>
						</dd>
					</dl>
<?php } ?>
<?php if(!empty($pluginInfo['homepage'])) { ?>
					<dl>
						<dt><?php echo _t('홈페이지');?> :</dt>
						<dd>
							<a href="<?php echo $pluginInfo['homepage'];?>" target="_blank"><?php echo $pluginInfo['homepage'];?></a>
						</dd>
					</dl>
<?php } ?>	
				<dl>
					<dt><?php echo _t('설명');?> :</dt>
					<dd>
						<?php echo $pluginInfo['description'];?>
					</dd>
				</dl>	
				<dl>
					<dt><?php echo _t('라이센스');?> :</dt>
					<dd>
						<?php echo $pluginInfo['license'];?>
					</dd>
				</dl>		
				<dl>
					<dt><?php echo _t('위치');?> :</dt>
					<dd>
						<?php echo _f('이 플러그인은 %1 에 설치되어 있습니다.', htmlspecialchars($service['path'].'/plugins/'.$pluginInfo['name']));?>
					</dd>
				</dl>
<?php
	if(count($pluginInfo['tags']) > 0) {
?>
				<dl>
					<dt><?php echo _t('치환자');?> :</dt>
					<dd>
						<?php echo _f('사용하는 스킨에 %1 이 포함되어 있어야 합니다.', '<span class="strong">'.implode(', ', $pluginInfo['tags']).'</span>');?>
					</dd>
				</dl>	
<?php
	}
?>
			</div>
			</fieldset>
		</div>

		<div class="modalclose_wrap">
			<a href="#" onclick="parent.hideModal(); return false;"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_close.gif" alt="<?php echo _t('닫기');?>" /></a>
		</div>

	</div>
</body>
</html>