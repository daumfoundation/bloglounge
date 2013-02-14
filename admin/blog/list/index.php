<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireMembership();

	$id = isset($_GET['id'])?$_GET['id']:0; // 최근 등록된 블로그 아이디
	$read = isset($_GET['read'])?$_GET['read']:0;	
	if (!preg_match("/^[0-9]+$/", $read)) {
		$read = 0;
	}	

	//피드수정

	$msg = '';
	if (isset($_POST['id']) || !empty($_POST['id'])) {
		$toEdit = array();

		$toEdit['group'] = $_POST['group'];
		$toEdit['title'] = htmlspecialchars($_POST['title']);
		$toEdit['xmlURL'] = $_POST['xmlURL'];

		if(in_array($_POST['useFilter'],array('tag','title','tag+title'))) {
			$toEdit['filter'] = htmlspecialchars($_POST['filter']);
		} else {
			$toEdit['filter'] = "";
		}

		$toEdit['filterType'] = $_POST['useFilter'];
		$toEdit['author'] = htmlspecialchars($_POST['author']);
		$toEdit['autoUpdate'] = (isset($_POST['autoUpdate']) ? 'y' : 'n');
		$toEdit['allowRedistribute'] = (isset($_POST['allowRedistribute']) ? 'y' : 'n');
		$toEdit['visibility'] = (isset($_POST['visibility']) ? $_POST['visibility'] : NULL);
		$toEdit['everytimeUpdate'] = (isset($_POST['everytimeUpdate']) ? 'y' : 'n');
		$toEdit['isVerified'] = (isset($_POST['isVerified']) ? 'y' : 'n');

		if(!isAdmin()) {
			unset($toEdit['everytimeUpdate']);	
			unset($toEdit['isVerified']);
		}

		if (!preg_match("/^[0-9]+$/", $_POST['id']) || !Feed::edit($_POST['id'], $toEdit)) {
			$msg = _t('블로그 정보수정을 실패했습니다.');
		} else {
			$msg = _t('블로그 정보를 수정했습니다.');
		}
	}

	if(!empty($msg)) {
		addAppMessage($msg);
	}

	include ROOT. '/lib/piece/adminHeader.php';

	$pageCount = 20; // 페이지갯수
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if(!isset($page) || empty($page)) $page = 1;

	$keyword = isset($_GET['keyword']) && !empty($_GET['keyword']) ? $_GET['keyword'] : '';
	$type = isset($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : 'blogname';

	$params = '';
	if(!empty($keyword)) {
		$params = '&keyword=' . rawurlencode($keyword) . '&type=' . $type;	
		if($type == 'owner') {	
			requireComponent('Bloglounge.Model.Users');
			$id = User::getId($keyword);
			$sQuery = ' owner = ' . $id;
		} else if($type == 'ownername') {
			requireComponent('Bloglounge.Model.Users');
			$id = User::getIdByName($keyword);
			$sQuery = ' owner = ' . $id;
		} else {
			$sQuery = ' title LIKE "%' . $keyword . '%"';
		}			
		if(!empty($read)) { $page = Feed::getPredictionPage($read,$pageCount,$sQuery); }
		if($is_admin) {			
			list($feeds, $totalFeeds) = Feed::getFeeds($page,$pageCount,$sQuery);	
		} else {		
			list($feeds, $totalFeeds) = Feed::getFeedsByOwner(getLoggedId(), $page, $pageCount,$sQuery);	
		}
	} else {		
		if(!empty($read)) { $page = Feed::getPredictionPage($read,$pageCount); }
		if($is_admin) {
			list($feeds, $totalFeeds) = Feed::getFeeds($page,$pageCount);	
		} else {
			list($feeds, $totalFeeds) = Feed::getFeedsByOwner(getLoggedId(), $page, $pageCount);	
		}
	}

	$paging = Func::makePaging($page, $pageCount, $totalFeeds);

	$config = new Settings;
		
	requireComponent('Bloglounge.Data.Groups');
	$groups = Group::getGroupsAll();
	
?>	
	<script type="text/javascript">
		
		function updateFeed(id) {
				addMessage("<?php echo _t('블로그를 업데이트합니다.');?>");
				$.ajax({
				  type: "POST",
				  url: _path +'/service/feed/update.php',
				  data: 'id=' + id,
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {
						 addMessage("<?php echo _t('블로그 업데이트를 완료했습니다.');?>","success","reload");
						// document.location.reload();
					} else {
						addMessage($("response message", msg).text(),"error");
					}
				  },
				  error: function(msg) {
					 addMessage("<?php echo _t('알 수 없는 에러가 발생하여 업데이트하지 못했습니다.');?>","fail");
				  }
				});
		}
		
		function verifyFeed(id) {
				addMessage("<?php echo _t('인증을 시작합니다.');?>");
				$.ajax({
				  type: "POST",
				  url: _path +'/service/feed/verify.php',
				  data: 'id=' + id,
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {
						 var success = $("response success", msg).text();
						 if(success == 'true') {
							 addMessage("<?php echo _t('인증을 완료했습니다.');?>","success","reload");
						 } else {
							 addMessage("<?php echo _t('인증코드를 찾을 수 없습니다.');?>","success","reload");
						 }

						// document.location.reload();
					} else {
						addMessage($("response message", msg).text(),"error");
					}
				  },
				  error: function(msg) {
					 addMessage("<?php echo _t('알 수 없는 에러가 발생하여 인증하지 못했습니다.');?>","fail");
				  }
				});
		}
		function deleteItem(id) {
			if(confirm("<?php echo _t('이 블로그의 수집된 모든 글이 삭제되며 삭제된 글은 복구할 수 없습니다.\n\n이 블로그를 삭제하시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/feed/delete.php',
				  data: 'id=' + id,
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
		function changeVisibility(id, visibility) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/feed/visibility.php',
				  data: 'id=' + id + '&value=' + visibility,
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {
						if(visibility == 'y') {
							$("#list_item_" + id).removeClass('list_item_hide');

							$("#lockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_lock_off.gif");
							$("#unlockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_unlock_on.gif");

						} else {
							$("#list_item_" + id).addClass('list_item_hide');		
							
							$("#lockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_lock_on.gif");
							$("#unlockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_unlock_off.gif");
						}
					} else {
						alert($("response message", msg).text());
					}
				  },
				  error: function(msg) {
					 alert('unknown error');
				  }
				});
		}
<?php
	if(!empty($id) || !empty($read)) {
?>
		$(window).ready( function() {
<?php
	if(!empty($id)) {
?>
			var lastAddFeed = $("#list_item_<?php echo $id;?>");
			if(lastAddFeed.length > 0) {
				var to = lastAddFeed.css('backgroundColor');
				lastAddFeed.css('backgroundColor','#f3ffcd');

				lastAddFeed.animate({'backgroundColor':to},1500);
			}
<?php
	}
	if(!empty($read)) {
?>
			collectDiv("#read_item1", "#read_item2");
<?php
	}
?>
		});
<?php
	}
?>
	</script>
	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_blog.css" type="text/css" />
	<div class="wrap title_wrap search_wrap">
		<h3><?php echo empty($read)?_t("블로그목록"):_t("블로그수정 & 블로그목록");?> <span class="cnt">(<?php echo $totalFeeds;?>)</span></h3>
		<div class="search">
			<form method="get">
				<select name="type">
					<option value="blogname"<?php echo $type=='blogname'?' selected="selected"':'';?>><?php echo _t('블로그 제목');?></option>
					<option value="owner"<?php echo $type=='owner'?' selected="selected"':'';?>><?php echo _t('등록자 아이디');?></option>
					<option value="ownername"<?php echo $type=='ownername'?' selected="selected"':'';?>><?php echo _t('등록자 별명');?></option>
				</select>
				<input type="text" class="input faderInput" name="keyword" value="<?php echo $keyword;?>"/> <span class="searchbutton"><input type="submit" value="<?php echo _t('검색');?>" align="bottom" /></span><?php if(!empty($keyword)) {?><a href="<?php echo $service['path'];?>/admin/blog/entrylist" class="searchbutton"><span><?php echo _t('검색취소');?></span></a><?php } ?>
			</form>
		</div>
		<div class="clear"></div>
	</div>
<?php
	if(!empty($read)) {
		$readFeed = Feed::getAll($read);
		if($readFeed) {
			$date = Func::dateToString($readFeed['created']);			
			$date2 = Func::dateToString($readFeed['lastUpdate']);
			$noVerifier = Validator::getBool($config->useVerifier) && !Validator::getBool($readFeed['isVerified']) && ($readFeed['owner'] != 1);


			$desc = str_replace('&nbsp;','',func::stripHTML($readFeed['description']));				
			if(empty($desc)) {
				$desc = '<span class="empty">'._t('설명이 없습니다.').'</span>';
			}						
			$posts = FeedItem::getFeedItemsByFeedId($read,10);					
?>
<div class="wrap">
	<div class="read_item read_item1">
		<?php echo drawAdminBoxBegin('item_wrap');?>
			<div id="read_item1" class="item">
				<h2><?php echo $readFeed['title'];?></h2>
				<div class="extra">		
				
					<?php echo _t('수집일');?> : <span class="date"><?php echo date('y.m.d H:i:s', $readFeed['created']);?></span> <span class="date_text">(<?php echo $date;?>)</span> &nbsp;
					<?php echo _t('주소');?> : <a href="<?php echo $readFeed['blogURL'];?>" target="_blank"><?php echo Func::longURLtoShort($readFeed['blogURL'],35,20,15);?></a> <br />

					<?php echo _t('마지막업데이트');?> : <span class="date"><?php echo date('y.m.d H:i:s', $readFeed['lastUpdate']);?></span> <span class="date_text">(<?php echo $date2;?>)</span> 
					&nbsp;
					<?php echo _t('수집된 글수');?> :  <span class="count"><?php echo $readFeed['feedCount'];?></span>
				</div>
				<div class="data">	
<?php
			if($noVerifier) {
?>
				<div class="verifier_wrap">
					<div class="no_verifier">
						<?php echo _t('이 블로그는 현재 인증이 되지 않은 블로그입니다.<br />인증되지 않은 블로그는 업데이트 되지않습니다.');?>
					</div>

					<div class="verifier_note">
						<?php echo _f('인증하기 위해서는 이 블로그의 최신 글에 태그, 제목 또는 본문 중 한곳에<br /> <strong>"%1"</strong> 문장(단어)이 포함되어야 합니다.',$config->verifierType=='custom'?$config->verifier:Feed::getVerifier($readFeed['xmlURL']));?>
					</div>
				</div>
<?php
			}
?>
					<div class="desc">
						<?php echo $desc;?>	
					</div>

					<div class="grayline"></div>

					<div class="recent_posts">
						<h2><?php echo _t('최근 수집된 글');?></h2>
						<ul>
<?php
		if(count($posts)>0) {
				foreacH($posts as $post) {		
?>
							<li><a href="<?php echo $service['path'];?>/admin/blog/entrylist/?read=<?php echo $post['id'];?>"><?php echo $post['title'];?></a></li>
<?php
				}
		} else {
?>
							<li class="empty"><?php echo _t('수집된 글이 없습니다.');?></li>
<?php	} 
?>
							 <li class="more"><a href="<?php echo $service['path'];?>/admin/blog/entrylist/?type=blogURL&keyword=<?php echo rawurlencode($readFeed['blogURL']);?>"><?php echo _t('전체보기..');?></a></li>

						</ul>
					</div>
				</div>
			</div>
		<?php echo drawAdminBoxEnd();?>
	</div>
	<div class="read_item read_item2">		
		<?php echo drawAdminBoxBegin('item_wrap');?>
			<div id="read_item2" class="item">
				<form method="post" action="">
					<input type="hidden" name="id" value="<?php echo $read;?>"/>	
					<dl>
						<dt><?php echo _t('그룹');?></dt>
						<dd>
							<select name="group">
								<option value="0"><?php echo _t('없음');?></option>
<?php
							foreach($groups as $group) {
?>
								<option value="<?php echo $group['id'];?>"<?php echo $readFeed['group']==$group['id']?' selected="selected"':'';?>><?php echo $group['name'];?></option>
<?php
							}
?>
							</select>
						</dd>
					</dl>
					<dl>
						<dt><?php echo _t('블로그명');?></dt>
						<dd>
							<input type="text" name="title" value="<?php echo UTF8::clear($readFeed['title']);?>" class="input faderInput"/>
						</dd>
					</dl>
					<dl>
						<dt><?php echo _t('피드주소');?></dt>
						<dd>
							<input type="text" name="xmlURL" value="<?php echo UTF8::clear($readFeed['xmlURL']);?>" class="input faderInput"/>
						</dd>
					</dl>	
					<dl>
						<dt><?php echo _t('글쓴이');?></dt>
						<dd>
							<input type="text" name="author" value="<?php echo UTF8::clear($readFeed['author']);?>" class="input faderInput"/>
						</dd>
					</dl>	
					<dl class="comment">
						<dt></dt>
						<dd class="hint">
							<?php echo _t('설정하지 않으면 피드에 포함된 글쓴이 정보를 사용합니다.');?></span>
						</dd>
					</dl>			
					
					<div class="grayline"></div>


					<p class="radio_wrap">
						<input type="radio" id="isUnFilter" name="useFilter" value="none" <?php if (empty($filter)) { ?>checked="checked" <?php } ?> /> <label for="isUnFilter"><?php echo _t('모든 글을 수집합니다.');?></label>
					</p>
					<p class="radio_wrap">
						<input type="radio" id="isFilterTag" name="useFilter" value="tag" <?php if ((!empty($readFeed['filter']) || !empty($config->filter)) && $readFeed['filterType']=='tag') { ?>checked="checked" <?php } ?> /> <label for="isFilterTag"><?php echo _t('지정한 단어가 태그에 포함하는 글만 수집합니다.');?></label>		
					</p>		
					<p class="radio_wrap">
						<input type="radio" id="isFilterTitle" name="useFilter" value="title" <?php if ((!empty($readFeed['filter']) || !empty($config->filter)) && $readFeed['filterType']=='title') { ?>checked="checked" <?php } ?> /> <label for="isFilterTitle"><?php echo _t('지정한 단어가 제목에 포함하는 글만 수집합니다.');?></label>		
					</p>
					<p class="radio_wrap">
						<input type="radio" id="isFilterTagTitle" name="useFilter" value="tag+title" <?php if ((!empty($readFeed['filter']) || !empty($config->filter)) && $readFeed['filterType']=='tag+title') { ?>checked="checked" <?php } ?> /> <label for="isFilterTagTitle"><?php echo _t('지정한 단어가 태그 또는 제목에 포함하는 글만 수집합니다.');?></label>		
						<?php if (empty($config->filter)) { ?><div class="checkbox_input"><input type="text" id="feedFilter" name="filter" value="<?php echo htmlspecialchars($readFeed['filter']);?>" class="input faderInput" onfocus="if(document.getElementsByName('useFilter')[0].checked) document.getElementsByName('useFilter')[1].checked=true;" /></div><?php } ?>
						<div class="help checkbox_help">
							<?php if (empty($config->filter)) { echo _t('각 단어의 구분은 쉼표(,)로 합니다.'); } else { echo htmlspecialchars($config->filter); echo _t('관리자가 설정한 수집 태그 필터 설정이 우선권을 갖습니다'); } ?>
						</div>

					</p>

					<div class="grayline"></div>

					<p class="radio_wrap">
						<input type="radio" id="isPublic" name="visibility" value="y" <?php if ($readFeed['visibility']=='y') { ?>checked="checked"<?php } ?> /> <label for="isPublic"><?php echo _t('블로그를 공개합니다.');?></label>
					</p>
					<p class="radio_wrap">
						<input type="radio" id="isPrivate" name="visibility" value="n" <?php if ($readFeed['visibility']=='n') { ?>checked="checked"<?php } ?> /> <label for="isPrivate"><?php echo _t('블로그를 비공개합니다.');?></label>
					</p>

					<div class="grayline"></div>

<?php
				if($is_admin && Validator::getBool($config->useVerifier) && !Validator::getBool($readFeed['isVerified'])) {
?>
					<p class="checkbox_wrap">
						<input type="checkbox" name="isVerified" id="isVerified" <?php if (Validator::getBool($readFeed['isVerified'])) { ?>checked="checked"<?php } ?>/>&nbsp;<label for="isVerified"><?php echo _t('이 블로그의 인증을 완료합니다.');?></label><br />
						<div class="help checkbox_help"><?php echo _t('관리자가 인증절차를 생략하고 직접 인증완료합니다.');?></div>
					</p>
<?php
					}
?>

					<p class="checkbox_wrap">
						<input type="checkbox" name="autoUpdate" id="autoUpdate" <?php if (Validator::getBool($readFeed['autoUpdate'])) { ?>checked="checked"<?php } ?>/>&nbsp;<label for="autoUpdate"><?php echo _t('피드 정보로부터 제목, 글쓴이 이름을 자동으로 업데이트 합니다.');?></label><br />
						<div class="help checkbox_help"><?php echo _t('글 제목이나 글쓴이 이름을 고정하고 싶은 경우 이 기능을 해제하세요');?></div>
					</p>
					
					<p class="checkbox_wrap">
						<input type="checkbox" name="allowRedistribute" id="allowRedistribute" <?php if (Validator::getBool($readFeed['allowRedistribute'])) { ?>checked="checked"<?php } ?> /> <label for="allowRedistribute"><?php echo _t('이 블로그에서 수집된 글의 RSS 재출력과 외부 검색 노출을 허용합니다.');?></label>
						<div class="help checkbox_help"><?php echo _t('RSS 출력, 외부 검색엔진 수집등의 기능에 이 블로그에서 수집된 글이 포함됩니다.');?></div>
					</p>				
<?php
				if($is_admin) {
?>
					<p class="checkbox_wrap">
						<input type="checkbox" name="everytimeUpdate" id="everytimeUpdate" <?php if (Validator::getBool($readFeed['everytimeUpdate'])) { ?>checked="checked"<?php } ?> /> <label for="everytimeUpdate"><?php echo _t('이 블로그를 매번 최신화 합니다.');?></label>
						<div class="help checkbox_help"><?php echo _t('이 옵션을 사용하는 블로그가 많아질수록 동작 속도를 저하시킬 수 있습니다.');?></div>
					</p>
<?php
					}
?>
										
					<br />
										
					<br />

					<div class="grayline"></div>

					<p class="button_wrap">
						<span class="normalbutton"><input type="submit" value="<?php echo _t('수정완료');?>" /></span>
						<a href="#" class="normalbutton" onclick="deleteItem(<?php echo $readFeed['id'];?>); return false;"><span><?php echo _t('삭제');?></span></a>
					</p>

				</form>
			</div>
		<?php echo drawAdminBoxEnd();?>
	</div>
	<div class="clear"></div>
</div>
<?php
		}
	}
?>

<div class="wrap">
<?php 
	$headers = array(array('title'=>_t('등록일'),'class'=>'bloglist_date','width'=>'100px'),	
					array('title'=>_t('그룹'),'class'=>'bloglist_group','width'=>'90px'),
					array('title'=>_t('블로그'),'class'=>'bloglist_title','width'=>'260px'),
					array('title'=>_t('최근 업데이트'),'class'=>'bloglist_update','width'=>'360px'),
					array('title'=>_t('수집'),'class'=>'bloglist_count','width'=>'60px'),
					array('title'=>_t('실행'),'class'=>'bloglist_execute','width'=>'auto'));
	$datas = array();

	$group_names = array();		
	$group_names[0] = '';
	foreach($groups as $group) {
		$group_names[$group['id']] = $group['name'];
	}

	if(count($feeds)>0) {
		foreach($feeds as $feed) {		
			$data = array();

			$stringDate = Func::dateToString($feed['lastUpdate']);
			$lastPost = Feed::getLatestPost($feed['id']);
			$isNew = Func::isNew($feed['created'],1);
			$noVerifier = Validator::getBool($config->useVerifier) && !Validator::getBool($feed['isVerified']) && ($feed['owner'] != 1);

			$data['id'] = 'list_item_'.$feed['id'];
			$data['class'] = ($feed['visibility']=='n'?'list_item_hide':'').($feed['id']==$read?' list_item_select':'').($noVerifier?' no_verifier':'');
			
			$data['datas'] = array();
			
			// 블로그 등록날짜
			array_push($data['datas'], array('class'=>'bloglist_date','data'=> $noVerifier ? _t('미인증') : date('y.m.d H:i:s', $feed['created']) ));
			array_push($data['datas'], array('class'=>'bloglist_group','data'=> empty($group_names[$feed['group']])?'<span class="empty">'._t('그룹없음').'</span>':UTF8::lessen($group_names[$feed['group']],10) ));

			// 블로그 제목
			ob_start();
?>
			
<?php
			if($noVerifier) {
?>
				<div class="ftool"> <a href="#" class="microbutton" onclick="verifyFeed(<?php echo $feed['id'];?>); return false;"><span><?php echo _t('인증하기');?></span></a></div>
<?php
			}
?>
	
			<div class="ftitle<?php if(!$noVerifier) {?> noftool<?php } ?>">
				<a href="<?php echo $service['path'];?>/admin/blog/list?read=<?php echo $feed['id'];?>"><?php echo UTF8::lessenAsEm(stripcslashes($feed['title']), 25);?></a> <?php echo ($isNew?' <img src="'.$service['path'].'/images/admin/icon_new.gif" alt="new" align="absmiddle"/>':'');?>
			</div>

			<div class="clear"></div>
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'bloglist_title','data'=> $content ));

			// 블로그 최근업데이트
			ob_start();
			if(!empty($lastPost)) {
?>
					<a href="<?php echo $service['path'];?>/admin/blog/entrylist/?read=<?php echo $lastPost['id'];?>"><?php echo UTF8::lessenAsEm(stripcslashes(func::stripHTML($lastPost['title'])),20);?></a> <span class="date"> : <?php echo date('y.m.d H:i:s', $feed['lastUpdate']);?> (<?php echo $stringDate;?>) </span>
<?php
			} else {
?>
					<span class="empty"><?php echo _t('수집된 글이 없습니다.');?></span>
<?php
			}

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'bloglist_update','data'=> $content ));
			
			// 블로그 등록 글 수
			array_push($data['datas'], array('class'=>'bloglist_count','data'=> '<a href="'.$service['path'].'/admin/blog/entrylist/?type=blogURL&keyword='.rawurlencode($feed['blogURL']).'" title="'._t('전체보기').'">'.$feed['feedCount'].'</a>' ));
			
			// 블로그 실행
			ob_start();
?>
				<div class="updates">
					<a href="#" onclick="updateFeed(<?php echo $feed['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_update.gif" alt="<?php echo _t('업데이트');?>" /></a>
				</div>
				<div class="tools">
					<a href="#" onclick="changeVisibility(<?php echo $feed['id'];?>, 'n'); return false;"><img id="lockImage<?php echo $feed['id'];?>" src="<?php echo $service['path'];?>/images/admin/bt_lock_<?php echo $feed['visibility']=='n'?'on':'off';?>.gif" alt="비공개" /></a><a href="#" onclick="changeVisibility(<?php echo $feed['id'];?>, 'y'); return false;"><img id="unlockImage<?php echo $feed['id'];?>" src="<?php echo $service['path'];?>/images/admin/bt_unlock_<?php echo $feed['visibility']=='y'?'on':'off';?>.gif" alt="공개" /></a>
				</div>
				<div class="deletes">				
					<a href="#" onclick="deleteItem(<?php echo $feed['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_delete.gif" alt="<?php echo _t('삭제');?>" /></a>
				</div>
				<div class="clear"></div>
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'bloglist_execute','data'=> $content ));

			array_push($datas, $data);
		}

	} else {
			array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>empty($keyword)?_t('등록된 블로그가 없습니다.'):_t('검색된 블로그가 없습니다.')) )) );
	}
	$footers = '';
	echo makeTableBox('bloglist', $headers, $datas, $footers);	
?>
</div>

<div class="wrap">
	<br />	
	<div class="paging">
		<?php echo func::printPaging($paging,$params);?>
	</div>
</div>
<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
