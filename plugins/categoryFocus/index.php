<?php
function getCategoryFocus($input, $config) {
	global $database, $db, $skin, $event, $service, $accessInfo;

	// 첫페이지에만 보이기 ..
	if(!(empty($accessInfo['controller']) && ($accessInfo['page']==1))) {
		return $input;
	}

	$pluginURL = $event->pluginURL;
	requireComponent('LZ.PHP.Media');

	switch($config['categoryType']) {
		case 'random':
			$categories = Category::getRandomList($config['categoryCount']);
		break;
		case 'custom':
			$categoryNames = explode(',', $config['customCategory']);
			$categories = array();
			foreach($categoryNames as $categoryName) {
				$item = Category::getByName($categoryName);
				if($item) {
					array_push($categories, $item);
				}
			}
		break;
		case 'recent':
		default:
			$categories = Category::getList($config['categoryCount']);
		break;
	}

	$categoryCount = count($categories);

	// css
	ob_start();
?>
<style type="text/css"> 
	table.categoryFocus { width:100%; border:1px solid #dbdbdb; }
		table.categoryFocus td { width:50%; vertical-align:top;  }
			table.categoryFocus td.left { border-right:1px solid #dedede; border-bottom:1px solid #dedede;}
			table.categoryFocus td.right { border-bottom:1px solid #dedede; }

		table.categoryFocus tr.lastChild td { border-bottom:0; }

		table.categoryFocus .title { padding:10px; padding-top:8px; padding-bottom:5px; }
			table.categoryFocus .title h3 { float:left; font-size:12px; font-weight:bold; margin:0; padding:0; color:#444; }
				table.categoryFocus .title h3 a { color:#444; text-decoration:none; }
				table.categoryFocus .title h3 a:hover { text-decoration:underline; }

			table.categoryFocus .title .more { float:right; }

		table.categoryFocus ul { list-style:none; margin:0; padding:10px; background:url(<?php echo $pluginURL;?>/images/bg_title.gif) repeat-x;  }

			table.categoryFocus ul li.detail { }
				table.categoryFocus ul li.detail .thumbnail { float:left; width:60px; margin-right:10px; }
					table.categoryFocus ul li.detail .thumbnail img { width:50px; border:1px solid #ddd; padding:2px; }		

				table.categoryFocus ul li.detail .data { float:left; width:292px; }
					table.categoryFocus ul li.detail .data h3 { font-size:13px; color:#595959; font-weight:bold; margin:0; margin-bottom:4px; }
						table.categoryFocus ul li.detail .data h3 a { color:#595959; text-decoration:none; }
						table.categoryFocus ul li.detail .data h3 a:hover { text-decoration:underline; }

					table.categoryFocus ul li.detail .data .desc { color:#aaa; line-height:16px; font-size:11px; }

				table.categoryFocus ul li.detail .data_full { width:362px; }

			table.categoryFocus ul li.list { margin-top:6px; }
				table.categoryFocus ul li.list a { color:#888; text-decoration:none; font-size:11px; }
				table.categoryFocus ul li.list a:hover { text-decoration:underline;}


	.categoryFocus_shadow { height:0px; font-size:0; border-top:1px solid #f5f5f5; margin-bottom:15px; }

</style>
<?php
	$css = ob_get_contents();
	ob_end_clean();
	
	$skin->css($css);

	// content

	ob_start();
?>
	<table class="categoryFocus" cellspacing="0" cellpadding="0">
<?php
		$end = round($categoryCount / 2);
		for($i=0;$i<$end;$i++) {
			$index = $i * 2;
?>
		<tr<?php echo $i==$end-1?' class="lastChild"':'';?>>
			<td class="left">
				<?php echo printCategoryFocusView($categories, $index, $config);?>
			</td>
			<td class="right">
				<?php echo printCategoryFocusView($categories, $index+1, $config);?>
			</td>
		</tr>
<?php
		}
?>
	</table>
	<div class="categoryFocus_shadow"></div>
<?php
	$result = ob_get_contents();
	ob_end_clean();

	return $input . $result;
}

function printCategoryFocusView($categories, $index, $config) {
	global $service, $event;
	$pluginURL = $event->pluginURL;

	if($index < 0 || $index >= count($categories)) {
		return false;
	}
	$category = $categories[$index];
	$result = '';

	$entries = FeedItem::getRecentFeedItemsByCategory($category['id'], $config['categoryFeedCount']+1);
?>
	<div class="title">
		<h3><a href="<?php echo $service['path'];?>/category/<?php echo func::encode($category['name']);?>"><?php echo $category['name'];?></a></h3>
		<div class="more">
			<a href="<?php echo $service['path'];?>/category/<?php echo func::encode($category['name']);?>"><img src="<?php echo $pluginURL;?>/images/bt_more.gif" alt="<?php echo _t('더보기');?>" /></a>
		</div>
		<div class="clear"></div>
	</div>
	<ul>
<?php
		if(count($entries)>0) {
			$entry = $entries[0];
?>
		<li class="detail">
<?php
		$thumbnailFile = '';
		if($media = Media::getMedia($entry['thumbnailId'])) {
			$thumbnailFile = Media::getMediaFile($media['thumbnail']);
		}

		if(!empty($thumbnailFile)) {
?>
			<div class="thumbnail">
				<img src="<?php echo $thumbnailFile;?>" alt="<?php echo _t('미리보기 이미지');?>" />
			</div>
			<div class="data">
				<h3><a href="<?php echo $service['path'];?>/go/<?php echo $entry['id'];?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($entry['title']),$config['categoryTitleLength']);?></a></h3>
				<div class="desc">
					<?php echo UTF8::lessenAsByte(func::stripHTML($entry['description']),$config['categoryDescLength']);?>
				</div>
			</div>
<?php
		} else {
?>
			<div class="data data_full">
				<h3><a href="<?php echo $service['path'];?>/go/<?php echo $entry['id'];?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($entry['title']),$config['categoryTitleLength']);?></a></h3>
				<div class="desc">
					<?php echo UTF8::lessenAsByte(func::stripHTML($entry['description']),$config['categoryDescLength']);?>
				</div>
			</div>
<?php
		}
?>
			<div class="clear"></div>
		</li>
<?php
		}
		for($i=1;$i<count($entries);$i++) {
			$entry = $entries[$i];
?>
		<li class="list"><a href="<?php echo $service['path'];?>/go/<?php echo $entry['id'];?>" target="_blank"><?php echo UTF8::lessenAsByte(func::stripHTML($entry['title']),$config['categoryTitleLength']);?></a></li>
<?php
		}
?>
	</ul>
<?php
	return $result;
}
?>