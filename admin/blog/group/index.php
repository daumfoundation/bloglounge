<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireAdmin();

	requireComponent('Bloglounge.Data.Groups');

	include ROOT. '/lib/piece/adminHeader.php';
	$groups = Group::getList();
	$groupCount = count($groups );

	$selectGroup = null;
	$selectGroupId = 0;

	if(isset($_GET['group'])) {
		$selectGroupId = $_GET['group'];
		$selectGroup = Group::getAll($selectGroupId);
	}
?>
	<script type="text/javascript">
		function groupAdd() {
			var name = $("#groupAddName");
			
			
			if(name.val() == "") {
				alert('<?php echo _t("그룹명을 입력해주세요.");?>');
				name.focus();
				return false;
			}

			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/groupAdd.php',
			  data: 'name=' + encodeURIComponent(name.val()) + '&rand=' + Math.random(),
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
		
		function groupModify(id) {
			var name = $("#groupModifyName");
			
			
			if(name.val() == "") {
				alert('<?php echo _t("그룹명을 입력해주세요.");?>');
				name.focus();
				return false;
			}

			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/groupModify.php',
			  data: 'id=' + id + '&name=' + encodeURIComponent(name.val()) + '&rand=' + Math.random(),
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
		
		function groupUp(id) {
			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/groupMove.php',
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
		
		function groupDown(id) {
			$.ajax({
			  type: "POST",
			  url: _path +'/service/feed/groupMove.php',
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
		
		function groupDelete(id) {
			if(confirm("<?php echo _t('삭제된 그룹은 복구하실 수 없습니다. \n\n그룹을 삭제하시겠습니까?');?>")) {
				$.ajax({
				  type: "POST",
				  url: _path +'/service/feed/groupDelete.php',
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
		<h3><?php echo _t("그룹");?> <span class="cnt">(<?php echo $groupCount;?>)</span></h3>
	</div>

	<div class="wrap contents_wrap">
		<div class="group_lists">
			<div class="listbox">
				<div class="title">
					<a href="./"><?php echo _t('분류목록');?></a>
				</div>
				<ul>
<?php
	if(count($groups)>0) {
		foreach($groups as $group) {
?>
		<li<?php echo $selectGroupId==$group['id']?' class="selected"':''?>>
			<div class="text">
				<a href="./?group=<?php echo $group['id'];?>"><?php echo UTF8::lessen($group['name'],20);?></a>
			</div>
			<div class="tools">
<?php
	if($selectGroupId==$group['id']) {
?>
				<a href="#" onclick="groupUp(<?php echo $group['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_up.gif" alt="<?php echo _t('위로');?>" align="absmiddle" /></a>
				<a href="#" onclick="groupDown(<?php echo $group['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_down.gif" alt="<?php echo _t('아래로');?>" align="absmiddle" /></a>
				<a href="#" onclick="groupDelete(<?php echo $group['id'];?>); return false;"><img src="<?php echo $service['path'];?>/images/admin/bt_del.gif" alt="<?php echo _t('삭제');?>" align="absmiddle" /></a>
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
	if(($selectGroupId>0) && ($selectGroup != null)) {
?>
			<div class="groupbox">
				<ul>
					<li><span class="title"><?php echo _t('분류명');?></span> : <?php echo $selectGroup['name'];?></li>				
					<li class="lastChild"><span class="title"><?php echo _t('연결블로그');?></span> : <?php echo $selectGroup['count'];?></li>
				</ul>
			</div>			
			
			<div class="groupbox">
				<a href="./"><?php echo _t('이곳을 클릭하시면 새로운 그룹을 추가하실 수 있습니다.');?></a>
			</div>
<?php
	} else {
?>
			<div class="helpbox">
				<?php echo _t('그룹을 선택하시면 해당 그룹을 수정하실 수 있습니다.');?>
			</div>
<?php
	}
?>
		</div>
		<div class="group_datas">
<?php
	if(($selectGroupId>0) && ($selectGroup != null)) {
?>

			<h4><?php echo _t('그룹수정');?></h4>
			<h5><?php echo _t('선택된 그룹을 수정합니다.');?></h5>

			<form method="post" onsubmit="groupModify(<?php echo $selectGroupId;?>); return false;">
				<dl>
					<dt><label for="groupModifyName"><?php echo _t('그룹명');?></label></dt>
					<dd><input id="groupModifyName" name="groupName" type="text" maxlength="100" class="input faderInput" value="<?php echo $selectGroup['name'];?>" /></dd>
				</dl>	
				<dl class="comments">
					<dt></dt>
					<dd><?php echo _t('그룹명을 입력해주세요.');?></dd>
				</dl>
				<span class="normalbutton"><input type="submit" value="<?php echo _t('수정완료');?>" /></span>
			</form>
	
<?php
	} else {
?>
			<h4><?php echo _t('그룹추가');?></h4>
			<h5><?php echo _t('새로운 그룹을 추가합니다.');?></h5>

			<form method="post" onsubmit="groupAdd(); return false;">
				<dl>
					<dt><label for="groupAddName"><?php echo _t('그룹명');?></label></dt>
					<dd><input id="groupAddName" name="groupName" type="text" maxlength="100" class="input faderInput" /></dd>
				</dl>			
				<dl class="comments">
					<dt></dt>
					<dd><?php echo _t('그룹명을 입력해주세요.');?></dd>
				</dl>
				<div class="clear"></div>
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
