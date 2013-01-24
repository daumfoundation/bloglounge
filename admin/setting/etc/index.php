<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	include ROOT. '/lib/piece/adminHeader.php';
	
	$config = new Settings;
?>

<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_setting.css" type="text/css" />
<script type="text/javascript">		

	function saveSettings() {
			
			$.ajax({
			  type: "POST",
			  url: _path +'/service/setting/save.php',
			  data: "countRobotvisit=" + ($('#countRobotVisit').attr('checked')?'y':'n') +
							"&language="+ encodeURIComponent($('#language').val()) +
							"&welcomePack="+ encodeURIComponent($('#welcomePack').val()),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					addMessage("<?php echo _t('수정 완료했습니다.');?>");
				} else {
					alert($("response message", msg).text());
				}
			  },
			  error: function(msg) {
				 alert('unknown error');
			  }
			});
		};
</script>

<div class="wrap title_wrap">
	<h3><?php echo _t("그외설정");?></h3>
</div>

<div class="wrap setting_wrap">
	<h4><?php echo _t('세부설정');?></h4>
	<div class="setting">

		<dl class="normal">
			<dt><?php echo _t('기능');?></dt>
			<dd>
				<input type="checkbox" name="countRobotVisit" id="countRobotVisit" <?php if ($config->countRobotVisit == 'y') {?>checked="checked"<?php } ?> />&nbsp;<label for="countRobotVisit"><?php echo _t('검색엔진 수집 로봇의 방문 회수를 통계에 포함하지 않습니다.');?></label>
			</dd>
		</dl>	
		<dl class="normal comments last_item">
			<dt></dt>
			<dd class="text checkbox_hint">
				<?php echo _t('이 설정을 선택하면 사람이 아닌 기계적인 방법으로 접근한 방문은 통계에 포함하지 않습니다.');?>
			</dd>
		</dl>

		<dl class="normal">
			<dt><?php echo _t('문장');?></dt>
			<dd>
<?php
				ob_start();
?>
				<select name="language" id="language">
					<option value="ko"><?php echo _t('한국어');?></option>
				</select>
<?php
				$arg = ob_get_contents();
				ob_end_clean();

				echo _f('언어 : %1', $arg);
?>
			</dd>
		</dl>	
		<dl class="normal comments">
			<dt></dt>
			<dd class="text">
				<?php echo _t('전체에서 사용되는 언어구성을 설정합니다.');?>	
			</dd>
		</dl>

		<dl class="normal">
			<dt></dt>
			<dd>
<?php
				ob_start();
?>
				<select name="welcomePack" id="welcomePack">
<?php
					$xmls=new XMLStruct();
					$dir = opendir(ROOT . '/language/welcome');
					while ($file = readdir($dir)) {
						if (func::getExt($file)=='xml') {
							$filename = substr($file, 0, strpos($file, '.xml'));
							$xmls->openFile(ROOT . '/language/welcome/'.$file);
							$name = $xmls->getValue('/welcome/information/name');
							$author = $xmls->getValue('/welcome/information/author/name');
?>
					<option value="<?php echo $filename;?>" <?php if ($filename == $config->welcomePack){?>selected="selected"<?php } ?>><?php echo $name;?> (<?php echo $author;?>)</option>
<?php
						}
					} 
?>
				</select>
<?php
				$arg = ob_get_contents();
				ob_end_clean();

				echo _f('인사말 모음 : %1', $arg);
?>
			</dd>
		</dl>	
		<dl class="normal comments">
			<dt></dt>
			<dd class="text">
				<?php echo _t('인사말 치환자에 사용되는 문장을 정의할 수 있습니다.');?>	
			</dd>
		</dl>


		<br />

		<div class="button_wrap">
			<a href="#" class="normalbutton" onclick="saveSettings(); return false;"><span class="boldbutton"><?php echo _t('수정완료');?></span></a>
		</div>
	</div>

</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
