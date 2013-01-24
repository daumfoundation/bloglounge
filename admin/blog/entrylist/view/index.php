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
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $service['path'];?>/style/modal.css" />
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/common.js"></script>
</head>
<body style="background:#fafafa;">
	<div class="modal_title">
		<div class="title"><?php echo $feed['title'];?></div>
		<div class="close">x</div>
		<div class="clear"></div>
	</div>
	<div class="modal_preview_container">
		<div class="modal_description" style="background:#ffffff; height:506px; border-bottom:1px solid #ffffff; padding-top:5px; padding-bottom:5px; line-height:18px; font-size:12px; overflow-x:hidden; overflow-y:scroll;">	
			<?php echo $feed['description'];?>
		</div>

		<div class="modalclose_wrap">
			<a href="#" onclick="parent.hideModal(); return false;"><img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/bt_close.gif" alt="<?php echo _t('닫기');?>" /></a>
		</div>
	</div>
</body>
</html>