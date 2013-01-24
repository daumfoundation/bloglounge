<?php
	// show plugin info
	define('ROOT', '../../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	$id = $_GET['id'];

	$feed = FeedItem::getAll($id);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title></title>
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/common.css" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/admin_blog.css" />
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/common.js"></script>
<script type="text/javascript">
	$(function() {
		if($(document.body).height()==0) {
			var h = $("#entry_preview_data").height();

			var height = h + $("#entry_preview_button_wrap").height() + 55;
			$(document.body).height(height);
			parent.resizeEntryView('<?php echo $id;?>',height);
		} else {
			$("#entry_preview_data").height($(document.body).height() - $("#entry_preview_button_wrap").height() - 55);
		}
	});
</script>
</head>
<body>
	<div id="entry_preview_wrap">
		<div id="entry_preview_data">	
			<?php echo $feed['description'];?>
		</div>
		<div id="entry_preview_button_wrap">
			<a href="#" onclick="parent.hideEntryView('<?php echo $id;?>'); return false;"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_close.gif" alt="<?php echo _t('닫기');?>" /></a>
		</div>
	</div>
</body>
</html>