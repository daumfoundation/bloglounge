<?php
	// show export info
	define('ROOT', '../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();
	$domainName = $_GET['domainName'];

	requireComponent('Bloglounge.Model.Exports');

	$programName = Export::getProgramNameByDomain($domainName);

	if (!is_dir(ROOT . '/exports/'.$programName)) {
		func::alert(_t('프로그램이 존재하지 않습니다'), 'dialog');
	}

	if (!file_exists(ROOT .'/exports/'.$programName.'/index.xml')) {
		func::alert(_t('프로그램 정보를 찾을 수 없습니다'), 'dialog');
	}

	requireComponent('LZ.PHP.XMLStruct');
	$xmls = new XMLStruct;
	if (!$xmls->openFile(ROOT . '/exports/'.$programName.'/index.xml')) {
		func::alert(_t('프로그램 정보를 읽을 수 없습니다'), 'dialog');
	}

	$exportInfo = array();
	$exportInfo['domain'] = $domainName;
	$exportInfo['program'] = $programName;
	$exportInfo['title'] = $xmls->getValue('/export/information/name[lang()]');
	$exportInfo['config'] = $xmls->selectNode('/export/config[lang()]');
	$exportInfo['description'] = func::filterJavascript($xmls->getValue('/export/information/description[lang()]'));
	$exportInfo['license'] = func::filterJavascript($xmls->getValue('/export/information/license[lang()]'));
	$exportInfo['version'] = func::filterJavascript($xmls->getValue('/export/information/version'));
	$exportInfo['author'] = func::filterJavascript($xmls->getValue('/export/information/author[lang()]'));
	$exportInfo['email'] = func::filterJavascript($xmls->getAttribute('/export/information/author[lang()]', 'email'));
	$exportInfo['homepage'] = func::filterJavascript($xmls->getAttribute('/export/information/author[lang()]', 'link'));
	$exportInfo['status'] = Validator::getBool($db->queryCell("SELECT status FROM {$database['prefix']}Exports WHERE domain='{$domainName}'"));

	$exportInfo['tags'] = array();
	$sNode = $xmls->selectNode('/export/binding');
	if(isset($sNode['tag'])) {
		foreach($sNode['tag'] as $tag) {
			array_push($exportInfo['tags'], '[##_'.$tag['.attributes']['name'].'_##]');
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo $exportInfo['title'];?></title>
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/common.css" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/admin.css" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/admin_plugin.css" />
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/common.js"></script>
<script type="text/javascript">
	function saveConfig() {
		var eTypes = [];
		var eValues = [];
		var query = '';

		$('#configForm :input').each( function() {
			if (this.name && (this.value !== false) && !this.disabled) {
				if(this.type=='radio' && this.checked == false) { 
				} else {
					eTypes.push(this.type);
					if(this.type=='checkbox') {
						eValues.push('_'+encodeURIComponent(this.name) + '=' + encodeURIComponent((this.checked==true?'true':'false')));
					} else {
						eValues.push('_'+encodeURIComponent(this.name) + '=' + encodeURIComponent(this.value));
					}
				}				
			}		
		});
		
		var query = eValues.join('&');
		var queryTypes = '&fieldTypes=' + eTypes.join('|');
		$.ajax({
			  type: "POST",
			  url: '<?php echo $service['path'];?>/service/export/save.php',
			  data: 'domainName=<?php echo $domainName;?>'+queryTypes+'&'+query,				  
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {					
					parent.addMessage("<?php echo _t('프로그램 설정을 수정하였습니다.');?>");
					parent.hideExportConfig('<?php echo $domainName;?>');
				} else {
					alert($("response message", msg).text());
				}
			  },
			  error: function(msg) {
				 alert('unknown error');
			  }
			});	

	}

	$(function() {
		var buttonHeight = $("#export_config_button_wrap").height() + parseInt($("#export_config_button_wrap").css('padding-top')) + parseInt($("#export_config_button_wrap").css('padding-bottom'));

		if($(document.body).height()==0) {
			var height = $("#export_wrap").height() + buttonHeight;
			$(document.body).height(height);
		} else {
			$("#export_config_wrap").height($(document.body).height() - buttonHeight);
			var h = $("#export_information_wrap").height();
			if(h<$("#export_config_wrap").height()) {
				h = $("#export_config_wrap").height();
			}
			var height = h + buttonHeight;
		}
		
		parent.resizeExportConfig('<?php echo $domainName;?>',height);
	});
</script>
</head>
<body class="export_body">
<?php
		$exportConfig = new XMLStruct;
		$exportSettings = array();
		$exportSettingsStr = $db->queryCell("SELECT settings FROM {$database['prefix']}Exports WHERE domain='{$domainName}'");
		if ($exportConfig->open($exportSettingsStr)) {
			$exportConfigs = $exportConfig->selectNode('/config');
			if(isset($exportConfigs['field'])) {
				foreach ($exportConfigs['field'] as $field) {
					$name = $field['.attributes']['name'];
					$type = $field['.attributes']['type'];
					$value = $field['.value'];

					$exportSettings[$name] = array();
					$exportSettings[$name]['value'] = $value;
					$exportSettings[$name]['type'] = $type;
				}
			}	
		}

?>
<div id="export_wrap">
<div id="export_information_wrap">
			<fieldset>
				<legend><?php echo _t('정보');?></legend><br class="margin" />
					<dl>
						<dt><?php echo _t('버전');?> :</dt>
						<dd>
							<?php echo $exportInfo['version'];?>
						</dd>
					</dl>
<?php if(!empty($exportInfo['email'])) { ?>
					<div class="clear"></div>
					<dl>
						<dt><?php echo _t('이메일주소');?> :</dt>
						<dd>
							<a href="mailto:<?php echo $exportInfo['email'];?>"><?php echo $exportInfo['email'];?></a>
						</dd>
					</dl>
<?php } ?>
<?php if(!empty($exportInfo['homepage'])) { ?>
					<div class="clear"></div>
					<dl>
						<dt><?php echo _t('홈페이지');?> :</dt>
						<dd>
							<a href="<?php echo $exportInfo['homepage'];?>" target="_blank"><?php echo $exportInfo['homepage'];?></a>
						</dd>
					</dl>
<?php } ?>	
				<div class="clear"></div>
				<dl>
					<dt><?php echo _t('설명');?> :</dt>
					<dd>
						<?php echo $exportInfo['description'];?>
					</dd>
				</dl>	

				<div class="clear"></div>
				<dl>
					<dt><?php echo _t('라이센스');?> :</dt>
					<dd>
						<?php echo $exportInfo['license'];?>
					</dd>
				</dl>		

				<div class="clear"></div>
				<dl>
					<dt><?php echo _t('위치');?> :</dt>
					<dd>
						<?php echo _f('이 프로그램은 %1 에 설치되어 있습니다.', htmlspecialchars($service['path'].'/exports/'.$exportInfo['program']));?>
					</dd>
				</dl>
<?php
	if(count($exportInfo['tags']) > 0) {
?>
				<div class="clear"></div>
				<dl>
					<dt><?php echo _t('치환자');?> :</dt>
					<dd>
						<?php echo _f('사용하는 스킨에 %1 이 포함되어 있어야 합니다.', '<span class="strong">'.implode(', ', $exportInfo['tags']).'</span>');?>
					</dd>
				</dl>	
<?php
	}
?>
</div>

<div id="export_config_wrap">
	<div class="export_config_container">

		<div class="export_config_description">
<?php
		if (!isset($exportInfo['config']['fieldset'])) { // 설정이 없는데 이 파일이 호출된거면
?>
			<fieldset>
				<legend><?php echo _t('설정');?></legend><br class="margin" />
				<div class="export_config_empty">
					<?php echo _t('설정할 수 있는 항목이 없습니다');?>
				</div>
			</fieldset>
<?php
		}  else {
?>
			<form id="configForm" onsubmit="saveConfig(); return false;">
<?php
			for($i=0;$i<count($exportInfo['config']['fieldset']);$i++) {
			$fieldset = $exportInfo['config']['fieldset'][$i];
?>
			<fieldset<?php echo ($i==count($exportInfo['config']['fieldset'])-1)?' class="lastChild"':'';?>>
				<legend><?php echo $fieldset['.attributes']['legend'];?></legend><br class="margin" />
<?php
					foreach ($fieldset['field'] as $field) {
						if (!isset($field['.attributes']) || empty($field['.attributes'])) continue;
						$attributes = $field['.attributes'];
						$pName = $attributes['name'];
						$pConfig = (isset($exportSettings[$pName]) && is_array($exportSettings[$pName])) ? $exportSettings[$pName] : $field;
?>
				<div class="field">
					<dl>
						<dt><?php echo $attributes['title']; ?></dt>
						<dd>
<?php
							switch (strtolower($attributes['type'])) {
								case 'blog':
									$feeds = Feed::getFeedsAll();
?>
								<select id="<?php echo $attributes['name'];?>blog" name="<?php echo $attributes['name'];?>" style="width:320px;" class="select">
<?php
									foreach($feeds as $feed) {
?>
										<option value="<?php echo $feed['id'];?>"<?php echo $feed['id'] == $pConfig['value'] ? ' selected="selected"':'';?>><?php echo $feed['title'];?></option>
<?php
									}
?>									
								</select>
<?php
								break;
								case 'category':
									$categories = Category::getCategoriesAll();
?>
								<select id="<?php echo $attributes['name'];?>category" name="<?php echo $attributes['name'];?>" style="width:320px;" class="select">
<?php
									foreach($categories as $category) {
?>
										<option value="<?php echo $category['id'];?>"<?php echo $category['id'] == $pConfig['value'] ? ' selected="selected"':'';?>><?php echo $category['name'];?></option>
<?php
									}
?>									
								</select>
<?php
								break;
								case 'textarea':
									$presetValue = (($pConfig['type'] == 'textarea') && !empty($pConfig['value'])) ? trim($pConfig['value']) : trim($field['.value']);
?>
			<textarea id="<?php echo $attributes['name'];?>textarea" name="<?php echo $attributes['name'];?>" class="textarea" style="width:<?php echo $attributes['width'];?>px; height:<?php echo $attributes['height'];?>px;"/><?php if (!empty($presetValue))  {  echo $presetValue; } ?></textarea>
<?php
								break;
								case 'radio':
									$presetValue = (($pConfig['type'] == 'radio') && !empty($pConfig['value'])) ? trim($pConfig['value']) : ((isset($pConfig['.attributes']['value']) && !empty($pConfig['.attributes']['value'])) ? $pConfig['.attributes']['value'] : '');
									foreach ($field['option'] as $option) {
									$ui = uniqid(mt_rand());
?>
			<input type="radio" name="<?php echo $attributes['name'];?>" id="<?php echo $attributes['name'].$ui;?>" value="<?php echo $option['.attributes']['value'];?>" <?php if (($option['.attributes']['value'] == $presetValue) || (isset($option['.attributes']['checked']) && ($option['.attributes']['value']=='checked'))) { ?>checked="checked"<?php } ?>/><label for="<?php echo $attributes['name'].$ui;?>">&nbsp;<?php echo $option['.value'];?></label>&nbsp;
<?php
									}
									break;
								case 'checkbox':
									$presetValue = (($pConfig['type'] == 'checkbox') && !empty($pConfig['value'])) ? trim($pConfig['value']) : ((isset($pConfig['.attributes']['value']) && !empty($pConfig['.attributes']['value'])) ? $pConfig['.attributes']['value'] : '');
?>
			<input type="checkbox" id="<?php echo $attributes['name'];?>checkbox" name="<?php echo $attributes['name'];?>" class="checkbox" <?php echo $pConfig['value']=='true'?' checked="checked"':'';?> /><label for="<?php echo $attributes['name'];?>checkbox">&nbsp;<?php echo $attributes['value'];?>&nbsp;</label>
<?php
									break;
								case 'select':
								$presetValue = ((strpos($pConfig['type'], 'select') !== false) && !empty($pConfig['value'])) ? trim($pConfig['value']) : ((isset($pConfig['.attributes']['value']) && !empty($pConfig['.attributes']['value'])) ? $pConfig['.attributes']['value'] : '');
?>
	<select id="<?php echo $attributes['name'];?>select" name="<?php echo $attributes['name'];?>" class="select">
<?php
									foreach ($field['option'] as $option) {
?>
		<option value="<?php echo $option['.attributes']['value'];?>" <?php if (($option['.attributes']['value'] == $presetValue) || (isset($option['.attributes']['checked']) && ($option['.attributes']['value']=='checked'))) { ?>selected="selected"<?php } ?>><?php echo $option['.value'];?></option>
<?php
									}
?>
	</select>
<?php
									break;

								default:
								case 'text':
									$option = isset($field['option'])?$field['option']:null;
									$presetValue = (($pConfig['type'] == 'text') && !empty($pConfig['value'])) ? trim($pConfig['value']) : ((isset($option['.attributes']['value']) && !empty($option['.attributes']['value'])) ? $option['.attributes']['value'] : '');
?>
		<input type="text" id="<?php echo $attributes['name'];?>text" name="<?php echo $attributes['name'];?>" class="text" value="<?php echo $presetValue;?>"/>
<?php
									break;
							}
?>
						</dd>
					</dl>	

<?php 
						if (isset($field['caption'])) {
?>
					<div class="caption">
						<?php echo $field['caption'][0]['.value']; ?>
					</div>
<?php			
						} 
?>					<div class="clear"></div>
				</div>
<?php
					} // end each field
?>
			</fieldset>
<?php
				} // end each fieldset
} ?>
		</form>
		</div>
	</div>
</div>
<div class="clear"></div>
</div>

<div id="export_config_button_wrap">
	<?php if (!is_null($exportInfo['config'])) { ?><a href="#" class="normalbutton" onclick="saveConfig(); return false;"><span class="boldbutton"><?php echo _t('수정완료');?></span></a>&nbsp;<?php } ?>
	<a href="#" class="normalbutton" onclick="parent.hideExportConfig('<?php echo $domainName;?>'); return false;"><span><?php echo _t('닫기');?></span></a>
</div>	

</body>
</html>