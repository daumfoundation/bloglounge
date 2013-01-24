<?php
	function exportFunction_iframe($params, $exportConfig) {
		global $export, $service, $config, $event;
		
		$page = isset($params['get']['page'])?$params['get']['page']:1;
		if($page <= 0) $page = 1;

		$thumbnail = isset($exportConfig) && isset($exportConfig['thumbnail']) ?  Validator::getBool($exportConfig['thumbnail']) : true;
		$pageCount = isset($exportConfig) && isset($exportConfig['count']) ?  $exportConfig['count'] : 10;
		$newWindow = isset($exportConfig) && isset($exportConfig['popup']) ?  Validator::getBool($exportConfig['popup']) : true;
		$categoryView = isset($exportConfig) && isset($exportConfig['category_view']) ?  Validator::getBool($exportConfig['category_view']) : false;
		$focusView = isset($exportConfig) && isset($exportConfig['focus_view']) ?  ($exportConfig['focus_view'] == 'focus' ? true : false) : false;
		
		if($focusView) {
			list($posts, $totalFeedItems) = FeedItem::getFeedItems('focus', 'y', '', $page, $pageCount);
		} else {
			if($categoryView) {
				$categoryValue = isset($exportConfig) && isset($exportConfig['category']) ?  $exportConfig['category'] : '';
				list($posts, $totalFeedItems) = FeedItem::getFeedItems('category', $categoryValue, '', $page, $pageCount);
			} else {
				list($posts, $totalFeedItems) = FeedItem::getFeedItems('', '', '', $page, $pageCount);
			}
		}
		$paging = Func::makePaging($page, $pageCount, $totalFeedItems);

		requireComponent('LZ.PHP.Media');

		ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo $config->title;?></title>
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $export->exportURL;?>/style.css" />
<link rel="shortcut icon" href="<?php echo $service['path'];?>/images/favicon.ico" />
</head>
<body>
	<div id="export_iframe_wrap">
		<ul>
<?php
		foreach($posts as $post) {	
			$post['thumbnail'] = '';
			if($media = Media::getMedia($post['thumbnailId'])) {
				$post['thumbnail'] = $media['thumbnail'];	
			}
			$thumbnailFile =  $event->on('Text.postThumbnail', Media::getMediaFile($post['thumbnail']));

			$post['description'] = func::stripHTML($post['description'].'>');
			if (substr($post['description'], -1) == '>') $post['description'] = substr($post['description'], 0, strlen($post['description']) - 1);
			$post_description = UTF8::lessenAsByte(func::htmltrim($post['description']), 300);
			if (strlen($post_description) == 0) $post_description = '<span class="empty">'._t('(글의 앞부분이 이미지 혹은 HTML 태그만으로 되어있습니다)').'</span>';					

			$post_description = $event->on('Text.postDescription', $post_description);

?>
			<li class="<?php echo empty($thumbnailFile)||!$thumbnail?'thumbnail_nonexistence':'';?>">
<?php
		if($thumbnail) {

			$link_url = $config->addressType == 'id' ? $service['path'].'/go/'.$post['id'] : $service['path'].'/go/'.$post['permalink'];

			if(!empty($post['thumbnail'])) {
?>
				<div class="thumbnail">
					<a href="<?php echo $link_url;?>" target="<?php echo $newWindow?'_blank':'_parent';?>"><img src="<?php echo $thumbnailFile;?>" alt="thumnail" /></a>
				</div>
<?php
			}
		}
?>
				<div class="data">
					<h3><a href="<?php echo $link_url;?>" target="<?php echo $newWindow?'_blank':'_parent';?>"><?php echo UTF8::clear($event->on('Text.postTitle', func::stripHTML($post['title'])));?></a></h3>
					<p><?php echo $post_description;?></p>
				</div>
				<div class="clear"></div>
			</li>
<?php
		}
?>
		</ul>

		<div class="paging">
			<?php echo func::printPaging($paging);?>
		</div>

	</div>
</body>
</html>
<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
?>