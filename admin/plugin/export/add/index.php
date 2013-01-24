<?php
	define('ROOT', '../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	$domainName = isset($_GET['domainName'])?$_GET['domainName']:'';
	$programName = isset($_GET['programName'])?$_GET['programName']:'';

	$addMode = false;

	if(!empty($domainName) && !empty($programName)) {
		$addMode = true;
		$error = false;

		requireComponent('Bloglounge.Model.Exports');

		$export = new Export;
		$result = $export->add($domainName, $programName);

		if($result === true) {
			header("Location: http://{$_SERVER['HTTP_HOST']}{$service['path']}/admin/plugin/export/");
			exit;
		} else {
			$error = $result;
		}
	}
	
	$exports = array();

	$xmls = new XMLStruct;			
	$exportXmls = new XMLStruct;
	$dir = dir(ROOT . '/exports/');
	while (($file = $dir->read()) !== false) {
		if (!preg_match('/^[A-Za-z0-9 _-]+$/', $file)) continue;
		if (!is_dir(ROOT . '/exports/' . $file)) continue;
		if (!file_exists(ROOT . '/exports/'.$file.'/index.xml')) continue;
		if (!$xmls->openFile(ROOT . '/exports/'.$file.'/index.xml')) continue;

		$export = array();
		$export['name'] = $file;
		$export['title'] = $xmls->getValue('/export/information/name[lang()]');
		$export['description'] = $xmls->getValue('/export/information/description[lang()]');

		$exportAuthor = $xmls->selectNode('/export/information/author[lang()]');
		$export['author'] = array('name'=>$exportAuthor['.value'], 'link'=>$exportAuthor['.attributes']['link'], 'email'=>$exportAuthor['.attributes']['email']);
		
		if ($exportConf = $xmls->selectNode('/export/config[lang()]')) {
			$export['config'] = 'y';	
			$export['window'] = $exportConf['window'][0]['.attributes'];
		} else {
			$export['config'] = 'n';
		}

		if(!isset($export['window']['height']) || $export['window']['height']=='auto') {
			$export['window']['height'] = 0;
		}
		array_push($exports, $export);
		unset($export);
	}

	$dir->close();

	include ROOT. '/lib/piece/adminHeader.php';

	if($addMode) {
		switch($error) {
			case -1: // 이미 존재하는 도메인
?>	
		<div class="wrap">
			<div class="warning_messages_title">
				<?php echo _f('도메인 : <span>%1</span>',$domainName);?>
			</div>
			<div class="warning_messages_wrap">
				<br />
					<?php echo _t('이미 같은 도메인을 사용하고 있는 익스포트가 있습니다.<br />다른 도메인을 입력해주세요.');?>		
				<br /><br />
				<a href="#" class="normalbutton" onclick="history.back(); return false;"><span><?php echo _t('뒤로');?></span></a>
			</div>
		</div>
<?php
			break;
			case -2: // 존재하지 않는 프로그램
?>	
		<div class="wrap">
			<div class="warning_messages_title">
				<?php echo _f('프로그램 : <span>%1</span>',$programName);?>
			</div>
			<div class="warning_messages_wrap">
				<br />
					<?php echo _t('존재하지 않는 프로그램을 선택하셨습니다.');?>		
				<br /><br />
				<a href="#" class="normalbutton" onclick="history.back(); return false;"><span><?php echo _t('뒤로');?></span></a>
			</div>
		</div>
<?php
			break;
		}
	} else {
?>
	<script type="text/javascript">
		function onExportCheck() {
			if($("#exportProgram").val() == "_none_select") {
				alert("<?php echo _t('프로그램을 선택해주세요');?>");
				$("#exportProgram").focus();
				return false;
			}

			if($("#exportDomain").val() == '') {
				alert("<?php echo _t('도메인을 입력해주세요');?>");
				$("#exportDomain").focus();
				return false;
			}
			
			return true;
		}
	</script>

	<div class="wrap title_wrap">
		<h3><?php echo _t("익스포트 추가");?></h3>
	</div>
	
	<br />

	<div class="wrap">
		<form method="get" onsubmit="return onExportCheck();">		
			<dl>
				<dt><label for="exportProgram"><?php echo _t('프로그램');?></label></dt>
				<dd>
					<select id="exportProgram" name="programName">
						<option value="_none_select"><?php echo _t('선택해주세요');?></option>
<?php
			foreach($exports as $export) {
?>
						<option value="<?php echo $export['name'];?>"><?php echo $export['title'];?></option>
<?php
			}
?>
					</select>
				</dd>
			</dl>
			<dl>
				<dt><label for="exportDomain"><?php echo _t('도메인');?></label></dt>
				<dd><input id="exportDomain" name="domainName" type="text" class="input faderInput" /></dd>
			</dl>			
			<dl class="comments">
				<dt></dt>
				<dd><?php echo _t('"/e/도메인" 또는 "/export/도메인" 과 같은 형태로 접근하실 수 있습니다');?></dd>
			</dl>


			<span class="normalbutton"><input type="submit" value="<?php echo _t('추가');?>" /></span>
		</form>	
	</div>
<?php
	} // if($addMode) {} else {}

	include ROOT. '/lib/piece/adminFooter.php';
?>