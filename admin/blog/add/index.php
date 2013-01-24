<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireMembership();
	$userInformation = getUsers();

	$config = new Settings;

	$feedURL = isset($_GET['feedURL'])?$_GET['feedURL']:'';
	$feedId = isset($_GET['feedId'])?$_GET['feedId']:'';

	$verifyMode = $_GET['verifyMode'] == 'true' && !empty($feedURL) ? true : false;

	if(isset($_POST['feedURL']) && !empty($_POST['feedURL'])) { 
		
		if($userInformation['is_accepted'] == 'y') {
			$visibility = isset($_POST['isVisible']) ? 'y' : 'n';
			$filterType = isset($_POST['useFilter']) ? $_POST['useFilter'] : 'tag';
			$filter = $filterType&&isset($_POST['feedFilter'])?$_POST['feedFilter']:'';
			
			$id = $event->on('Add.addFeed', array($_POST['feedURL'], $visibility, $filter, $filterType));
			if($id === false || !is_numeric($id) || empty($id)) {
				$id = Feed::add($_POST['feedURL'], $visibility, $filter, $filterType);	
			}

			addAppMessage(_t('블로그를 추가했습니다.'));

			if(Validator::getBool($config->useVerifier) && !isAdmin()) {
				$targetURL = "http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/blog/add?feedId={$id}&feedURL=".$_POST['feedURL'].'&verifyMode=true';
			} else {
				$targetURL = "http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/blog/list?id={$id}";
			}			
			
			header("Location: $targetURL");
			exit;
		}
	}

	include ROOT. '/lib/piece/adminHeader.php';



	if($is_admin) {
?>
	<script type="text/javascript">
		function importOPML(id) {
			var obj = $(id);
			if(obj.val()=="") {
				obj.focus();
				return false;
			}

			addMessage("<?php echo _t('OPML 파일을 읽고 있습니다. 상황에 따라 시간이 길어질 수 있습니다.');?>");
			return true;
		}

		function exportOPML() {
			return true;
		}

		function showImportFile() {
			$("#opmlImportByFile").show();
			$("#opmlImportByURL").hide();
		}

		function showImportURL() {
			$("#opmlImportByFile").hide();
			$("#opmlImportByURL").show();
		}

		function onFeedCheck() {
			if($("#feedAddName").val() == "") {
				alert("<?php echo _t('피드주소를 입력해주세요');?>");
				$("#feedAddName").focus();
				return false;
			}
			addMessage("<?php echo _t('입력하신 피드를 검사중입니다.');?>");
			return true;
		}
	</script>
<?php
	}
	if($verifyMode) {
		$verifier = $config->verifier;

		if($config->verifierType == 'random') {
			$verifier = Feed::getVerifier($feedURL);
		}

		
?>
	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_blogadd.css" type="text/css" />
	<div class="wrap title_wrap">
		<h3><?php echo _t("블로그 인증");?></h3>
	</div>
	<br />
	<div class="accept_wrap wrap">
			<?php echo drawGrayBoxBegin();?>	
				<div class="verify_messages">
					<?php echo _t('블로그의 글을 수집하기 위해서는 인증이 필요합니다.');?><br />
					<div class="verifier_message">
					 <?php echo _t('인증코드');?> : <input type="text" class="input" readonly="true" value="<?php echo $verifier;?>" onclick="$(this).select();" />
					</div>
					<ul>
						<li>추가한 블로그의 최신글에 위 인증코드가 태그, 제목 또는 본문 중 한곳에 포함되면 인증이 완료됩니다.</li>
					</ul>
				</div>
			<?php echo drawGrayBoxEnd();?>		
			<br />
			<a href="<?php echo "http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/blog/list?id={$feedId}";?>" class="normalbutton boldbutton"><span><?php echo _t('확인');?></span></a>
	</div>

<?php

	} else { // if($verifyMode) else 

	if($userInformation['is_accepted'] == 'n') {
?>
	<div class="accept_wrap wrap">
			<?php echo drawGrayBoxBegin();?>	
				<div class="accept_messages">
					<?php echo _t('현재 페이지는 인증된 회원만이 사용하실 수 있습니다.');?>
				</div>
			<?php echo drawGrayBoxEnd();?>
	</div>
<?php
	} else {
?>
	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_blogadd.css" type="text/css" />
	<div class="wrap title_wrap">
		<h3><?php echo _t("블로그 추가");?></h3>
	</div>

<?php
	if(!empty($feedURL)) {
		$result = $event->on('Add.checkFeed', $feedURL);
		if(!is_array($result)) {
			$feedURL = trim('http://' . str_replace('http://','',$feedURL));
			list($status, $feed, $xml) = feed::getRemoteFeed($feedURL);
		} else {
			list($status, $feed, $xml) = $result;
			
		}
?>
	<div class="wrap add_detail_wrap">
		<div class="messagebox">
			<a href="./"><?php echo _t('다른 새로운 피드를 검사하신 후 추가 하시려면 이곳을 클릭하십시오.');?></a>
		</div>

<?php 
	if(!empty($feed) && !empty($xml)) {
?>
		<form method="post">
			<input type="hidden" name="feedURL" value="<?php echo $feed['xmlURL'];?>" />
			<dl>
					<dt><?php echo _t('피드주소');?></dt>
					<dd class="text xml_text"><?php echo func::filterURLModel($feed['xmlURL']); ?></dd>
			</dl>				
			<dl>
					<dt><?php echo _t('제목');?></dt>
					<dd class="text title_text"><?php echo stripslashes($feed['title']); ?></dd>
			</dl>				
			<dl>
					<dt><?php echo _t('설명');?></dt>
					<dd class="text description_text"><?php echo stripslashes($feed['description']); ?></dd>
			</dl>		
			<dl>
					<dt><?php echo _t('주소');?></dt>
					<dd class="text url_text"><?php echo func::filterURLModel($feed['blogURL']); ?> <!--<?php echo $feed['blogTool'];?>--></dd>
			</dl>
<?php		
	if(feed::doesExistXmlURL($feed['xmlURL'])) {
?>			
		<div class="warning_messages_wrap">
			<br />
				<?php echo _t('이 블로그는 이미 등록되어 있어 재등록 하실 수 없습니다.');?>		
			<br /><br />
			<a href="#" class="normalbutton" onclick="history.back(); return false;"><span><?php echo _t('뒤로');?></span></a>

		</div>
<?php
	} else {
		$result = $event->on('Add.getFeed', $xml);
		if(!is_array($result)) {
			$result = feed::getFeedItems($xml);
		}

		if(count($result)>0) {
?>
			<dl>
					<dt><?php echo _t('글');?></dt>
					<dd class="text">
						<?php echo _f('가장 최신의 글 "%1"(을)를 포함한 %2개의 글이 존재합니다.','<span class="point">'.UTF8::lessen($result[0]['title'],40).'</span>', '<span class="cnt">'.count($result).'</span>');?>
					</dd>
			</dl>
<?php
		}
?>
			<div class="options_wrap">
					<p>
						<?php if (empty($config->filter)) { ?><input type="radio" name="useFilter" value="none" id="useFilter_no" checked="checked" />&nbsp;<label for="useFilter_no"><?php echo _t('모든 글을 수집합니다.');?></label><br /><?php } ?>
						<input type="radio" name="useFilter" value="tag" id="useFilter_yes_tag" <?php if (!empty($config->filter)) { ?>checked="checked"<?php } ?>/>&nbsp;<label for="useFilter_yes_tag"><?php echo _t('지정한 단어가 태그에 포함하는 글만 수집합니다.');?></label><br />
						<input type="radio" name="useFilter" value="title" id="useFilter_yes_title" <?php if (!empty($config->filter)) { ?>checked="checked"<?php } ?>/>&nbsp;<label for="useFilter_yes_title"><?php echo _t('지정한 단어가 제목에 포함하는 글만 수집합니다.');?></label><br />
						<input type="radio" name="useFilter" value="tag+title" id="useFilter_yes_tag_title" <?php if (!empty($config->filter)) { ?>checked="checked"<?php } ?>/>&nbsp;<label for="useFilter_yes_tag_title"><?php echo _t('지정한 단어가 제목 또는 태그에 포함하는 글만 수집합니다.');?></label>

						<div><?php if (empty($config->filter)) { ?><input type="text" id="feedFilter" name="feedFilter" class="input faderInput" onfocus="if(document.getElementsByName('useFilter')[0].checked) document.getElementsByName('useFilter')[1].checked=true;" /><div class="help"><?php echo _t('각 단어의 구분은 쉼표(,)로 합니다.');?></div><?php } else { echo $config->filter;?> <div class="help"><?php echo _t('관리자가 설정한 수집 태그 필터 설정이 우선권을 갖습니다.');?></div><?php } ?></div>
					</p>
					<p>
						<input type="checkbox" name="isVisible" id="isVisible" checked="true" /> <label for="isVisible"><?php echo _t('블로그공개');?></label>
						<div class="help">
							<?php echo _t('블로그를 외부에 공개합니다. 비공개시 해당블로그의 글도 모두 비공개 처리됩니다.');?>
						</div>
					</p>
			</div>

			<br />			

			<span class="normalbutton"><input type="submit" value="<?php echo _t('추가');?>" /></span>

		</form>
<?php
		}
	} else {
?>
		<div class="warning_messages_wrap">
			<br />
				<?php echo _t('잘못된 피드이거나 피드주소를 찾을 수 없습니다. 올바른 피드주소를 입력해주세요.');?>
			<br /><br />
			<a href="#" class="normalbutton" onclick="history.back(); return false;"><span><?php echo _t('뒤로');?></span></a>

		</div>
<?php
	}
?>
	</div>
<?php		
		
	} else {
?>
	<div class="wrap add_wrap">
		<form method="get" onsubmit="return onFeedCheck();">
			<dl>
				<dt><label for="feedAddName"><?php echo _t('피드주소');?></label></dt>
				<dd><input id="feedAddName" name="feedURL" type="text" class="input faderInput" /></dd>
			</dl>			
			<dl class="comments">
				<dt></dt>
				<dd><?php echo _t('블로그 주소를 입력하셔도 자동으로 피드주소를 검사합니다.');?></dd>
			</dl>
			<span class="normalbutton"><input type="submit" value="<?php echo _t('피드검사');?>" /></span>
		</form>	
	</div>
<?php
	if($is_admin) {
?>
	<div  class="wrap opml_wrap">

		<h3><?php echo _t("OPML 관리");?></h3>
		<br />

		<dl>
				<dt><?php echo _t('가져오기');?></dt>
				<dd class="text">
					<a href="#" onclick="showImportURL(); return false;">웹상에서 가져오기</a> , <a href="#" onclick="showImportFile(); return false;">내 컴퓨터에서 가져오기</a>
				</dd>
		</dl>
		<dl class="comments">
			<dt></dt>
			<dd>
				<?php echo _t('OPML 파일을 읽어서 피드 목록에 추가합니다.');?>				
			</dd>
		</dl>

		<div id="opmlImportByURL" class="opmlImport">
			<form method="post" action="./opml/import/" enctype="multipart/form-data" target="_hiddenFrame" onsubmit="return importOPML('#importURL');">
				<input type="hidden" name="importType" value="url" />
				<input type="text" id="importURL" name="importURL" class="input faderInput" />

				<span class="normalbutton"><input type="image" align="absmiddle" value="가져오기" /></span>

				<div class="help">
					<?php echo _t("OPML이 위치한 URL주소를 입력하여 블로그를 추가합니다.");?>
				</div>
			</form>
		</div>

		<div id="opmlImportByFile" class="opmlImport">
			<form method="post" action="./opml/import/" enctype="multipart/form-data" target="_hiddenFrame" onsubmit="return importOPML('#importFile');">
				<input type="hidden" name="importType" value="upload" />
				<input type="file" id="importFile" name="importFile" class="input faderInput" />

				<span class="normalbutton"><input type="image" align="absmiddle" value="가져오기" /></span>

				<div class="help">
					<?php echo _t("내 컴퓨터상의 OPML을 업로드하여 블로그를 추가합니다.");?>
				</div>
			</form>
		</div>

		<dl>
				<dt><?php echo _t('내보내기');?></dt>
				<dd class="text">
					<a href="./opml/export/" onclick="return exportOPML();">다운로드</a>
				</dd>
		</dl>
		<dl class="comments">
			<dt></dt>
			<dd><?php echo _t('피드 목록 전체를 OPML 파일로 저장합니다.');?></dd>
		</dl>

	</div>		
	
	<iframe id="_hiddenFrame" name="_hiddenFrame" class="hidden" src="about:blank" frameborder="0" width="0" height="0"></iframe>
<?php
	}
	}
   }
}
	include ROOT. '/lib/piece/adminFooter.php';
?>
