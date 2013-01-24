<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireMembership();

	// 글수정
	$msg = '';
	if (isset($_POST['id']) || !empty($_POST['id']) || (isset($_POST['id']) && preg_match("/^[0-9]+$/", $_POST['id']))) {
		$feedItem = FeedItem::getAll($_POST['id']);
		if (FeedItem::editWithArray($_POST['id'], array("title"=>$_POST['title'], "focus"=>((isset($_POST['isFocus']) && $is_admin) ? 'y' : 'n'), "author"=>$_POST['author'], "permalink"=>$_POST['permalink'], "tags"=>$_POST['tags'],"autoUpdate"=>(isset($_POST['autoUpdate']) ? 'y' : 'n'), "visibility"=>(isset($_POST['visibility']) ? $_POST['visibility'] : NULL), "allowRedistribute"=>(isset($_POST['allowRedistribute']) ? 'y' : 'n')))) {
		  if($_POST['tags'] != $feedItem['tags']) {
		  
				$tags = func::array_trim(explode(',', $_POST['tags']));
		  		
				$oldTags = func::array_trim(explode(',', $feedItem['tags']));

				Tag::buildTagIndex($_POST['id'], $tags, $oldTags);
				
				Category::buildCategoryRelations($_POST['id'], $tags, $oldTags);
				
			}
		
			
			if(isset($_POST['category']))  {
				
				Category::setItemCategory($_POST['id'], $_POST['category']);
				
				Category::rebuildCount($_POST['category']);
				
				Category::rebuildCount($feedItem['category']);
			}
			
			
			$msg = _t('글 정보를 수정하였습니다.');
		} else {
			$msg = _t('글 정보수정을 실패했습니다.');
		}
	}

	$read = isset($_GET['read'])?$_GET['read']:0;	
	if (!preg_match("/^[0-9]+$/", $read)) {
		$read = 0;
	}	

	// 대표 썸네일 변경
	if(isset($_GET['thumbnail']) && !empty($read)) {
		FeedItem::setThumbnail($read, $_GET['thumbnail']);	
		if(empty($msg)) $msg = _t('대표 썸네일을 변경하였습니다.');
	}

	if(!empty($msg)) {
		addAppMessage($msg);
	}

	include ROOT. '/lib/piece/adminHeader.php';

	$pageCount = 15; // 페이지갯수
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if(!isset($page) || empty($page)) $page = 1;

	$keyword = isset($_GET['keyword']) && !empty($_GET['keyword']) ? $_GET['keyword'] : '';
	$type = isset($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : 'title+name';

	$params = '';
	if(!empty($keyword)) {
		$params = '&keyword=' . rawurlencode($keyword) . '&type=' . $type;	

		if(!empty($read)) { $page =	FeedItem::getPredictionPage($read, $pageCount, $type,$keyword); }
		if($is_admin) {
			list($posts, $totalFeedItems) = FeedItem::getFeedItems($type,$keyword,'',$page,$pageCount);		
		} else {
			list($posts, $totalFeedItems) = FeedItem::getFeedItemsByOwner(getLoggedId(), $type,$keyword,'',$page,$pageCount);					
		}
	} else {

		
		if($is_admin) 
			{if(!empty($read)) { $page = FeedItem::getPredictionPage($read,$pageCount); }
			list($posts, $totalFeedItems) = FeedItem::getFeedItems('','','',$page,$pageCount);			
		} else {
			if(!empty($read)) { $page =	FeedItem::getPredictionPageByOwner(getLoggedId(),$read,$pageCount); }
			list($posts, $totalFeedItems) = FeedItem::getFeedItemsByOwner(getLoggedId(),'','','',$page,$pageCount);	
		}
	}

	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);
	
	$categories = Category::getList();

	$categoryList = array();
	$categoryList[0] = array('id'=>'0', 'name'=>_t('분류없음'));

	foreach($categories as $category) {
		$categoryList[ $category['id'] ] = array('id'=>$category['id'], 'name'=>$category['name']);
	}


?>
	<script type="text/javascript">
		function toggleCheckAll(obj, className) {
			var is_checked = obj.checked;
			$("."+className).each( function() {
					this.checked = is_checked;
			});
		}

		function changeAction() {
			var list = $("#action_list");
			var now = $(list.attr('options')[list.attr('selectedIndex')]);						
			var value = now.val();
			switch(now.attr('class')) {
				case 'visibility_action':
					changeAllVisibility('postid', value);
				break;
				case 'category_action':
					changeAllCategory('postid', value, now.text());
				break;
				case 'delete_action':
					deleteAllItem('postid');
				break;
			}
		}
<?php
	if($is_admin) {
?>
		function setFocus(id) {
			var focusImage = $("#focusImage" + id);
			var on = 'n';
			if(focusImage.attr('src') == "<?php echo $service['path'];?>/images/admin/bt_focus_off.png") {
				on = 'y';
			}
			
			$.ajax({
			  type: "POST",
			  url: _path +'/service/entry/focus.php',
			  data: 'id=' + id + "&focus=" + on,
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					if(on == 'y') {
						$("#focusImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_focus_on.png");
					} else {
						$("#focusImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_focus_off.png");
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
	}
?>
		function changeCategory(id, value, text) {
			$.ajax({
			  type: "POST",
			  url: _path +'/service/entry/category.php',
			  data: 'id=' + id + "&value=" + encodeURIComponent(value),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					if(value == 0) {
						$("#entryCategory"+id).html('<span class="empty">' + text + '</span>');
					} else {
						$("#entryCategory"+id).text(text);
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

		function changeAllCategory(className, value, text) {	
			var ids = '';
			$("."+className).each( function() {
					if(this.checked) {
						ids += $(this).val() + ',';
					}
			});

			if(ids == '') {
				return false;
			}		

			$.ajax({
			  type: "POST",
			  url: _path +'/service/entry/category.php',
			  data: 'id=' + ids + "&value=" + encodeURIComponent(value),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {		
					ids = ids.split(',');
					for(i=0;i<ids.length;i++) {
						id = ids[i];
						if(id=='') continue;
						if(value == 0) {
							$("#entryCategory"+id).html('<span class="empty">' + text + '</span>');
						} else {
							$("#entryCategory"+id).text(text);
						}
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

		function deleteItem(id) {
			if(confirm("<?php echo _t('이 글을 휴지통으로 보내시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/entry/delete.php',
				  data: 'id=' + id + "&admin_mode=true",
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

		function deleteAllItem(className) {
			var ids = '';
			$("."+className).each( function() {
					if(this.checked) {
						ids += $(this).val() + ',';
					}
			});

			if(ids == '') {
				return false;
			}		
			
			if(confirm("<?php echo _t('선택된 모든 글을 휴지통에 보내시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/entry/delete.php',
				  data: 'id=' + ids + "&admin_mode=true",
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
				  url: _path +'/service/entry/visibility.php',
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

		function changeAllVisibility(className, visibility) {
			var ids = '';
			$("."+className).each( function() {
					if(this.checked) {
						ids += $(this).val() + ',';
					}
			});

			if(ids == '') {
				return false;
			}

			$.ajax({
				  type: "POST",
				  url: _path +'/service/entry/visibility.php',
				  data: 'id=' + ids + '&value=' + visibility,
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {	
						ids = ids.split(',');
						for(i=0;i<ids.length;i++) {
							id = ids[i];
							if(id=='') continue;

							if(visibility == 'y') {
								$("#list_item_" + id).removeClass('list_item_hide');

								$("#lockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_lock_off.gif");
								$("#unlockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_unlock_on.gif");
							} else {
								$("#list_item_" + id).addClass('list_item_hide');
								
								$("#lockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_lock_on.gif");
								$("#unlockImage" + id).attr('src', "<?php echo $service['path'];?>/images/admin/bt_unlock_off.gif");
							}
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

		function showEntryView(id) {
				var height = 0;
				
				if($("#entryPreviewDetail" + id).length==0) {
					$("#entryPreview" + id + " td").append($("<div>").attr('id', 'entryPreviewDetail' + id).css ( {
						"width":"100%",
						"height":height+"px"
					} )).addClass('entry_preview_detail');
					
					$('<iframe frameborder="0" scrolling="no">').attr('id', 'entryPreviewDetailFrame' + id).css( {
						"width":"100%",
						"height":height+"px"
					}).addClass('entry_preview_detail_frame').appendTo('#entryPreviewDetail' + id); 

					$('#entryPreviewDetailFrame' + id).attr('src', "./view/?id="+id).css('height', height + 'px');
				} 

				if($("#entryPreview" + id).css('display') == 'none') {		
					$("#icon_view_more_"+id).attr('src',"<?php echo $service['path'];?>/images/admin/bt_view_more_hide.gif");
					$("#entryPreview" + id).show();
					$("#list_item_" + id).addClass('show_preview');
				} else {
					$("#icon_view_more_"+id).attr('src',"<?php echo $service['path'];?>/images/admin/bt_view_more.gif");
					$("#entryPreview" + id).hide();	
					$("#list_item_" + id).removeClass('show_preview');
				}
				var pos = $("#list_item_"+id).offset();
				$(window).scrollTop(pos.top);
		//		location.href = "#entryPos" + id;
		}
		
		function hideEntryView(id) {
			$("#icon_view_more_"+id).attr('src',"<?php echo $service['path'];?>/images/admin/bt_view_more.gif");
			$("#entryPreview" + id).hide();	
			$("#list_item_" + id).removeClass('show_preview');
			
			var pos = $("#list_item_"+id).offset();

			if(typeof(parent)!='undefined')
				$(parent.window).scrollTop(pos.top);
			else 
				$(window).scrollTop(pos.top);
		}		
		
		function resizeEntryView(id, height) {
			$("#entryPreviewDetail" + id).height(height);
			$("#entryPreviewDetailFrame" + id).height(height);
		}
<?php
	if(!empty($read)) {
?>
		$(window).ready( function() {
			collectDiv("#read_item1", "#read_item2");
		});
<?php
	}
?>

	</script>
	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_blog.css" type="text/css" />
	
	<div class="wrap title_wrap search_wrap">
		<h3><?php echo empty($read)?_t("글목록"):_t("글수정 & 글목록");?> <span class="cnt">(<?php echo $totalFeedItems;?>)</span></h3>
		<div class="search">
			<form method="get">
				<select name="type">
					<option value="title+description"<?php echo $type=='title+description'?' selected="selected"':'';?>><?php echo _t('제목+내용');?></option>
					<option value="title"<?php echo $type=='title'?' selected="selected"':'';?>><?php echo _t('제목');?></option>
					<option value="description"<?php echo $type=='description'?' selected="selected"':'';?>><?php echo _t('내용');?></option>
					<option value="tag"<?php echo $type=='tag'?' selected="selected"':'';?>><?php echo _t('태그');?></option>
					<option value="blogURL"<?php echo $type=='blogURL'?' selected="selected"':'';?>><?php echo _t('블로그주소');?></option>
				</select>
				<input type="text" class="input faderInput" name="keyword" value="<?php echo $keyword;?>"/> <span class="searchbutton"><input type="submit" value="<?php echo _t('검색');?>" align="bottom" /></span><?php if(!empty($keyword)) {?><a href="<?php echo $service['path'];?>/admin/blog/entrylist" class="searchbutton"><span><?php echo _t('검색취소');?></span></a><?php } ?>
			</form>
		</div>
		<div class="clear"></div>
	</div>
<?php	
	requireComponent('LZ.PHP.Media');

	if(!empty($read)) {
		$readFeedItem = FeedItem::getAll($read);
	
		if(!isset($readFeedItem['category'])) {
			$readFeedItem['category'] = 0;
		}
	
		if($readFeedItem && ($readFeedItem['visibility']!='d')) {
			$date = Func::dateToString($readFeedItem['written']);
			$medias = Media::getMediasByFeedItemId($readFeedItem['id']);
			$desc = str_replace('&nbsp;','',func::stripHTML($readFeedItem['description']));				
			if(empty($desc)) {
				$desc = '<span class="empty">'._t('내용이 비어있거나 HTML로만 작성되어 있습니다.').'</span>';
			}							
?>
<div class="wrap">
	<div class="read_item read_item1">
		<?php echo drawAdminBoxBegin('item_wrap');?>
			<div id="read_item1" class="item">
				<h2><?php echo $readFeedItem['title'];?></h2>
				<div class="extra">		
				
					<?php echo _t('수집일');?> : <span class="date"><?php echo date('y.m.d H:i:s', $readFeedItem['written']);?></span> <span class="date_text">(<?php echo $date;?>)</span> &nbsp;
					<?php echo _t('주소');?> : <a href="<?php echo $readFeedItem['permalink'];?>" target="_blank"><?php echo Func::longURLtoShort($readFeedItem['permalink'],34,24,10);?></a>
				</div>
				<div class="data">	
<?php
			if(count($medias)>0) {
?>
					<div class="thumbnail">
<?php
					foreach($medias as $media) {
						$thumbnailFile = '';
						if ((substr($media['thumbnail'], 0, 7) != 'http://')) {
							if (!is_file(ROOT . '/cache/thumbnail/' . $media['thumbnail'])) { 
								$thumbnailFile = '';
							} else {
								$thumbnailFile = str_replace('/cache/thumbnail//', '/cache/thumbnail/', $service['path']. '/cache/thumbnail/'.$media['thumbnail']);			
							}
						}

						if(!empty($thumbnailFile)) {
?>
							<a href="<?php echo $service['path'];?>/admin/blog/entrylist?read=<?php echo $readFeedItem['id'];?>&thumbnail=<?php echo $media['id'];?>"><img src="<?php echo $thumbnailFile;?>" <?php echo $media['id'] == $readFeedItem['thumbnailId']?' class="selected"':'';?>/></a>
<?php

						}
					}
?>						
					</div>
<?php
				}
?>
					<div class="desc">
						<?php echo $desc;?>	
					</div>

					<a href="#" class="smallbutton" onclick="showEntryView(<?php echo $readFeedItem['id'];?>); return false;"><span><?php echo _t('미리보기');?></span></a>

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
						<dt><?php echo _t('분류');?></dt>
						<dd>
					
						<select name="category" style="vertical-align:middle;">
<?php	
					foreach($categoryList as $category) {
?>
							<option value="<?php echo $category['id']?>" <?php echo $category['id'] == $readFeedItem['category'] ? "selected" : ""?>><?php echo $category['name']?></option>
<?php
					}
?>
						</select>
						&nbsp;<a href="<?php echo $service['path'];?>/admin/blog/category"><span class="small">추가/수정</span></a>
						</dd>
					</dl>



					<dl>
						<dt><?php echo _t('글제목');?></dt>
						<dd><input type="text" name="title" value="<?php echo htmlspecialchars(stripcslashes($readFeedItem['title']));?>" class="input faderInput" /></dd>
					</dl>

					<dl>
						<dt><?php echo _t('글쓴이');?></dt>
						<dd>
							<input type="text" name="author" value="<?php echo htmlspecialchars($readFeedItem['author']);?>" class="input faderInput" />
						</dd>
					</dl>
					
					<dl>
						<dt><?php echo _t('글주소');?></dt>
						<dd>
							<input type="text" name="permalink" value="<?php echo $readFeedItem['permalink'];?>" class="input faderInput"/>
						</dd>
					</dl>

					<dl>
						<dt><?php echo _t('태그');?></dt>
						<dd>
							<input type="text" name="tags" value="<?php echo htmlspecialchars(str_replace(', ', ',', $readFeedItem['tags']));?>" class="input faderInput" />
						</dd>
					</dl>

					<div class="grayline"></div>

					<p class="radio_wrap">
						<input type="radio" id="isPublic" name="visibility" value="y" <?php if ($readFeedItem['visibility']=='y') { ?>checked="checked"<?php } ?> /> <label for="isPublic"><?php echo _t('이 글을 공개글로 설정합니다.');?></label>
					</p>
					<p class="radio_wrap">
						<input type="radio" id="isPrivate" name="visibility" value="n" <?php if ($readFeedItem['visibility']=='n') { ?>checked="checked"<?php } ?> /> <label for="isPrivate"><?php echo _t('이 글을 비공개글로 설정합니다.');?></label>
					</p>

					<div class="grayline"></div>
<?php
			if($is_admin) {
?>
					<p class="checkbox_wrap">
						<input type="checkbox" name="isFocus" id="isFocus" <?php if (Validator::getBool($readFeedItem['focus'])) { ?>checked="checked"<?php } ?> /> <label for="isFocus"><?php echo _t('이 글을 포커스로 설정합니다.');?></label>
						<div class="help checkbox_help"><?php echo _t('현재 글을 포커스로 사용하시려면 선택하세요.');?></div>
					</p>
<?php
			}
?>
					<p class="checkbox_wrap">
						<input type="checkbox" name="autoUpdate" id="autoUpdate" <?php if (Validator::getBool($readFeedItem['autoUpdate'])) { ?>checked="checked"<?php } ?> /> <label for="autoUpdate"><?php echo _t('피드 정보로부터 제목, 글쓴이 이름을 자동으로 업데이트 합니다.');?></label>
						<div class="help checkbox_help"><?php echo _t('글 제목이나 글쓴이 이름을 고정하고 싶은 경우 이 기능을 해제하세요');?></div>
					</p>
					<p class="checkbox_wrap">
						<input type="checkbox" name="allowRedistribute" id="allowRedistribute" <?php if (Validator::getBool($readFeedItem['allowRedistribute'])) { ?>checked="checked"<?php } ?> /> <label for="allowRedistribute"><?php echo _t('이 글의 RSS 재출력과 외부 검색 노출을 허용합니다.');?></label>
						<div class="help checkbox_help"><?php echo _t('RSS 출력, 외부 검색엔진 수집등의 기능에 이 글의 정보가 포함됩니다');?></div>
					</p>
					
					<br />

					<div class="grayline"></div>

					<p class="button_wrap">
						<span class="normalbutton"><input type="submit" value="<?php echo _t('수정완료');?>" /></span>
						<a href="#" class="normalbutton" onclick="deleteItem(<?php echo $readFeedItem['id'];?>); return false;"><span><?php echo _t('삭제');?></span></a>
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
	$headers = array(array('title'=>_t('선택'),'class'=>'entrylist_select','width'=>'50px'),
					array('title'=>_t('날짜'),'class'=>'entrylist_date','width'=>'100px'),
					array('title'=>_t('분류'),'class'=>'entrylist_category','width'=>'100px'),
					array('title'=>_t('제목'),'class'=>'entrylist_title','width'=>'320px'),
					array('title'=>_t('블로그'),'class'=>'entrylist_blog','width'=>'180px'),
					array('title'=>_t('랭크'),'class'=>'entrylist_rank','width'=>'50px'),
					array('title'=>_t('조회'),'class'=>'entrylist_hit','width'=>'60px'),
					array('title'=>_t('실행'),'class'=>'entrylist_execute','width'=>'auto'));
	
	$datas = array();
	if(count($posts)>0) {
		foreach($posts as $post) {		
			
			if(!isset($post['category'])) {
				$post['category'] = 0;
			}
			
			$data = array();

			$date = Func::dateToString($post['written']);
			$feedvisibility = Feed::get($post['feed'], 'visibility');

			$data['id'] = 'list_item_'.$post['id'];
			$data['class'] = ($post['visibility']=='n'?'list_item_hide':'').($post['id']==$read?' list_item_select':'');
			
			$data['datas'] = array();
			
			// 글 선택
			array_push($data['datas'], array('class'=>'entrylist_select','data'=> '<input type="checkbox" class="postid" value="'.$post['id'].'" />' ));
	
			// 글 등록날짜		
			ob_start();
?>			
			<?php echo date('y.m.d H:i:s', $post['written']);?><br />
			<span class="date_text">(<?php echo $date;?>)</span>
<?php
			$content = ob_get_contents();
			ob_end_clean();
			array_push($data['datas'], array('class'=>'entrylist_date','data'=> $content ));

			// 글 분류
			ob_start();
?>
			<?php echo ($post['category']==0?'<span class="empty">'.$categoryList[$post['category']]['name'].'</span>':$categoryList[$post['category']]['name']);?>
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('id'=>'entryCategory'.$post['id'],'class'=>'entrylist_category','data'=> $content ));
			
			// 글 제목
			ob_start();
?>
				<div class="focus">
					<?php if($is_admin) { echo '<a href="#" onclick="setFocus(\'' . $post['id'] . '\'); return false;">'; } ?><img id="focusImage<?php echo $post['id'];?>" src="<?php echo $service['path'];?>/images/admin/bt_focus_<?php echo $post['focus']=='y'?'on':'off';?>.png" alt="focus" align="absmiddle" <?php echo $is_admin?'':'title="'._t('포커스기능은 관리자만 사용하실 수 있습니다.').'"';?>/><?php if($is_admin) { echo '</a>'; }?>
				</div>
<?php
				if(!empty($post['thumbnailId'])) {
					if($media = Media::getMedia($post['thumbnailId'])) {
						$post['thumbnail'] = $media['thumbnail'];	
					}

					$thumbnailFile = Media::getMediaFile($post['thumbnail']);

					if(!empty($thumbnailFile)) {
?>
					<div class="thumbnail">
						<img src="<?php echo $thumbnailFile;?>"/>
					</div>
<?php
					}
				}
				
					$desc = UTF8::lessenAsEm(str_replace('&nbsp;','',func::stripHTML($post['description'])),42);
					if(empty($desc)) {
						$desc = '<span class="empty">'._t('내용이 비어있거나 HTML로만 작성되어 있습니다.').'</span>';
					}	
					$isNew = Func::isNew($post['written'],1);

?>
					<div class="data<?php echo !empty($thumbnailFile)?'':' no_thumbnail_data';?>">
						<div class="title"><a href="<?php echo $service['path'];?>/admin/blog/entrylist?read=<?php echo $post['id'];?>"><?php echo UTF8::lessenAsEm(stripcslashes(func::stripHTML($post['title'])), 36);?></a><?php echo ($isNew?'<img src="'.$service['path'].'/images/admin/icon_new.gif" alt="new" align="absmiddle" class="new" />':'');?></div>
						<a href="<?php echo $service['path'];?>/admin/blog/entrylist?read=<?php echo $post['id'];?>"><?php echo $desc?></a>
					</div>
					<div class="clear"></div>
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'entrylist_title','data'=> $content ));

			// 글 블로그
			ob_start();
?>
					<a href="<?php echo $service['path'];?>/admin/blog/list?read=<?php echo $post['feed'];?>" title="<?php echo _f('\'%1\' 정보보기', stripcslashes(Feed::get($post['feed'], 'title')));?>"><?php echo UTF8::lessenAsEm(stripcslashes(Feed::get($post['feed'], 'title')), 30);?></a> <?php echo $feedvisibility=='n'?'<span class="hide">'._t('(비공개)').'</span>':'';?>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'entrylist_blog','data'=> $content ));
			
			// 글 랭크
			$rank = Boom::getRank($post['id']);
			array_push($data['datas'], array('class'=>'entrylist_rank','data'=> '<span class="rank'.$rank.'">'.$rank.'</span>' ));
			
			// 글 읽은 수
			array_push($data['datas'], array('class'=>'entrylist_hit','data'=> $post['click']));
			
			// 글 실행
			ob_start();
?>
				<div class="tools">
					<a href="#" onclick="changeVisibility(<?php echo $post['id'];?>, 'n'); return false;"><img id="lockImage<?php echo $post['id'];?>" src="<?php echo $service['path'];?>/images/admin/bt_lock_<?php echo $post['visibility']=='n'?'on':'off';?>.gif" alt="<?php echo _t('비공개');?>" /></a><a href="#" onclick="changeVisibility(<?php echo $post['id'];?>, 'y'); return false;"><img id="unlockImage<?php echo $post['id'];?>" src="<?php echo $service['path'];?>/images/admin/bt_unlock_<?php echo $post['visibility']=='y'?'on':'off';?>.gif" alt="<?php echo _t('공개');?>" /></a> <a href="#" onclick="deleteItem(<?php echo $post['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_trash.gif" alt="<?php echo _t('휴지통으로');?>" /></a>
				</div>
				<div class="more">				
					<a href="#" onclick="showEntryView(<?php echo $post['id'];?>); return false;"><img id="icon_view_more_<?php echo $post['id'];?>" src="<?php echo $service['path'];?>/images/admin/bt_view_more.gif" alt="<?php _t('내용 미리보기');?>" /></a>
				</div>
				<div class="clear"></div>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'entrylist_execute','data'=> $content ));
			
			array_push($datas, $data);

			// 내용 미리보기
			array_push($datas, array('empty'=>true, 'id'=>'entryPreview'.$post['id'],'class'=>'entry_preview'));

		}

	} else {
			array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>empty($keyword)?_t('수집된 글이 없습니다.'):_t('검색된 글이 없습니다.')) )) );
	}
	ob_start();
?>
			<select id="action_list" align="absmiddle">
				<option class="default" value="default"><?php echo _t('어떻게 하시겠습니까?');?></option>
				<optgroup class="visibility" label="<?php echo _t('공개여부');?>">
					<option class="visibility_action" value="y"><?php echo _t('공개합니다.');?></option>	
					<option class="visibility_action" value="n"><?php echo _t('비공개합니다.');?></option>
				</optgroup>		
				<optgroup class="category" label="<?php echo _t('분류변경');?>">
<?php
	foreach($categoryList as $category) {
?>
					<option class="category_action" value="<?php echo $category['id'];?>"><?php echo $category['name'];?></option>
<?php
	}
?>
				</optgroup>		
				<optgroup class="delete" label="<?php echo _t('휴지통');?>">
					<option class="delete_action" value="trash"><?php echo _t('휴지통으로 보냅니다.');?></option>
				</optgroup>
			</select>

			<a href="#" class="smallbutton" onclick="changeAction(); return false;"><span class="boldbutton"><?php echo _t('적용');?></span></a>
<?php
	$select = ob_get_contents();
	ob_end_clean();

	ob_start();
?>
		<div class="select">
			<input type="checkbox" onclick="toggleCheckAll(this,'postid');" />
		</div>
		<div class="action">
			<?php echo _f('<label for="action_list">선택한 글을</label> %1', $select);?>
		</div>				
		<div class="clear"></div>
<?php
	$footers = ob_get_contents();
	ob_end_clean();

	echo makeTableBox('entrylist', $headers, $datas, $footers);	
?>
</div>

<div class="wrap">
	<br />
	<div class="paging">
		<?php echo outputPaging($paging, $params);?>
	</div>
</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>