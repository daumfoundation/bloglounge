<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	requireComponent('Bloglounge.Data.Category');

	include ROOT. '/lib/piece/adminHeader.php';
	$categories = Category::getCategories();
	$categoryCount = count($categories);

	$selectCategory = null;
	$selectCategoryId = 0;

	if(isset($_GET['category'])) {
		$selectCategoryId = $_GET['category'];
		$selectCategory = Category::getAll($selectCategoryId);
	}
?>
	<script type="text/javascript">
		function categoryAdd() {
			var name = $("#categoryAddName");
			var filter = $("#categoryAddFilter");
			
			if(name.val() == "") {
				alert('<?php echo _t("분류명을 입력해주세요.");?>');
				name.focus();
				return false;
			}

			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/categoryAdd.php',
			  data: 'name=' + encodeURIComponent(name.val()) + '&filter=' + encodeURIComponent(filter.val()) + '&rand=' + Math.random(),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					var name = $("response message", msg).text();
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
		
		function categoryModify(id) {
			var name = $("#categoryModifyName");
			var filter = $("#categoryModifyFilter");
			
			if(name.val() == "") {
				alert('<?php echo _t("분류명을 입력해주세요.");?>');
				name.focus();
				return false;
			}

			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/categoryModify.php',
			  data: 'id=' + id + '&name=' + encodeURIComponent(name.val()) + '&filter=' + encodeURIComponent(filter.val()) + '&rand=' + Math.random(),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					var message = $("response message", msg).text();
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
		
		function categoryUp(id) {
			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/categoryMove.php',
			  data: 'id=' + id + '&type=up' + '&rand=' + Math.random(),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					var message = $("response message", msg).text();
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
		
		function categoryDown(id) {
			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/categoryMove.php',
			  data: 'id=' + id + '&type=down' + '&rand=' + Math.random(),
			  dataType: 'xml',
			  success: function(msg){		
				error = $("response error", msg).text();
				if(error == "0") {
					var message = $("response message", msg).text();
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
		
		function categoryDelete(id) {
			if(confirm("<?php echo _t('삭제된 분류는 복구하실 수 없습니다. \n\n분류를 삭제하시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/feed/categoryDelete.php',
				  data: 'id=' + id + '&rand=' + Math.random(),
				  dataType: 'xml',
				  success: function(msg){		
					error = $("response error", msg).text();
					if(error == "0") {
						var message = $("response message", msg).text();
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

	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/admin_category.css" type="text/css" />
	<div class="wrap title_wrap">
		<h3><?php echo _t("분류");?> <span class="cnt">(<?php echo $categoryCount;?>)</span></h3>
	</div>

	<div class="wrap contents_wrap">
		<div class="category_lists">
			<div class="listbox">
				<div class="title">
					<a href="./"><?php echo _t('분류목록');?></a>
				</div>
				<ul>
<?php
	if(count($categories)>0) {
		foreach($categories as $category) {
?>
		<li<?php echo $selectCategoryId==$category['id']?' class="selected"':''?>>
			<div class="text">
				<a href="./?category=<?php echo $category['id'];?>"><?php echo UTF8::lessen($category['name'],20);?></a>
			</div>
			<div class="tools">
<?php
	if($selectCategoryId==$category['id']) {
?>
				<a href="#" onclick="categoryUp(<?php echo $category['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_up.gif" alt="<?php echo _t('위로');?>" align="absmiddle" /></a>
				<a href="#" onclick="categoryDown(<?php echo $category['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_down.gif" alt="<?php echo _t('아래로');?>" align="absmiddle" /></a>
				<a href="#" onclick="categoryDelete(<?php echo $category['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_del.gif" alt="<?php echo _t('삭제');?>" align="absmiddle" /></a>
<?php
	}
?>
			</div>
			<div class="clear"></div>
		</li>
<?php
		}
	} else {
?>
		<li class="empty"><?php echo _t('분류가 없습니다.');?></li>
<?php
	}
?>
				</ul>
				<div class="clear"></div>
			</div>
			<div class="shadow"></div>

			<br />
	
<?php
	if(($selectCategoryId>0) && ($selectCategory != null)) {
?>
			<div class="categorybox">
				<ul>
					<li><span class="title"><?php echo _t('분류명');?></span> : <?php echo $selectCategory['name'];?></li>				
					<li class="lastChild"><span class="title"><?php echo _t('연결글수');?></span> : <?php echo $selectCategory['count'];?></li>
				</ul>
			</div>			
			
			<div class="categorybox">
				<a href="./"><?php echo _t('이곳을 클릭하시면 새로운 분류를 추가하실 수 있습니다.');?></a>
			</div>
<?php
	} else {
?>
			<div class="helpbox">
				<?php echo _t('분류를 선택하시면 해당 분류를 수정하실 수 있습니다.');?>
			</div>
<?php
	}
?>
		</div>
		<div class="category_datas">
<?php
	if(($selectCategoryId>0) && ($selectCategory != null)) {
?>

			<h4><?php echo _t('분류수정');?></h4>
			<h5><?php echo _t('선택된 분류를 수정합니다.');?></h5>

			<form method="post" onsubmit="categoryModify(<?php echo $selectCategoryId;?>); return false;">
				<dl>
					<dt><label for="categoryModifyName"><?php echo _t('분류명');?></label></dt>
					<dd><input id="categoryModifyName" name="categoryName" type="text" maxlength="100" class="input faderInput" value="<?php echo $selectCategory['name'];?>" /></dd>
				</dl>	
				<dl class="comments">
					<dt></dt>
					<dd><?php echo _t('분류명을 입력해주세요.');?></dd>
				</dl>	
				<dl>
					<dt><label for="categoryModifyFilter"><?php echo _t('자동분류');?></label></dt>
					<dd><input id="categoryModifyFilter" name="categoryFilter" type="text" class="input faderInput" value="<?php echo $selectCategory['filter'];?>" /></dd>
				</dl>	
				<dl class="comments">
					<dt></dt>
					<dd><?php echo _t('자동분류는 태그로 분류됩니다. 태그는 , 로 다중입력 가능합니다.');?></dd>
				</dl>
				<span class="normalbutton"><input type="submit" value="<?php echo _t('수정완료');?>" /></span>
			</form>
	
<?php
	} else {
?>
			<h4><?php echo _t('분류추가');?></h4>
			<h5><?php echo _t('새로운 분류를 추가합니다.');?></h5>

			<form method="post" onsubmit="categoryAdd(); return false;">
				<dl>
					<dt><label for="categoryAddName"><?php echo _t('분류명');?></label></dt>
					<dd><input id="categoryAddName" name="categoryName" type="text" maxlength="100" class="input faderInput" /></dd>
				</dl>			
				<dl class="comments">
					<dt></dt>
					<dd><?php echo _t('분류명을 입력해주세요.');?></dd>
				</dl>
				<dl>
					<dt><label for="categoryAddFilter"><?php echo _t('자동분류');?></label></dt>
					<dd><input id="categoryAddFilter" name="categoryFilter" type="text" class="input faderInput" /></dd>
				</dl>			
				<dl class="comments">
					<dt></dt>
					<dd><?php echo _t('자동분류는 태그로 분류됩니다. 태그는 , 로 다중입력 가능합니다.');?></dd>
				</dl>

				<span class="normalbutton"><input type="submit" value="<?php echo _t('추가');?>" /></span>
			</form>
<?php
	}
?>
		</div>
		<div class="clear"></div>
	</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>
