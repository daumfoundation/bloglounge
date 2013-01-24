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
		var filter = $('#filter');
		var blackFilter = $('#blackfilter');

		if ((!document.getElementsByName('useFilter')[0].checked) && (filter.val().length == 0)){
			alert('<?php echo _t('필터 역할을 할 단어를 입력해주세요.');?>');
			filter.focus();
			return false;
		}

		if ((!document.getElementsByName('useBlackFilter')[0].checked)  && (blackFilter.val().length == 0)){
			alert('<?php echo _t('필터 역할을 할 단어를 입력해주세요.');?>');
			blackFilter.focus();
			return false;
		}

		var filterValue = '';
		var blackfilterValue = '';
		var filterType = $(":input:radio[name=useFilter]:checked").val();
		var blackfilterType = $(":input:radio[name=useFilter]:checked").val();

		if (!document.getElementsByName('useFilter')[0].checked)  filterValue = filter.val();
		if (!document.getElementsByName('useBlackFilter')[0].checked)  blackfilterValue = blackFilter.val();
		
		$.ajax({
		  type: "POST",
		  url: _path +'/service/setting/save.php',
		  data: "filter="+encodeURIComponent(filterValue)+"&filterType="+encodeURIComponent(filterType)+"&blackfilter="+encodeURIComponent(blackfilterValue)+"&blackfilterType="+encodeURIComponent(blackfilterType),
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
	<h4><?php echo _t('필터');?></h4>
	<div class="setting">
		<div class="borderbox">
			<?php echo _t('지나치게 많은 단어의 지정은 수집 효율과 동작 속도를 저하시킬 수 있습니다.');?>
		</div>

		<div class="setting_data">
			<h6><?php echo _t('수집 허용');?></h6>
			<input type="radio" name="useFilter" id="useFilterNone" value="none" <?php if(empty($config->filter)) {?>checked="checked"<?php } ?>/><label for="useFilterNone">&nbsp;<?php echo _t('이 기능을 사용하지 않습니다.');?></label><br />
			<input type="radio" name="useFilter" id="useFilterTag" value="tag" <?php if (!empty($config->filter) && ($config->filterType == 'tag')) {?>checked="checked"<?php } ?>/><label for="useFilterTag">&nbsp;<?php echo _t('모든 피드로부터 지정한 단어를 태그에 포함하고 있는 글만 수집합니다.');?></label><br />
			<input type="radio" name="useFilter" id="useFilterTitle" value="titile" <?php if (!empty($config->filter) && ($config->filterType == 'titile')) {?>checked="checked"<?php } ?>/><label for="useFilterTitle">&nbsp;<?php echo _t('모든 피드로부터 지정한 단어를 제목에 포함하고 있는 글만 수집합니다.');?></label><br />
			<input type="radio" name="useFilter" id="useFilterTagTitle" value="tag+title" <?php if (!empty($config->filter) && ($config->filterType == 'tag+title')) {?>checked="checked"<?php } ?>/><label for="useFilterTagTitle">&nbsp;<?php echo _t('모든 피드로부터 지정한 단어를 태그 또는 제목에 포함하고 있는 글만 수집합니다.');?></label>

			<div class="textarea_wrap">
				<textarea name="filter" id="filter" class="" onfocus="if(document.getElementsByName('useFilter')[0].checked) document.getElementsByName('useFilter')[1].checked=true;"><?php echo htmlspecialchars($config->filter);?></textarea>			
				<div class="help"><?php echo _t('수집하고자 하는 단어를 지정할 수 있으며, 각 단어의 구분은 쉼표(,)로 합니다.');?></div>
			</div>
		</div>
		<div class="setting_data">
			<h6><?php echo _t('수집 제한');?></h6>
			<input type="radio" name="useBlackFilter" id="useBlackFilterNone" value="none" <?php if(empty($config->blackfilter)) {?>checked="checked"<?php } ?>/><label for="useBlackFilterNone">&nbsp;<?php echo _t('이 기능을 사용하지 않습니다.');?></label><br />
			<input type="radio" name="useBlackFilter" id="useBlackFilterTag" value="tag" <?php if (!empty($config->blackfilter) && ($config->blackfilterType == 'tag')) {?>checked="checked"<?php } ?>/><label for="useBlackFilterTag">&nbsp;<?php echo _t('모든 피드로부터 지정한 단어를 태그에 포함하고 있는 글을 수집하지 않습니다.');?></label><br />
			<input type="radio" name="useBlackFilter" id="useBlackFilterTitle" value="titile" <?php if (!empty($config->blackfilter) && ($config->blackfilterType == 'titile')) {?>checked="checked"<?php } ?>/><label for="useBlackFilterTitle">&nbsp;<?php echo _t('모든 피드로부터 지정한 단어를 제목에 포함하고 있는 글을 수집하지 않습니다.');?></label><br />
			<input type="radio" name="useBlackFilter" id="useBlackFilterTagTitle" value="tag+title" <?php if (!empty($config->blackfilter) && ($config->blackfilterType == 'tag+title')) {?>checked="checked"<?php } ?>/><label for="useBlackFilterTagTitle">&nbsp;<?php echo _t('모든 피드로부터 지정한 단어를 태그 또는 제목에 포함하고 있는 글을 수집하지 않습니다.');?></label>

			<div class="textarea_wrap">
				<textarea name="blackfilter" id="blackfilter" class="" onfocus="if(document.getElementsByName('useBlackFilter')[0].checked) document.getElementsByName('useBlackFilter')[1].checked=true;"><?php echo htmlspecialchars($config->blackfilter);?></textarea>
				<div class="help"><?php echo _t('수집을 차단하고자 하는 단어를 지정할 수 있으며, 각 단어의 구분은 쉼표(,)로 합니다.');?></div>
			</div>
		</div>	

		<br />

		<div class="button_wrap">
			<a href="#" class="normalbutton" onclick="saveSettings(); return false;"><span class="boldbutton"><?php echo _t('수정완료');?></span></a>
		</div>
	</div>

</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>