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
	$pluginInfo['title'] = $xmls->getValue('/plugin/information/name[lang()]');
	$pluginInfo['config'] = $xmls->selectNode('/plugin/config[lang()]');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo $pluginInfo['title'];?></title>
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/common.css" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/modal.css" />
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
			  url: '<?php echo $service['path'];?>/service/plugin/save.php',
			  data: 'pluginName=<?php echo $pluginName;?>'+queryTypes+'&'+query,				  
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					parent.hideModal();
				} else {
					alert($("response message", msg).text());
				}
			  },
			  error: function(msg) {
				 alert('unknown error');
			  }
			});	

	}

	$(window).ready( function() {
		parent.$("#pluginDetailFrame").height($(document.body).height());
	});

</script>
</head>
<body>
<?php
		$plugConfig = new XMLStruct;
		$plugSettings = array();
		$plugSettingsStr = $db->queryCell("SELECT settings FROM {$database['prefix']}Plugins WHERE name='{$pluginName}'");
		if ($plugConfig->open($plugSettingsStr)) {
			$pluginConfigs = $plugConfig->selectNode('/config');
			if(isset($pluginConfigs['field'])) {
				foreach ($pluginConfigs['field'] as $field) {
					$name = $field['.attributes']['name'];
					$type = $field['.attributes']['type'];
					$value = $field['.value'];

					$plugSettings[$name] = array();
					$plugSettings[$name]['value'] = $value;
					$plugSettings[$name]['type'] = $type;
				}
			}	
		}
?>
	<div class="modal_title">
		<?php echo $pluginInfo['title'];?> <?php echo _t('설정');?>
	</div>
	<div class="modal_container">
		<ul class="modal_tabs">
			<li><a href="<?php echo $service['path'];?>/admin/plugin/list/info/?pluginName=<?php echo $pluginInfo['name'];?>"><?php echo _t('이 플러그인에 대해');?></a></li>
<?php
			if (!is_null($pluginInfo['config'])) { 
?>
			<li class="selected"><a href="<?php echo $service['path'];?>/admin/plugin/list/config/?pluginName=<?php echo $pluginInfo['name'];?>"><?php echo _t('플러그인 설정');?></a></li>
<?php
			}
?>

		</ul>
		<div class="clear"></div>

		<div class="modal_description">
<?php
		if (!isset($pluginInfo['config']['fieldset'])) { // 설정이 없는데 이 파일이 호출된거면
?>
			<div class="modal_empty">
				<?php echo _t('설정할 수 있는 항목이 없습니다');?>
			</div>
<?php
		}  else {
?>
			<form id="configForm" onsubmit="saveConfig(); return false;">
<?php
			for($i=0;$i<count($pluginInfo['config']['fieldset']);$i++) {
			$fieldset = $pluginInfo['config']['fieldset'][$i];
?>
			<fieldset<?php echo ($i==count($pluginInfo['config']['fieldset'])-1)?' class="lastChild"':'';?>>
				<legend><?php echo $fieldset['.attributes']['legend'];?></legend>
<?php
					foreach ($fieldset['field'] as $field) {
						if (!isset($field['.attributes']) || empty($field['.attributes'])) continue;
						$attributes = $field['.attributes'];
						$pName = $attributes['name'];
						$pConfig = (isset($plugSettings[$pName]) && is_array($plugSettings[$pName])) ? $plugSettings[$pName] : $field;
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
								<select name="<?php echo $attributes['name'];?>" style="width:320px;">
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
								case 'textarea':
									$presetValue = (($pConfig['type'] == 'textarea') && !empty($pConfig['value'])) ? trim($pConfig['value']) : trim($field['.value']);
?>
			<textarea name="<?php echo $attributes['name'];?>" class="textarea" style="width:<?php echo $attributes['width'];?>px; height:<?php echo $attributes['height'];?>px;"/><?php if (!empty($presetValue))  {  echo $presetValue; } ?></textarea>
<?php
								break;
								case 'radio':
									$presetValue = (($pConfig['type'] == 'radio') && !empty($pConfig['value'])) ? trim($pConfig['value']) : '';
									foreach ($field['option'] as $option) {
									$ui = uniqid(mt_rand());
?>
			<input type="radio" name="<?php echo $attributes['name'];?>" id="<?php echo $attributes['name'].$ui;?>" value="<?php echo $option['.attributes']['value'];?>" <?php if (($option['.attributes']['value'] == $presetValue) || (isset($option['.attributes']['checked']) && ($option['.attributes']['value']=='checked'))) { ?>checked="checked"<?php } ?>/><label for="<?php echo $attributes['name'].$ui;?>">&nbsp;<?php echo $option['.value'];?></label>&nbsp;
<?php
									}
									break;
								case 'checkbox':
									$presetValue = (($pConfig['type'] == 'checkbox') && !empty($pConfig['value'])) ? trim($pConfig['value']) : '';
?>
			<input type="checkbox" name="<?php echo $attributes['name'];?>" id="<?php echo $attributes['name'];?>checkbox" <?php echo $pConfig['value']=='true'?' checked="checked"':'';?> /><label for="<?php echo $attributes['name'];?>checkbox">&nbsp;<?php echo $attributes['value'];?>&nbsp;</label>
<?php
									break;
								case 'select':
								$presetValue = ((strpos($pConfig['type'], 'select') !== false) && !empty($pConfig['value'])) ? trim($pConfig['value']) : '';
?>
	<select name="<?php echo $attributes['name'];?>">
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
		<input type="text" name="<?php echo $attributes['name'];?>" value="<?php echo $presetValue;?>" class="input"/>
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
?>
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
	<div class="modalclose_wrap">
		<?php if (!is_null($pluginInfo['config'])) { ?><a href="#" onclick="saveConfig(); return false;"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_modify.gif" alt="<?php echo _t('수정완료');?>" /></a>&nbsp;<?php } ?>
		<a href="#" onclick="parent.hideModal(); return false;"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_close.gif" alt="<?php echo _t('닫기');?>" /></a>
	</div>
</body>
</html>