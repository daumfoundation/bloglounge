<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireMembership();

	include ROOT. '/lib/piece/adminHeader.php';

	$pageCount = 15; // 페이지갯수
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	if(!isset($page) || empty($page)) $page = 1;
	
	$read = isset($_GET['read']) ? $_GET['read'] : 0;

	if($is_admin) {
		list($posts, $totalFeedItems) = FeedItem::getFeedItems('','','',$page,$pageCount,true);		
	} else {
		list($posts, $totalFeedItems) = FeedItem::getFeedItemsByOwner(getLoggedId(),'','','',$page,$pageCount,true);		
	}

	$paging = Func::makePaging($page, $pageCount, $totalFeedItems);
	
	$categories = Category::getCategories();

	$categoryList = array();
	$categoryList[0] = array('id'=>'0', 'name'=>_t('분류없음'));

	foreach($categories as $category) {
		$categoryList[ $category['id'] ] = array('id'=>$category['id'], 'name'=>$category['name']);
	}

	requireComponent('LZ.PHP.Media');

?>
	<script type="text/javascript">
		var is_checked = false;
		function toggleCheckAll(className) {
			is_checked = !is_checked;
			$("."+className).each( function() {
					this.checked = is_checked;
			});
		}
		function deleteItem(id) {
			if(confirm("<?php echo _t('삭제된 글은 복구하실 수 없습니다.\n\n이 글을 삭제하시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/entry/remove.php',
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
			
			if(confirm("<?php echo _t('삭제된 글은 복구하실 수 없습니다.\n\n선택된 모든 글을 삭제하시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/entry/remove.php',
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

		function restoreItem(id) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/entry/restore.php',
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

		function restoreAllItem(className) {
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
			  url: _path +'/service/entry/restore.php',
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

	</script>
	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_blog.css" type="text/css" />
	<div class="wrap title_wrap">
		<h3><?php echo _t("휴지통");?> <span class="cnt">(<?php echo $totalFeedItems;?>)</span></h3>
	</div>


<div class="wrap">
<?php 
	$headers = array(array('title'=>_t('선택'),'class'=>'entrylist_select','width'=>'50px'),
					array('title'=>_t('날짜'),'class'=>'entrylist_date','width'=>'100px'),
					array('title'=>_t('분류'),'class'=>'entrylist_category','width'=>'100px'),
					array('title'=>_t('제목'),'class'=>'entrylist_title','width'=>'350px'),
					array('title'=>_t('블로그'),'class'=>'entrylist_blog','width'=>'180px'),
					array('title'=>_t('랭크'),'class'=>'entrylist_rank','width'=>'50px'),
					array('title'=>_t('조회'),'class'=>'entrylist_hit','width'=>'60px'),
					array('title'=>_t('실행'),'class'=>'entrylist_execute','width'=>'auto'));
	
	$datas = array();

	if(count($posts)>0) {
		foreach($posts as $post) {		
			$data = array();

			$date = Func::dateToString($post['written']);
			$feedvisibility = Feed::get($post['feed'], 'visibility');

			$data['id'] = 'list_item_'.$post['id'];
			$data['class'] = ($post['visibility']=='n'?'list_item_hide':'').($post['id']==$read?' list_item_select':'');
			
			$data['datas'] = array();			
			
			if(!isset($post['category'])) {
				$post['category'] = 0;
			}
			
			// 글 선택
			array_push($data['datas'], array('class'=>'entrylist_select','data'=> '<input type="checkbox" class="postid" value="'.$post['id'].'" />' ));
	
			// 글 등록날짜		
			ob_start();
?>
			<?php echo date('y.m.d H:i:s', $post['written']);?><br />
			<span class="date_text">(<?php echo _f($date[0],$date[1]);?>)</span>
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

			array_push($data['datas'], array('class'=>'entrylist_category','data'=> $content ));
			
			// 글 제목
			ob_start();

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
					<div class="data">
						<div class="title"><?php echo UTF8::lessenAsEm(stripcslashes(func::stripHTML($post['title'])), 38);?></div>
						<?php echo $desc?>
					</div>
					<div class="clear"></div>
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'entrylist_title','data'=> $content ));

			// 글 블로그
			ob_start();
?>
					<a href="<?php echo $service['path'];?>/admin/blog/list?read=<?php echo $post['feed'];?>" title="<?php echo _f('\'%1\' 정보보기', stripcslashes(Feed::get($post['feed'], 'title')));?>"><?php echo UTF8::lessenAsEm(stripcslashes(Feed::get($post['feed'], 'title')), 30);?></a> <?php echo $feedvisibility=='n'?_t('비공개'):'';?>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'entrylist_blog','data'=> $content ));
			
			// 글 랭크
			array_push($data['datas'], array('class'=>'entrylist_rank','data'=> '<span class="rank'.Boom::getRank($post['id']).'">'.Boom::getRank($post['id']).'</span>' ));
			
			// 글 읽은 수
			array_push($data['datas'], array('class'=>'entrylist_hit','data'=> $post['click']));
			
			// 글 실행
			ob_start();
?>				
				<div class="tools">
					<a href="#" onclick="restoreItem(<?php echo $post['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_restore.gif" alt="복원" /></a>
				</div>
				<div class="trash">				
					<a href="#" onclick="deleteItem(<?php echo $feed['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_delete.gif" alt="<?php echo _t('삭제');?>" /></a>
				</div>
				<div class="clear"></div>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'entrylist_execute','data'=> $content ));
			
			array_push($datas, $data);
		}

	} else {
			array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>'휴지통이 비어있습니다.') )) );
	}

	ob_start();
?>
		<div class="select">
			<a href="#" onclick="toggleCheckAll('postid'); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_arrow.gif" /></a>
		</div>
		<div class="action">
			<a href="#" onclick="restoreAllItem('postid'); return false;"><?php echo _t('복구');?></a> <span class="sep">|</span> <strong><a href="#" onclick="deleteAllItem('postid'); return false;"><?php echo _t('삭제');?></a></strong>
		</div>				
		<div class="clear"></div>
<?php
	$footers = ob_get_contents();
	ob_end_clean();

	echo makeTableBox('entrylist', $headers, $datas, $footers);	
?>
</div>

<div class="wrap paging_wrap">			
	<div class="paging">
		<?php echo outputPaging($paging);?>
	</div>
</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>