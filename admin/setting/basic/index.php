<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	include ROOT. '/lib/piece/adminHeader.php';
	
	$config = new Settings;

	$logoFile = $config->logo;
	$logoPath = $service['path'].'/cache/logo/'.$logoFile;

	$logoAlt = _t('로고 이미지');

	if (empty($logoFile) || !file_exists(ROOT . '/cache/logo/'.$logoFile)) {
		$logoPath = $service['path'].'/images/noimage.jpg';
		$logoAlt = _t('로고 이미지가 설정되지 않았습니다');
	}
	
	$useLogo = false;

	// 현재 사용중인 스킨

	$n_skinname = Settings::get('metaskin');
	$n_skinpath = ROOT . '/skin/meta/'.$n_skinname;

	if(file_exists($n_skinpath.'/skin.html')) {
		$n_skinhtml = file_get_contents($n_skinpath.'/skin.html');
		if(empty($n_skinhtml)) {
			if(strpos('[##_logo_url_##]', $n_skinhtml) !== false) {
				$useLogo = true;
			}
		}
	}

?>

<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_setting.css" type="text/css" />
<script type="text/javascript">
		function saveSettings() {	
			if($('#useVerifier').attr('checked') && ($('#verifierType').val() == 'custom') && ($('#verifier').val() == '')) {
				alert("<?php echo _t('지정 단어로 인증하려면 지정 단어가 공백이면 안됩니다.');?>");
				$('#verifier').focus();
				return false;
			}

			$.ajax({
			  type: "POST",
			  url: _path +'/service/setting/save.php',
			  data: "updateCycle="+$('#updateCycle').val()+
							"&archivePeriod="+$('#archivePeriod').val()+
							"&restrictJoin="+($('#restrictJoin').attr('checked')?'y':'n')+
							"&useRssOut="+($('#useRssOut').attr('checked')?'y':'n')+	
							"&feeditemsOnRss="+$('#feeditemsOnRss').val()+
							"&restrictBoom="+($('#restrictBoom').attr('checked')?'y':'n')+
							"&rankBy="+$('#rankBy').val()+
							"&rankLife="+$('#rankLife').val()+
							"&boomDownReactor="+$('#boomDownReactor').val()+
							"&boomDownReactLimit="+$('#boomDownReactLimit').val() +
							"&thumbnailLimit="+$('#thumbnailLimit').val()+		
							"&thumbnailSize="+$('#thumbnailSize').val()+		
							"&thumbnailType="+($('#thumbnailType').attr('checked')?'resizeBaseWidth':'resize')+		
							"&updateProcess="+$('#updateProcess').val()+
							"&summarySave="+($('#summarySave').attr('checked')?'y':'n')+
			 			    "&useVerifier="+($('#useVerifier').attr('checked')?'y':'n')+
							"&verifierType="+$('#verifierType').val()+
							"&verifier="+$('#verifier').val()+
							"&directView="+($('#directView').attr('checked')?'y':'n'),
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
		}

		function checkVerifier() {
			if($("#directView").attr('checked') && !$('#useVerifier').attr('checked')) {
				alert("<?php echo _t('블로그 글을 바로 보기 위해서는 인증시스템은 필수사항입니다.');?>");
				return false;
			}

			if($('#useVerifier').attr('checked')) {
				$('#verifierType').removeAttr( 'disabled' );
				if($('#verifierType').val() == 'random') {
					$('#verifier').attr( 'disabled', 'disabled' );
				} else {
					$('#verifier').removeAttr( 'disabled' );
				}
			} else {
				$('#verifierType').attr( 'disabled', 'disabled' );
				$('#verifier').attr( 'disabled', 'disabled' );
			}

			return true;
		}

		function checkDirectView() {
			$('#useVerifier').attr('checked','true');
			checkVerifier();
		}
</script>

<div class="wrap title_wrap">
	<h3><?php echo _t("환경설정");?></h3>
</div>

<div class="wrap setting_wrap">
	<h4><?php echo _t('기본정보');?></h4>
	<div class="setting">		
		<form action="./logo/" method="post" enctype="multipart/form-data" target="_hiddenFrame">
		<div class="logoImage">
			<img src="<?php echo $logoPath; ?>" alt="<?php echo $logoAlt;?>" id="myLogo" />
		</div>
		<div class="data">		
				<dl>
					<dt><?php echo _t('제목');?></dt>
					<dd>
						<input type="text" class="input faderInput" name="title" id="title" value="<?php echo htmlspecialchars($config->title); ?>" />
					</dd>
				</dl>	
				<dl>
					<dt><?php echo _t('설명');?></dt>
					<dd>
						<input type="text" class="input faderInput" name="description" id="description" value="<?php echo htmlspecialchars($config->description); ?>" />
					</dd>
				</dl>
				<dl>
					<dt><?php echo _t('로고');?></dt>
					<dd>
						<input type="file" class="input faderInput" name="logoFile" id="logoFile"/>
					</dd>
				</dl>					
				<dl>
					<dt></dt>
					<dd>
						<input type="checkbox" name="delLogo" id="delLogo"/>&nbsp;<label for="delLogo"><span class="help"><?php echo _t('로고 이미지 삭제');?></span></label>
					</dd>
				</dl>	

<!--
				<dl>
					<dt></dt>
					<dd>
						 <span class="information"><?php echo $useLogo ? '':_t('현재 스킨은 로고 이미지가 보여지지 않습니다.');?></span>
					</dd>
				</dl>
-->

		</div>
		<div class="clear"></div>
		
		<div class="button_wrap">
			<span class="normalbutton"><input type="submit" value="<?php echo _t('수정완료');?>" /></span>
		</div>
		</form>		
		<iframe id="_hiddenFrame" name="_hiddenFrame" class="hidden" src="about:blank" frameborder="0" width="0" height="0"></iframe>
	</div>
	<h4><?php echo _t('세부설정');?></h4>
	<div class="setting">
<?php
				$updateCycle = $config->updateCycle;
				$archivePeriod = $config->archivePeriod;
				$thumbnailLimit = $config->thumbnailLimit;
				$thumbnailSize = $config->thumbnailSize;	
				$thumbnailType = $config->thumbnailType;
				$updateProcess = $config->updateProcess;
				$summarySave = $config->summarySave;
?>	

	<dl class="normal">
		<dt><?php echo _t('수집');?></dt>
		<dd>
<?php
			ob_start();
?>
				<select name="thumbnailLimit" id="thumbnailLimit">			
					<option value="0" <?php if ($thumbnailLimit == '0') { ?>selected="selected"<?php } ?>><?php echo _f('%1개',0);?></option>
					<option value="1" <?php if ($thumbnailLimit == '1') { ?>selected="selected"<?php } ?>><?php echo _f('%1개',1);?></option>
					<option value="2" <?php if ($thumbnailLimit == '2') { ?>selected="selected"<?php } ?>><?php echo _f('%1개', 2);?></option>
					<option value="3" <?php if ($thumbnailLimit == '3') { ?>selected="selected"<?php } ?>><?php echo _f('%1개', 3);?></option>
					<option value="4" <?php if ($thumbnailLimit == '4') { ?>selected="selected"<?php } ?>><?php echo _f('%1개', 4);?></option>
					<option value="5" <?php if ($thumbnailLimit == '5') { ?>selected="selected"<?php } ?>><?php echo _f('%1개', 5);?></option>
					<option value="6" <?php if ($thumbnailLimit == '6') { ?>selected="selected"<?php } ?>><?php echo _f('%1개', 6);?></option>
				</select>				
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('미리보기 이미지를 %1 저장합니다.', $arg);
?>

		</dd>
	</dl>	

	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
				<select name="thumbnailSize" id="thumbnailSize">		
<?php
		for($size=50;$size<=1000;$size+=50) {
?>
					<option value="<?php echo $size;?>" <?php if ($thumbnailSize == $size) { ?>selected="selected"<?php } ?>><?php echo _f('%1px',$size);?></option>
<?php
		}
?>
				</select>				
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('미리보기 이미지의 크기를 %1 로 저장합니다.', $arg);
?>

		</dd>
	</dl>	
	<dl class="normal">
		<dt></dt>
		<dd>
			<input type="checkbox" <?php if ($thumbnailType == 'resizeBaseWidth') { ?>checked="checked"<?php } ?> name="thumbnailType" id="thumbnailType" value="y" /><label for="thumbnailType">&nbsp;<?php echo _t('미리보기 이미지의 높이를 자동조절합니다.');?></label>
		</dd>
	</dl>	
	<dl class="normal comments ">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('이 옵션을 지정하지 않으면 저장되는 썸네일의 크기는 너비와 높이가 같은 정사각형으로 저장됩니다.');?>
		</dd>
	</dl>

	<dl class="line"></dl>

	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
				<select name="updateProcess" id="updateProcess">
					<option value="random" <?php if ($updateProcess == 'random') { ?>selected="selected"<?php } ?>><?php echo _t('임의적로');?></option>
					<option value="repeat" <?php if ($updateProcess == 'repeat') { ?>selected="selected"<?php } ?>><?php echo _t('순차적으로');?></option>
				</select>				
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('블로그의 피드를 %1 수집합니다.', $arg);
?>

		</dd>
	</dl>	
	
	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
				<select name="updateCycle" id="updateCycle">		
					<option value="0.25" <?php if ($updateCycle == '0.25') { ?>selected="selected"<?php } ?>><?php echo _f('%1초 이상', 15);?></option>				
					<option value="0.5" <?php if ($updateCycle == '0.5') { ?>selected="selected"<?php } ?>><?php echo _f('%1초 이상', 30);?></option>
					<option value="1" <?php if ($updateCycle == '1') { ?>selected="selected"<?php } ?>><?php echo _f('%1분 이상', 1);?></option>
					<option value="5" <?php if ($updateCycle == '5') { ?>selected="selected"<?php } ?>><?php echo _f('%1분 이상', 5);?></option>
					<option value="10" <?php if ($updateCycle == '10') { ?>selected="selected"<?php } ?>><?php echo _f('%1분 이상', 10);?></option>
					<option value="30" <?php if ($updateCycle == '30') { ?>selected="selected"<?php } ?>><?php echo _f('%1분 이상', 30);?></option>
					<option value="60" <?php if ($updateCycle == '60') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 1);?></option>
					<option value="120" <?php if ($updateCycle == '120') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 2);?></option>
					<option value="150" <?php if ($updateCycle == '150') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 4);?></option>
					<option value="360" <?php if ($updateCycle == '360') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 6);?></option>
					<option value="480" <?php if ($updateCycle == '480') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 8);?></option>
					<option value="720" <?php if ($updateCycle == '720') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 12);?></option>
					<option value="960" <?php if ($updateCycle == '960') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 16);?></option>
					<option value="1080" <?php if ($updateCycle == '1080') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 18);?></option>
					<option value="1200" <?php if ($updateCycle == '1200') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 20);?></option>
					<option value="1440" <?php if ($updateCycle == '1440') { ?>selected="selected"<?php } ?>><?php echo _f('%1시간 이상', 24);?></option>
				</select>					
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('마지막 업데이트로부터 %1 지난 피드만 새 글을 가져옵니다', $arg);
?>
		</dd>
	</dl>	

	<dl class="normal">
		<dt></dt>
		<dd>
<?php
			ob_start();
?>
				<select name="archivePeriod" id="archivePeriod">
					<option value="0" <?php if ($archivePeriod == '0') { ?>selected="selected"<?php } ?>><?php echo _t('계속해서');?></option>
					<option value="7" <?php if ($archivePeriod == '7') { ?>selected="selected"<?php } ?>><?php echo _f('%1일간', 7);?></option>
					<option value="15" <?php if ($archivePeriod == '15') { ?>selected="selected"<?php } ?>><?php echo _f('%1일간', 15);?></option>
					<option value="30" <?php if ($archivePeriod == '30') { ?>selected="selected"<?php } ?>><?php echo _f('%1개월간', 1);?></option>
					<option value="90" <?php if ($archivePeriod == '90') { ?>selected="selected"<?php } ?>><?php echo _f('%1개월간', 3);?></option>
					<option value="180" <?php if ($archivePeriod == '180') { ?>selected="selected"<?php } ?>><?php echo _f('%1개월간', 6);?></option>
					<option value="360" <?php if ($archivePeriod == '360') { ?>selected="selected"<?php } ?>><?php echo _f('%1개월간', 12);?></option>
				</select>				
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('수집된 글을 %1 보관합니다.', $arg);
?>

		</dd>
	</dl>		
	<dl class="normal ">
		<dt></dt>
		<dd>
			<input type="checkbox" <?php if (Validator::getBool($summarySave)) { ?>checked="checked"<?php } ?> name="summarySave" id="summarySave" value="y" /><label for="summarySave">&nbsp;<?php echo _t('수집된 글의 본문을 요약 저장합니다.');?></label>
		</dd>
	</dl>		
	<dl class="normal comments last_item">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('이 설정을 선택하면 용량 절약을 위해 본문내용을 최대 1000자만큼만 저장합니다.');?>
		</dd>
	</dl>	
	<dl class="normal">
		<dt><?php echo _t('정책');?></dt>
		<dd>
			<input type="checkbox" <?php if (Validator::getBool($config->directView)) { ?>checked="checked"<?php } ?> name="directView" id="directView" value="y" onclick="checkDirectView();" /><label for="directView">&nbsp;<?php echo _t('블로그라운지에서 블로그의 글을 바로 볼 수 있도록 합니다.');?></label>
		</dd>
	</dl>
	<dl class="normal comments">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('이 설정을 선택하면 블로그라운지에서 바로 블로그의 글을 보실 수 있습니다. 인증시스템은 필수로 사용되게 됩니다.');?>
		</dd>
	</dl>		
	<dl class="normal">
		<dt></dt>
		<dd>
			<input type="checkbox" <?php if (Validator::getBool($config->restrictJoin)) { ?>checked="checked"<?php } ?> name="restrictJoin" id="restrictJoin" value="y" /><label for="restrictJoin">&nbsp;<?php echo _t('운영자가 승인해야 회원가입을 가능하게 합니다.');?></label>
		</dd>
	</dl>
	<dl class="normal comments">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('이 설정을 선택하면 회원정보 관리에서 가입 신청을 허락해주어야 가입자가 피드를 등록할 수 있습니다.');?>
		</dd>
	</dl>	
	<dl class="normal">
		<dt></dt>
		<dd>
			<input type="checkbox" <?php if (Validator::getBool($config->useVerifier)) { ?>checked="checked"<?php } ?> name="useVerifier" id="useVerifier" value="y" onclick="return checkVerifier();" /><label for="useVerifier">&nbsp;<?php echo _t('인증시스템을 사용합니다.');?></label>
		</dd>
	</dl>	
	<dl class="normal comments">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('이 설정을 선택하면 사용자가 등록한 블로그에 인증시스템을 도입합니다. 운영자가 등록한 블로그는 사용되지 않습니다.');?>
		</dd>
	</dl>
	
	<dl class="normal">
		<dt></dt>
		<dd class="inner">
<?php
			ob_start();
?>
				<select onclick="checkVerifier()" name="verifierType" id="verifierType" <?php if (!Validator::getBool($config->useVerifier)) { ?>disabled="disabled" <?php } ?>>					
					<option value="random" <?php if ($config->verifierType == 'random') { ?>selected="selected"<?php } ?>><?php echo _t('자동생성 단어');?></option>
					<option value="custom" <?php if ($config->verifierType == 'custom') { ?>selected="selected"<?php } ?>><?php echo _t('지정 단어');?></option>
				</select>					
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('인증 단어로 %1 를 사용합니다.', $arg);
?>
		</dd>
	</dl>		
	<dl class="normal">
		<dt></dt>
		<dd class="inner">
			지정 단어 : <input type="text" class="input" value="<?php echo $config->verifier;?>" name="verifier" id="verifier" <?php if (!Validator::getBool($config->useVerifier) || $config->verifierType == 'random') { ?>disabled="disabled" <?php } ?>/>
		</dd>
	</dl>	
	<dl class="normal comments">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('인증 단어는 인증할 블로그의 태그, 제목 또는 본문에 포함될 시 인증이 됩니다. 지정 단어는 특수문자와의 조합등 잘 사용하지 않는 고유 문장(단어)을 사용하는 것이 좋습니다.');?>
		</dd>
	</dl>

	<dl class="line"></dl>

	<dl class="normal">
		<dt></dt>
		<dd>
			<input type="checkbox" <?php if (Validator::getBool($config->useRssOut)) { ?>checked="checked"<?php } ?> name="useRssOut" id="useRssOut" value="y" /><label for="useRssOut">&nbsp;<?php echo _t('수집된 글을 RSS 로 출력합니다.');?> (http://<?php echo $_SERVER['HTTP_HOST'].$service['path'];?>/rss/)</label>
		</dd>
	</dl>	
	<dl class="normal comments">
		<dt></dt>
		<dd class="text checkbox_hint">
			<?php echo _t('이 설정을 선택하면 사용자가 재배포를 허용한 최근 글을 다시 RSS 피드로 출력할 수 있습니다.');?>
		</dd>
	</dl>

	<dl class="normal last_item">
		<dt></dt>
		<dd class="inner">
<?php	
			$feeditemsOnRss = $config->feeditemsOnRss;
			ob_start();
?>
				<select name="feeditemsOnRss" id="feeditemsOnRss">
<?
					for($i=5;$i<=100;$i+=5) {
?>
					<option value="<?php echo $i;?>" <?php if ($feeditemsOnRss == $i) { ?>selected="selected"<?php } ?>><?php echo _f('%1개', $i);?></option>
<?php
					}
?>
				</select>					
<?php
			$arg = ob_get_contents();
			ob_end_clean();
			echo _f('RSS피드로 수집된 글을 %1 출력합니다.', $arg);
?>
		</dd>
	</dl>	

	<dl class="normal">
		<dt><?php echo _t('인기글');?></dt>
		<dd>
			<input type="checkbox" name="restrictBoom" id="restrictBoom" value="y" <?php if (Validator::getBool($config->restrictBoom)) {?>checked="checked"<?}?>/>&nbsp;<label for="restrictBoom"><?php echo _t('로그인 한 사람만 추천, 반대 기능을 사용할 수 있도록 합니다.');?></label>
		</dd>
	</dl>	
	<dl class="normal">
		<dt></dt>
		<dd>
<?php
		$rankBy = $config->rankBy;
		$rankLife = $config->rankLife;
		ob_start();
?>
		<select name="rankBy" id="rankBy">
			<option value="click" <?php if ($rankBy == 'read') { ?>selected="selected"<?php } ?>><?php echo _t('읽은 사람');?></option>
			<option value="boom" <?php if ($rankBy == 'boom') { ?>selected="selected"<?php } ?>><?php echo _t('추천한 사람');?></option>
		</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();

			echo _f('%1 이 많은 글을 인기글로 선정합니다', $arg);
			unset($arg1); unset($arg2);
?>
		</dd>
	</dl>
	<dl class="normal">
		<dt></dt>
		<dd>
<?php
		$rankBy = $config->rankBy;
		$rankLife = $config->rankLife;
		ob_start();
?>
			<select name="rankLife" id="rankLife">
				<option value="7" <?php if ($rankLife == '7') { ?>selected="selected"<?php } ?>><?php echo _f('%1일',7);?></option>
				<option value="15" <?php if ($rankLife == '15') { ?>selected="selected"<?php } ?>><?php echo _f('%1일',15);?></option>
				<option value="30" <?php if ($rankLife == '30') { ?>selected="selected"<?php } ?>><?php echo _f('%1일',30);?></option>
				<option value="60" <?php if ($rankLife == '60') { ?>selected="selected"<?php } ?>><?php echo _f('%1일',60);?></option>
				<option value="90" <?php if ($rankLife == '90') { ?>selected="selected"<?php } ?>><?php echo _f('%1일',90);?></option>
				<option value="100" <?php if ($rankLife == '100') { ?>selected="selected"<?php } ?>><?php echo _f('%1일',100);?></option>
			</select>
<?php
			$arg = ob_get_contents();
			ob_end_clean();

			echo _f('작성된 지 %1 이 넘은 글은 호감도를 계산하지 않습니다', $arg);

?>
		</dd>
	</dl>
	<dl class="normal last_item">
		<dt></dt>
		<dd>
<?php
		ob_start();
?>
		<select name="boomDownReactLimit" id="boomDownReactLimit">
			<?php for ($i=5; $i<=100; $i+=5) {?><option value="<?php echo $i;?>" <?php if ($config->boomDownReactLimit == $i) {?> selected="selected" <?php } ?>><?php echo $i;?></option><?php } ?>
		</select>
<?php
			$arg1 = ob_get_contents();
			ob_end_clean();
			ob_start();
?>
		<select name="boomDownReactor" id="boomDownReactor">
			<option value="hide" <?php if ($config->boomDownReactor == 'hide') { ?>selected="selected"<?php } ?>><?php echo _t('목록에 보이지 않게 합니다');?></option>
			<option value="delete" <?php if ($config->boomDownReactor == 'delete') { ?>selected="selected"<?php } ?>><?php echo _t('자동으로 삭제합니다');?></option>
			<option value="none" <?php if ($config->boomDownReactor == 'none') { ?>selected="selected"<?php } ?>><?php echo _t('아무것도 하지 않습니다');?></option>
		</select>
<?php
			$arg2 = ob_get_contents();
			ob_end_clean();

			echo _f('반대한 사람이 %1 이상인 글에 대해 %2', $arg1, $arg2);
			unset($arg1); unset($arg2);

?>
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
