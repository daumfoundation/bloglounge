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
			if (($('#useFilter').attr('checked') == true) && ($('#filter').val().length == 0)){
				alert('<?php echo _t('태그 또는 분류명을 지정해주세요');?>');
				$('#filter').focus();
				return false;
			}

			if (($('#useBlackFilter').attr('checked') == true) && ($('#blackfilter').val().length == 0)){
				alert('<?php echo _t('태그 또는 분류명을 지정해주세요');?>');
				$('#blackfilter').focus();
				return false;
			}

			var filter = '';
			var blackfilter = '';
			if ($('#useFilter').attr('checked'))  filter = $('#filter').val();
			if ($('#useBlackFilter').attr('checked'))  blackfilter = $('#blackfilter').val();
		
			$.ajax({
			  type: "POST",
			  url: _path +'/service/setting/save.php',
			  data: "filter="+encodeURIComponent(filter)+"&blackfilter="+encodeURIComponent(blackfilter),
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
	<h3><?php echo _t("블라인드");?></h3>
</div>

<div class="wrap setting_wrap">
	<h4><?php echo _t('태그필터');?></h4>
	<div class="setting">
		<div class="borderbox">
			<?php echo _t('지나치게 많은 단어의 지정은 수집 효율과 동작 속도를 저하시킬 수 있습니다.');?>
		</div>

		<div class="setting_data">
			<input type="checkbox" name="useFilter" id="useFilter" value="y" <?php if ($config->filter) {?>checked="checked"<?php } ?>/><label for="useFilter">&nbsp;<?php echo _t('모든 피드로부터 지정한 태그 또는 분류의 글만 수집합니다');?></label>

			<div class="textarea_wrap">
				<textarea name="filter" id="filter" class=""><?php echo htmlspecialchars($config->filter);?></textarea>			
				<div class="help"><?php echo _t('수집하고자 하는 단어를 지정할 수 있으며, 각 단어의 구분은 쉼표(,)로 합니다.');?></div>
			</div>
		</div>
		<div class="setting_data">
			<input type="checkbox" name="useBlackFilter" id="useBlackFilter" value="y" <?php if ($config->blackfilter) {?>checked="checked"<?php } ?>/><label for="useBlackFilter">&nbsp;<?php echo _t('모든 피드로부터 지정한 태그 또는 분류의 글을 수집하지 않습니다');?></label>

			<div class="textarea_wrap">
				<textarea name="blackfilter" id="blackfilter" class=""><?php echo htmlspecialchars($config->blackfilter);?></textarea>
				<div class="help"><?php echo _t('수집을 차단하고자 하는 단어를 지정할 수 있으며, 각 단어의 구분은 쉼표(,)로 합니다.');?></div>
			</div>
		</div>	

		<br />

		<div class="button_wrap">
				<a href="#" onclick="saveSettings(); return false;"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_modify.gif" alt="<?php echo _t('수정완료');?>" /></a>
		</div>
	</div>

</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
