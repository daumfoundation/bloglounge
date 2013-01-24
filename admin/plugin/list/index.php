<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	include ROOT. '/lib/piece/adminHeader.php';

	$plugins = array();
	$xmls = new XMLStruct;			
	$pluginXmls = new XMLStruct;
	$dir = dir(ROOT . '/plugins/');
	while (($file = $dir->read()) !== false) {
		if (!preg_match('/^[A-Za-z0-9 _-]+$/', $file)) continue;
		if (!is_dir(ROOT . '/plugins/' . $file)) continue;
		if (!file_exists(ROOT . '/plugins/'.$file.'/index.xml')) continue;
		if (!$xmls->openFile(ROOT . '/plugins/'.$file.'/index.xml')) continue;

		$plugin = array();
		$plugin['name'] = $file;
		$plugin['title'] = $xmls->getValue('/plugin/information/name[lang()]');
		$plugin['description'] = $xmls->getValue('/plugin/information/description[lang()]');

		$pluginAuthor = $xmls->selectNode('/plugin/information/author[lang()]');
		$plugin['author'] = array('name'=>$pluginAuthor['.value'], 'link'=>$pluginAuthor['.attributes']['link'], 'email'=>$pluginAuthor['.attributes']['email']);
		
		$pluginTings = $xmls->selectNode('/plugin/ting[lang()]');
		$plugin['ting'] = array();
		if(isset($pluginTings['pop'])) {
			foreach ($pluginTings['pop'] as $pop) {
				$event = $pop['.attributes']['event'];
				$type = $pop['.attributes']['type'];
				$text = $pop['.value'];
				array_push($plugin['ting'], array('event'=>$event, 'type'=>$type, 'text'=>trim($text)));
			}
		}		
	
		if ($pluginConf = $xmls->selectNode('/plugin/config[lang()]')) {
			$plugin['config'] = 'y';	
			$plugin['window'] = $pluginConf['window'][0]['.attributes'];
		} else {
			$plugin['config'] = 'n';
		}

		if (!$plugin['status'] = $db->queryCell("SELECT status FROM {$database['prefix']}Plugins WHERE name='{$file}'"))
			$plugin['status'] = 'off';

		array_push($plugins, $plugin);
		unset($plugin);
	}

	$dir->close();
	
	$pageCount = 15; // 페이지갯수
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if(!isset($page) || empty($page)) $page = 1;

	$paging = Func::makePaging($page, $pageCount, count($plugins));
	
?>	

	<script type="text/javascript">
		var pluginTing = [];
<?php
	foreach ($plugins as $plugin) {
		echo "\t\tpluginTing['{$plugin['name']}'] = [];\n";
		echo "\t\tpluginTing['{$plugin['name']}']['title'] = '".func::escapeJSInAttribute($plugin['title'])."';\n";
		echo "\t\tpluginTing['{$plugin['name']}']['config'] = '{$plugin['config']}';\n";
		foreach ($plugin['ting'] as $ting) {
			echo "\t\tpluginTing['{$plugin['name']}']['{$ting['event']}'] = [];\n";
			echo "\t\tpluginTing['{$plugin['name']}']['{$ting['event']}']['type'] = '{$ting['type']}';\n";
			echo "\t\tpluginTing['{$plugin['name']}']['{$ting['event']}']['text'] = '".func::escapeJSInAttribute($ting['text'])."';\n";
		}
	}
?>
		function showPluginConfig(pluginName, type, width, height) {
			try {
				if (($('#pluginStatus'+pluginName).val() == 'off') && type =='config')
					return false;

				var isDialogAlreadyExists = ($('#pluginDetail').length==0) ? false : true;
				
				if (!isDialogAlreadyExists) {
						$(document.body).append($("<div>").attr('id', 'pluginDetail').css ( {
							"width":width+"px",
							"height":height+"px"
						} ).addClass('adminModal'));
						
						$('<iframe frameborder="0" scrolling="no">').attr('id', 'pluginDetailFrame').css( {
							"width":width+"px",
							"height":height+"px"
						}).addClass('adminModalFrame').appendTo('#pluginDetail'); 
				}
				$('#pluginDetailFrame').attr('src', "./"+type+"/?pluginName="+pluginName).css('height', height + 'px');
				showModal('#pluginDetail',{onShow:fnModalCenter});		

			} catch (e) {
				window.open('./'+type+'/?pluginName='+pluginName, 'pluginDetail', 'width='+width+', height='+height+', scrollbars=1, status=0, resizable=1');
			}
		}

		function togglePlugin(pluginName) {
			var pluginItem = $('#pluginItem'+pluginName);
			var pluginStatus = $('#pluginStatus'+pluginName);
			var pluginIcon = $('#pluginIcon'+pluginName);

			if (pluginStatus.val() == 'off') { // activate
				try {
					var t = ting(pluginTing[pluginName]['Plugin.on']['type'], <?php echo _f("%1+'을 켭니다'", 'pluginTing[pluginName][\'title\']');?>+'.\n' + pluginTing[pluginName]['Plug.on']['text']);
					var tq = (t != '') ? ((t) ? '&ting=y' : '&ting=n') : '';				
				} catch (e) {
					var tq = '';
				}
				$.ajax({
				  type: "POST",
				  url: _path +'/service/plugin/activate.php',
				  data: 'plugin=' + pluginName + tq,
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {
						pluginStatus.val('on');
						pluginItem.addClass('list_use');
						pluginIcon.attr('src', "<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_unuse.gif");
					} else {
						alert($("response message", msg).text());
					}
				  },
				  error: function(msg) {
					 alert('unknown error');
				  }
				});
			} else { // deactivate
				try {
					var t = ting(pluginTing[pluginName]['Plugin.off']['type'], <?php echo _f("%1+'을 끕니다'", 'pluginTing[pluginName][\'title\']');?>+'.\n' + pluginTing[pluginName]['Plug.off']['text']);
					var tq = (t != '') ? ((t) ? 'ting=y' : 'ting=n') : '';					
				} catch (e) {
					var tq = '';
				}

				$.ajax({
				  type: "POST",
				  url: _path +'/service/plugin/deactivate.php',
				  data: 'plugin=' + pluginName + tq,
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {
						pluginStatus.val('off');
						pluginItem.removeClass('list_use');
						pluginIcon.attr('src', "<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_use.gif");
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

		function ting (type, text) {
			if (type == 'alert') {
				alert(text);
				return '';
			} else if (type == 'confirm') {
				return confirm(text);
			}
		}
	</script>

<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_plugin.css" type="text/css" />
<div class="wrap title_wrap">
	<h3><?php echo _t("플러그인 목록");?> <span class="cnt">(<?php echo count($plugins);?>)</span></h3>
</div>

<div class="wrap">
<?php 
	$headers = array(array('title'=>_t('사용'),'class'=>'plugin_use','width'=>'80px'),
					array('title'=>_t('상태'),'class'=>'plugin_state','width'=>'60px'),
					array('title'=>_t('제목'),'class'=>'plugin_name','width'=>'200px'),
					array('title'=>_t('설명'),'class'=>'plugin_desc','width'=>'auto'),
					array('title'=>_t('제작자'),'class'=>'plugin_maker','width'=>'100px'),
					array('title'=>_t('설정'),'class'=>'plugin_config','width'=>'80px'));
	$datas = array();

	if(count($plugins)>0) {
		$start = ($page-1)*$pageCount;
		$end = ($page)*$pageCount;

		if($end > count($plugins)) $end = count($plugins);
		for($index=$start;$index<$end;$index++) {
			$plugin = $plugins[$index];
			
			$data = array();

			$data['id'] = 'pluginItem'.$plugin['name'];
			$data['class'] = $plugin['status']=='on'?' list_use':'';
			
			$data['datas'] = array();
			
		
			// 플러그인 사용
			ob_start();

		if($plugin['status']=='on') {
?>
			<a href="#" onclick="togglePlugin('<?php echo $plugin['name'];?>'); return false;"><img id="pluginIcon<?php echo $plugin['name'];?>" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_unuse.gif" /></a>
<?php 	} else { ?>
			<a href="#" onclick="togglePlugin('<?php echo $plugin['name'];?>'); return false;"><img id="pluginIcon<?php echo $plugin['name'];?>" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_use.gif" /></a>
<?php	} 
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'plugin_use','data'=> $content ));

			// 플로그인 상태

			array_push($data['datas'], array('class'=>'plugin_state','data'=> '<div>&nbsp;</div>'));


			// 플러그인 이름	
			array_push($data['datas'], array('class'=>'plugin_name','data'=> '<a href="#" onclick="showPluginConfig(\''.$plugin['name'].'\', \'info\', '.$plugin['window']['width'].', '.$plugin['window']['height'].'); return false;">' . $plugin['title'] . '</a>' ));
			
			// 플러그인 설명	
			array_push($data['datas'], array('class'=>'plugin_desc','data'=> $plugin['description'] ));
		
			// 플러그인 제작자	
			array_push($data['datas'], array('class'=>'plugin_maker','data'=> $plugin['author']['name'] ));

			// 플러그인 도구
			ob_start();

			if($plugin['config'] == 'y') {
?>			
			<a href="#" onclick="showPluginConfig('<?php echo $plugin['name'];?>', 'config', <?php echo $plugin['window']['width'];?>, <?php echo $plugin['window']['height'];?>); return false;"><img id="pluginConfigIcon" src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_setting.gif" alt="설정.." /></a>	
<?php
			}
?>			
			<input id="pluginStatus<?php echo $plugin['name'];?>" type="hidden" value="<?php echo $plugin['status'];?>" />
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'plugin_config','data'=> $content ));
			
			array_push($datas, $data);				
		}

	} else {
			array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>'플러그인이 존재하지 않습니다.') )) );
	}
	
	$footers = '';
	echo makeTableBox('pluginlist', $headers, $datas, $footers);	
?>
</div>

<br />

<div class="paging">
	<?php echo outputPaging($paging);?>
</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>