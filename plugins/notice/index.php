<?php
	function dressNotice($input, $config) {
		global $database, $db, $skin;
		$blogid = isset($config['blog'])?$config['blog']:'';
		$tag = isset($config['tag'])?$config['tag']:'';
		$count = isset($config['count'])?$config['count']:5;
		$title_length = isset($config['title_length'])?$config['title_length']:15;
		$src_notices = $skin->cutSkinTag('notice');

		if(!empty($blogid)) {
			list($notices, $totalFeeditem) = getNoticeFeedItems($blogid, 1, $count);	
			
			$s_notices_rep = '';
			$src_notice_rep = $skin->cutSkinTag('notice_rep');

			if($totalFeeditem > 0) {
				foreach($notices as $notice) {
					$sp_notices = $skin->parseTag('notice_title', UTF8::lessen($notice['title'],$title_length), $src_notice_rep);		
					$sp_notices = $skin->parseTag('notice_url', $notice['permalink'], $sp_notices);		
					
					$s_notices_rep .= $sp_notices;
					$sp_notices = '';
				}	
				
				$src_notices = $skin->dressOn('notice_rep', $src_notice_rep, $s_notices_rep, $src_notices);

			} else {			
				$s_notices_rep = '';	
				$src_notices = ''; // 공지사항이 없을시 공지사항 틀도 감추기..
			}		
	
			$skin->dress('notice', $src_notices);
		}
	

		return $input . $result;
	}

	function getNoticeMenuText($name, $config) {

		return array('class'=>'notice', 'text'=>_t('공지사항'), 'link'=>'notice');
	}

	function getNoticePage($input, $config) {
		global $database, $db, $event, $service;

		$blogId = isset($config['blog'])?$config['blog']:0;
		$tag = isset($config['tag'])?$config['tag']:'';
		$pluginURL = $event->pluginURL;
		
		$params = '';

		if(!empty($blogId)) {
			
			$pageCount = 15; // 페이지갯수
			$page = isset($_GET['page']) ? $_GET['page'] : 1;
			if(!isset($page) || empty($page)) $page = 1;

			list($posts, $totalFeedItems) = getNoticeFeedItems($blogId,$page,$pageCount);							
			$paging = Func::makePaging($page, $pageCount, $totalFeedItems);
			
			ob_start();

?>
		<link rel="stylesheet" href="<?php echo $pluginURL;?>/style.css" type="text/css" />

		<script type="text/javascript">
			var is_checked = false;
			function toggleCheckAll(className) {
				is_checked = !is_checked;
				$("."+className).each( function() {
						this.checked = is_checked;
				});
			}

			function deleteItem(id) {
				if(confirm("<?php echo _t('이 글을 삭제하시겠습니까?');?>")) {
					$.ajax({
					  type: "POST",
					  url: '<?php echo $pluginURL;?>/delete.php',
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
				
				if(confirm("<?php echo _t('선택된 모든 글을 삭제하시겠습니까?');?>")) {
					$.ajax({
					  type: "POST",
					  url:  '<?php echo $pluginURL;?>/delete.php',
					  data: 'id=' + ids,
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

		<div class="title_wrap">
			<h3><?php echo _t('공지사항');?> <span class="cnt">(<?php echo $totalFeedItems;?>)</span></h3>
		</div>
		
		<div class="notice_wrap">
<?php
	$headers = array(array('title'=>_t('선택'),'class'=>'entrylist_select','width'=>'50px'),
					array('title'=>_t('날짜'),'class'=>'entrylist_date','width'=>'100px'),
					array('title'=>_t('제목'),'class'=>'entrylist_title','width'=>'790px'),
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
			
			// 글 선택
			array_push($data['datas'], array('class'=>'noticelist_select','data'=> '<input type="checkbox" class="postid" value="'.$post['id'].'" />' ));
	
			// 글 등록날짜		
			ob_start();
?>
			<?php echo date('y.m.d H:i:s', $post['written']);?><br />
			<span class="date_text">(<?php echo _f($date[0],$date[1]);?>)</span>
<?php
			$content = ob_get_contents();
			ob_end_clean();
			array_push($data['datas'], array('class'=>'noticelist_date','data'=> $content ));
			
			// 글 제목
			ob_start();
?>

<?php
			$desc = UTF8::lessenAsEm(str_replace('&nbsp;','',func::stripHTML($post['description'])),82);
			if(empty($desc)) {
				$desc = '<span class="empty">'._t('내용이 비어있거나 HTML로만 작성되어 있습니다.').'</span>';
			}	
			$isNew = Func::isNew($post['written'],1);
?>

			<div class="title"><?php echo UTF8::lessenAsEm(stripcslashes(func::stripHTML($post['title'])), 60);?> <?php echo ($isNew?' <img	src="'.$service['path'].'/images/admin/icon_new.gif" alt="new" align="absmiddle" class="new" />':'');?></div>
			<?php echo $desc?>
<?php
			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'noticelist_title','data'=> $content ));

			// 글 실행
			ob_start();
?>
			<a href="#" class="normalbutton" onclick="deleteItem(<?php echo $post['id'];?>); return false;"><span><?php echo _t('삭제');?></span></a>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			array_push($data['datas'], array('class'=>'noticelist_execute','data'=> $content ));
			
			array_push($datas, $data);
		}

	} else {
			array_push( $datas, array( 'class'=>"list_empty", 'datas'=>array(array('data'=>empty($keyword)?_t('공지사항이 없습니다.'):_t('검색된 공지사항이 없습니다.')) )) );
	}

	ob_start();
?>
		<div class="select">
			<a href="#" onclick="toggleCheckAll('postid'); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_arrow.gif" /></a>
		</div>
		<div class="action">
			<strong><a href="#" onclick="deleteAllItem('postid'); return false;"><?php echo _t('삭제');?></a></strong>
		</div>				
		<div class="clear"></div>
<?php
	$footers = ob_get_contents();
	ob_end_clean();

	echo makeTableBox('noticelist', $headers, $datas, $footers);	
?>
</div>

<div class="wrap">
	<br />
	<div class="paging">
		<?php echo outputPaging($paging, $params);?>
	</div>
</div>
<?php
			$input .= ob_get_contents();
			ob_end_clean();
		}

		return $input;
	}

	/* 공지사항 글 뽑기 */

	function getNoticeFeedItems($blogid, $page, $pageCount) {
		global $db, $database, $accessInfo;
		
		$ids = array();
		$result = $db->queryAll('SELECT exceptFeeditem FROM ' . $database['prefix'] . 'PluginNotice');
		foreach($result as $item) {
			array_push($ids, $item['exceptFeeditem']);
		}

		$sQuery = 'WHERE i.feed = ' . $blogid;
		if(count($ids) > 0) {
			$sQuery .= ' AND (i.id NOT IN ( ' . implode(',',$ids) . ' )) ';
		}

		$pageStart = ($page-1) * $pageCount; // 처음페이지 번호
		$feedList = $db->queryAll('SELECT i.id, i.permalink, i.title, i.written, i.visibility, i.description FROM '.$database['prefix'].'FeedItems i '.$sQuery.' ORDER BY i.written DESC LIMIT '.$pageStart.','.$pageCount);
		if (!list($totalFeedItems) = $db->pick('SELECT count(i.id) FROM '.$database['prefix'].'FeedItems i '.$sQuery)) {
			$totalFeedItems = 0;
		}

		return array($feedList, $totalFeedItems);

	}

?>