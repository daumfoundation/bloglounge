<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	include ROOT. '/lib/piece/adminHeader.php';

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
		$exports[$file] = $export;
		unset($export);
	}

	$dir->close();

	requireComponent('Bloglounge.Model.Exports');
	
	$exportList = Export::getList();

	$pageCount = 15; // 페이지갯수
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if(!isset($page) || empty($page)) $page = 1;

	$paging = Func::makePaging($page, $pageCount, count($exportList));
?>

<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_plugin.css" type="text/css" />

<script type="text/javascript">
		function showExportConfig(domainName, type, height) {
			if (($('#exportStatus'+domainName).val() == 'off') && type =='config')
				return false;			
		
			if($("#exportDetail" + domainName).length==0) {
				$("#exportConfigView" + domainName + " td").append($("<div>").attr('id', 'exportDetail' + domainName).css ( {
					"width":"100%",
					"height":height+"px"
				} )).addClass('exportDetail');
				
				$('<iframe frameborder="0" scrolling="no">').attr('id', 'exportDetailFrame' + domainName).css( {
					"width":"100%",
					"height":height+"px"
				}).addClass('exportDetailFrame').appendTo('#exportDetail' + domainName); 

				$('#exportDetailFrame' + domainName).attr('src', "./"+type+"/?domainName="+domainName).css('height', height + 'px');
			} 

			if($("#exportConfigView" + domainName).css('display') == 'none') {		
				$("#exportConfigView" + domainName).show();
			} else {
				$("#exportConfigView" + domainName).hide();
			}
		}

		function hideExportConfig(domainName) {
			$("#exportConfigView" + domainName).hide();
			if(typeof(parent)!='undefined')
				$(parent.window).scrollTop(0);
			else 
				$(window).scrollTop(0);
		}	
		
		function resizeExportConfig(domainName, height) {
			$("#exportDetail" + domainName).height(height);
			$("#exportDetailFrame" + domainName).height(height);
		}

		function deleteExport(domainName) {
			if(confirm("<?php echo _t('삭제하신 익스포트는 복구하실 수 없습니다.\n\n삭제하시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: '<?php echo $service['path'];?>/service/export/delete.php',
				  data: 'domainName=' + encodeURIComponent(domainName),				  
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {					
						document.location.reload();
					} else {
						alert($("response message", msg).text());
					}
				  },
				  error: function(msg) {
					 alert('unknown error');
				  }
				});	
			}
		}

</script>

<div class="wrap title_wrap">
	<h3><?php echo _t("익스포트 목록");?> <span class="cnt">(<?php echo count($exportList);?>)</span></h3>
	<div class="title_right">
		<a class="normalbutton" href="<?php echo $service['path'];?>/admin/plugin/export/add"><span class="boldbutton"><?php echo _t('익스포트 추가');?></span></a>
	</div>
</div>
	
<br />

<div class="wrap">
<?php	
	$headers = array(array('title'=>_t('도메인'),'class'=>'export_domain','width'=>'150px'),				
					array('title'=>_t('프로그램'),'class'=>'export_title','width'=>'auto'),
					array('title'=>_t('노출'),'class'=>'export_count','width'=>'80px'),
					array('title'=>_t('설정'),'class'=>'export_config','width'=>'100px'));

	$datas = array();
	
	if(count($exportList)==0) {
		array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>'사용중인 익스포트가 존재하지 않습니다.') )) );
	} else {

		foreach($exportList as $export) {
			$data = array();

			$data['id'] = 'exportItem'.$export['domain'];
			//$data['class'] = $export['status']=='on'?' list_use':'';
			
			$data['datas'] = array();

			array_push($data['datas'], array('class'=>'export_domain','data'=> $export['domain'] ));

			
			if(isset($exports[$export['program']])) {
				$program = $exports[$export['program']]['title'] . ' : <strong>' . $export['program'] . '</strong>';
			} else {
				$program = $export['program'];
			}

			array_push($data['datas'], array('class'=>'export_title','data'=> $program ));

			array_push($data['datas'], array('class'=>'export_count','data'=> $export['count'] ));
			
			ob_start();
?>
			<a href="#" class="microbutton" onclick="showExportConfig('<?php echo $export['domain'];?>', 'config', <?php echo $exports[$export['program']]['window']['height'];?>); return false;"><span><?php echo _t('설정');?></span></a>
			
			<a href="#" class="microbutton alertbutton" onclick="deleteExport('<?php echo $export['domain'];?>'); return false;"><span><?php echo _t('삭제');?></span></a>

			<input id="exportStatus<?php echo $export['domain'];?>" type="hidden" value="<?php echo $export['status'];?>" />
<?php
			$config = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'export_config','data'=> $config ));

			array_push( $datas, $data );


			// 설정창
			array_push($datas, array('empty'=>true, 'id'=>'exportConfigView'.$export['domain'],'class'=>'export_config_view'));
		}
	}

	$footers = '';
?>

<?php echo makeTableBox('exportlist', $headers, $datas, $footers);?>
</div>

<br />

<div class="paging">
	<?php echo func::printPaging($paging);?>
</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>