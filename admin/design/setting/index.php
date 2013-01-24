<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	include ROOT. '/lib/piece/adminHeader.php';	
	$skinConfig = new SkinSettings;

	// 현재 사용중인 스킨
	$n_skinname = Settings::get('metaskin');
	$n_skinpath = ROOT . '/skin/meta/'.$n_skinname;
	
	if(!file_exists($n_skinpath.'/index.xml')) {
?>
	<div class="accept_wrap wrap">
			<?php echo drawGrayBoxBegin();?>	
				<div class="accept_messages">
					<?php echo _t('현재 사용중인 스킨이 없습니다.');?>
				</div>
			<?php echo drawGrayBoxEnd();?>
	</div>
<?php
	} else {

	$xml = file_get_contents($n_skinpath.'/index.xml');
	$xmls = new XMLStruct();
	$xmls->open($xml);
?>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_design.css" type="text/css" />
<script type="text/javascript">
	function saveSkinSettings() {		
		$.ajax({
		  type: "POST",
		  url: _path +'/service/design/setting.php',
		  data: "postList="+$('#postList').val()+
				"&postTitleLength="+$('#postTitleLength').val()+
				"&postDescLength="+$('#postDescLength').val()+
				"&postNewLife="+$('#postNewLife').val()+
				"&feedList="+$('#feedList').val()+
				"&feedOrder="+$('#feedOrder').val()+
				"&feedTitleLength="+$('#feedTitleLength').val()+
				"&boomList="+$('#boomList').val()+
				"&boomTitleLength="+$('#boomTitleLength').val()+
				"&feedListPage="+$('#feedListPage').val()+
				"&feedListPageOrder="+$('#feedListPageOrder').val()+
				"&feedListPageTitleLength="+$('#feedListPageTitleLength').val()+
				"&feedListRecentFeedList="+$('#feedListRecentFeedList').val()+			  
				"&focusList="+$('#focusList').val()+
				"&focusTitleLength="+$('#focusTitleLength').val()+
				"&focusDescLength="+$('#focusDescLength').val()+
				"&tagCloudOrder="+$('#tagCloudOrder').val()+
				"&tagCloudLimit="+$('#tagCloudLimit').val(),
		  dataType: 'xml',
		  success: function(msg){		
			error = $("response error", msg).text();
			if(error == "0") {
				addMessage("<?php echo _t('출력설정을 수정하였습니다.');?>");
			} else {
				alert($("response message", msg).text());
			}
		  },
		  error: function(msg) {
			 alert('unknown error');
		  }
		});
	}
</script>

<div class="wrap title_wrap">
	<h3><?php echo _t("스킨설정");?></h3>
</div>

<div class="wrap skin_setting_wrap">

<div class="now_skin">
	<div class="listbox">
		<div class="title">
			<a href="<?php echo $service['path'];?>/admin/design/meta/"><?php echo _t('사용중인 스킨');?></a>
		</div>
		<div class="data">
			<div class="thumbnail">			
				<img src="<?php echo $n_skinpath;?>/preview.gif" alt="<?php echo _t('미리보기 이미지');?>" />
			</div>
			<div class="desc">
				<?php echo $xmls->getValue('/skin/information/name[lang()]');?> <span class="version"><?php echo $xmls->getValue('/skin/information/version');?></span> <span class="name">(<?php echo $n_skinname;?>)</span>
			</div>
			<div class="clear"></div>
		</div>
	</div>
	<div class="shadow"></div>
	
	<div class="borderbox">
		<a href="<?php echo $service['path'];?>/admin/design/meta/"><?php echo _t('스킨을 변경하시려면 이곳을 클릭하세요.');?></a>
	</div>

</div>

<div class="skin_setting">			
	<h4><?php echo _t('출력설정 수정');?></h4>
	<h5><?php echo _t('현재 사용중인 스킨의 출력설정을 수정합니다.');?></h5>

	<dl class="normal">
		<dt><?php echo _t('블로그');?></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="feedOrder">
			<option value="created" <?php if ($skinConfig->feedOrder == 'created') { ?>selected="selected"<?php } ?>><?php echo _t('최근에 등록된');?></option>
			<option value="lastUpdate" <?php if ($skinConfig->feedOrder == 'lastUpdate') { ?>selected="selected"<?php } ?>><?php echo _t('최근에 글을 수집한');?></option>
		</select>
<?php
			$arg1 = ob_get_contents();
			ob_end_clean();
			ob_start();
?>
		<select id="feedList">
<?php
			for ($i=5; $i <= 60; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->feedList) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
			} 
?>
		</select>
<?php
			$arg2 = ob_get_contents();
			ob_end_clean();
			echo _f('등록된 블로그를 %1 순서로 %2 개 보여줍니다', $arg1, $arg2);
			unset($arg1); unset($arg2);
?>
		</dd>
	</dl>

	<dl class="normal last_item">
		<dt></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="feedTitleLength">
<?php
			for ($i=3; $i <= 120; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->feedTitleLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
			} 
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('블로그 제목을 %1자 만큼 보여줍니다', $arg);
?>
		</dd>
	</dl>


	<dl class="normal">
		<dt><?php echo _t('인기글');?></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="boomList">
<?php
			for ($i=5; $i <= 60; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->boomList) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
			}
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('인기글을 %1개 보여줍니다', $arg);
?>
		</dd>
	</dl>

	<dl class="normal last_item">
		<dt></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="boomTitleLength">
<?php
			for ($i=3; $i <= 120; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->boomTitleLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php	
			} 
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('인기글 제목을 %1자 만큼 보여줍니다', $arg);
?>
		</dd>
	</dl>


	<dl class="normal last_item">
		<dt><?php echo _t('태그');?></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="tagCloudOrder">
			<option value="name" <?php if ($skinConfig->tagCloudOrder == 'name') { ?>selected="selected"<?php } ?>><?php echo _t('가나다');?></option>
			<option value="frequency" <?php if ($skinConfig->tagCloudOrder == 'frequency') { ?>selected="selected"<?php } ?>><?php echo _t('인기많은');?></option>
			<option value="random" <?php if ($skinConfig->tagCloudOrder == 'random') { ?>selected="selected"<?php } ?>><?php echo _t('무작위');?></option>
		</select>
<?php
			$arg1 = ob_get_contents();
			ob_end_clean();
			ob_start();
?>
		<select id="tagCloudLimit">
<?php
			for ($i=5; $i <= 50; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->tagCloudLimit) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php 
			} 
?>
		</select>
<?php
			$arg2 = ob_get_contents();
			ob_end_clean();
			echo _f('태그 상자에 태그를 %1 순서로 %2 개 보여줍니다', $arg1, $arg2);
			unset($arg1); unset($arg2);
?>
		</dd>
	</dl>
	
	<dl class="normal">
		<dt><?php echo _t('포커스');?></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="focusList">
<?php
			for ($i=1; $i <= 20; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->focusList) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php		
			} 
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('포커스를 %1 개 보여줍니다.', $arg);
?>
		</dd>
	</dl>
	
	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="focusTitleLength">
<?php
			for ($i=5; $i <= 100; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->focusTitleLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php	
			} 
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('포커스 제목을 %1 자 만큼 보여줍니다.', $arg);
?>
		</dd>
	</dl>
	
	<dl class="normal last_item">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="focusDescLength">
<?php
			for ($i=20; $i <= 1000; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->focusDescLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php 
			} 
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('포커스 본문을 %1 자 만큼 보여줍니다.', $arg);
?>
		</dd>
	</dl>

	<dl class="normal">
		<dt><?php echo _t('글');?></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="postList">
<?php
			for ($i=1; $i <= 30; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->postList) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
		}
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('등록된 글을 페이지당 %1개씩 보여줍니다', $arg);
?>
		</dd>
	</dl>

	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="postTitleLength">
<?php
			for ($i=3; $i <= 120; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->postTitleLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
			}
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('글 제목을  %1자 만큼 보여줍니다', $arg);
?>
		</dd>
	</dl>

	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="postDescLength">
<?php
			for ($i=20; $i <= 1000; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->postDescLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php 
			} 
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('글 본문을  %1자 만큼 보여줍니다', $arg);
?>
		</dd>
	</dl>

	<dl class="normal last_item">
		<dt></dt>
		<dd>
<?php
			ob_start();
?> 
		<select id="postNewLife">
			<option value="1" <?php if ($skinConfig->postNewLife == 1) { ?>selected="selected"<?php } ?>>1</option>
			<option value="3" <?php if ($skinConfig->postNewLife == 3) { ?>selected="selected"<?php } ?>>3</option>
			<option value="6" <?php if ($skinConfig->postNewLife == 6) { ?>selected="selected"<?php } ?>>6</option>
			<option value="12" <?php if ($skinConfig->postNewLife == 12) { ?>selected="selected"<?php } ?>>12</option>
			<option value="24" <?php if ($skinConfig->postNewLife == 24) { ?>selected="selected"<?php } ?>>24</option>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('작성된 지 %1시간 이내의 글에 새 글 표시합니다', $arg);
?>
		</dd>
	</dl>

	<dl class="normal">
		<dt><?php echo _t('블로그');?></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="feedListPageOrder">
			<option value="created" <?php if ($skinConfig->feedListPageOrder == 'created') { ?>selected="selected"<?php } ?>><?php echo _t('최근에 등록된');?></option>
			<option value="lastUpdate" <?php if ($skinConfig->feedListPageOrder == 'lastUpdate') { ?>selected="selected"<?php } ?>><?php echo _t('최근에 글을 수집한');?></option>
		</select>
<?php
			$arg1 = ob_get_contents();
			ob_end_clean();
			ob_start();
?>
		<select id="feedListPage">
<?php
			for ($i=5; $i <= 30; $i+=5) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->feedListPage) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php 
			} 
?>
		</select>
<?php
			$arg2 = ob_get_contents();
			ob_end_clean();
			echo _f('등록된 블로그 목록을 %1 순서로, 페이지당 %2 개씩 보여줍니다', $arg1, $arg2);
			unset($arg1); unset($arg2);
?>
		</dd>
	</dl>

	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="feedListPageTitleLength">
<?php
			for ($i=3; $i <= 120; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->feedListPageTitleLength) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
			}
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('블로그 제목을 %1 자 만큼 보여줍니다', $arg);
?>
		</dd>
	</dl>	
	
	<dl class="normal last_item">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
		<select id="feedListRecentFeedList">
<?php
			for ($i=1; $i <= 20; $i++) {
?>
			<option value="<?php echo $i;?>" <?php if ($i == $skinConfig->feedListRecentFeedList) {?> selected="selected"<?php } ?>><?php echo $i;?></option>
<?php
			}
?>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('블로그 의 최근 글을 %1 개 보여줍니다', $arg);
?>
		</dd>
	</dl>
	<div class="skin_setting_tools">
		<a href="#" class="normalbutton" onclick="saveSkinSettings(); return false;"><span class="boldbutton"><?php echo _t('수정완료');?></span></a>
	</div>

</div>
<div class="clear"></div>

</div>

<?php
}
?>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
