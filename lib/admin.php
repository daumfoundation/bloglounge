<?php	
	function drawAdminBoxBegin($class='') {
		if(!empty($class)) { $class = ' ' . $class; }

		$result = '<div class="box'.$class.'"><div class="box_l"><div class="box_r"><div class="box_t"><div class="box_b"><div class="box_lt"><div class="box_rt"><div class="box_lb"><div class="box_rb">';
		return $result;
	}

	function drawAdminBoxEnd() {
		$result = '</div></div></div></div></div></div></div></div></div>';
		return $result;
	}

	function drawAdminBox($class, $content) {
		$result = drawAdminBoxBegin($class);
		$result.= $content;
		$result.= drawAdminBoxEnd();

		return $result;
	}

	function drawGrayBoxBegin($class='') {
		if(!empty($class)) { $class = ' ' . $class; }

		$result = '<div class="box3'.$class.'"><div class="box3_l"><div class="box3_r"><div class="box3_t"><div class="box3_b"><div class="box3_lt"><div class="box3_rt"><div class="box3_lb"><div class="box3_rb">';
		return $result;
	}

	function drawGrayBoxEnd() {
		$result = '</div></div></div></div></div></div></div></div></div>';
		return $result;
	}

	function drawGrayBox($class, $content) {
		$result = drawGrayBoxBegin($class);
		$result.= $content;
		$result.= drawGrayBoxEnd();

		return $result;
	}

	function drawAdminTableBoxBegin($class='') {
		if(!empty($class)) { $class = ' ' . $class; }

		$result = '<div class="table_box'.$class.'"><div class="table_box_l"><div class="table_box_r"><div class="table_box_b"><div class="table_box_t"><div class="table_box_lb"><div class="table_box_rb"><div class="table_box_lt"><div class="table_box_rt">';
		return $result;
	}

	function drawAdminTableBoxEnd() {
		$result = '</div></div></div></div></div></div></div></div></div>';
		return $result;
	}

	function drawAdminTableBox($class, $content) {
		$result = drawAdminTableBoxBegin($class);
		$result.= $content;
		$result.= drawAdminTableBoxEnd();

		return $result;
	}

	function makeTableBox($class, $headers, $datas, $footers) {
		ob_start();
		
		echo drawAdminTableBoxBegin($class);
?>
	<div class="table_box_data">
		<table class="admin_table" cellspacing="0" cellpadding="0">
		<!-- header -->
		<thead class="headers">
			<tr>
<?php
		if(isset($headers)) {
			for($i=0;$i<count($headers);$i++) {
				$header = $headers[$i];

				$title = isset($header['title'])?$header['title']:'';
				$class = isset($header['class'])?$header['class']:'';
				$width = isset($header['width'])?$header['width']:'';

				$style = '';
				if(!empty($width) && ($width != 'auto')) {
					$style = ' style="width:' . $width .';"';
				}
?>
				<td class="<?php echo $class;?><?php echo $i<count($headers)-1?' sep':'';?>"<?php echo $style;?>>
					<?php echo $title;?>
				</td>
<?php
			}
		}	

?>			</tr>
		</thead>
<?php
		if(isset($datas)) {
?>		
		<tbody class="datas">
<?php
			$i = 1;
			$first = true;
			foreach($datas as $data) {
				$id = isset($data['id'])?$data['id']:'';		
				$is_empty = !(!isset($data['empty']) || ($data['empty'] === false));
				if(!$is_empty) {
					$class = 'list_item'.$i.' list_item'.($first?' list_first':'');
				} else {
					$class = '';
				}

				$class .= isset($data['class'])?' '.$data['class']:'';		
				$index = 0;
?>					
				<tr id="<?php echo $id;?>" class="<?php echo $class;?>">
<?php
				if($is_empty) {			
						$colspan = count($headers);
?>
						<td colspan="<?php echo $colspan;?>"></td>
<?php
				} else {
					for($i2=0;$i2<count($data['datas']);$i2++) {
						$item = $data['datas'][$i2];

						$item_id = isset($item['id'])?$item['id']:'';
						$item_class = isset($item['class'])?$item['class']:'';
						$item_data = isset($item['data'])?$item['data']:'';

						$width = $headers[$index++]['width'];
						$style = '';
						if(!empty($width) && ($width != 'auto')) {
							$style = ' style="width:' . $width .';"';
						}

						$colspan = 0;
						if($i2==count($data['datas'])-1) {
							$colspan = count($headers) - count($data['datas']);
							$style = '';
						}
?>
						<td <?php echo !empty($item_id)?' id="'.$item_id.'" ':'';?> <?php echo !empty($item_class)?' class="'.$item_class.'" ':'';?><?php echo $colspan==0?'':' colspan="'.($colspan+1).'"';?><?php echo $style;?>>
							<?php echo $item_data;?>
						</td>
<?php
					}
				}
?>
				</tr>
<?php
				if(!$is_empty) {
					$i++;		
					if($i>2) $i=1;
				}
				
				$first = false;
			}
?>
		</tbody>
<?php
		}
?>
		<tfoot class="footers">

			<tr>
				<td colspan="<?php echo count($headers);?>">			
<?php
		if(isset($footers)) {
				echo $footers;
		} else {
				echo '&nbsp';
		}
?> 
				</td>
			</tr>

		</tfoot>
		</table>
</div>
<?php
		echo drawAdminTableBoxEnd();

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
	
	function outputPaging($paging, $param = '') {
			global $accessInfo, $service;
			$path = $service['path'] . $accessInfo['subpath'];
			$result = '';
			if($paging['pagePrev'] != $paging['page']) {
				$result .= '<a href="'.$path.'/?page='.$paging['pagePrev'].$param.'" class="page_prev">'._t('이전').'</a>';
			}else {
				$result .= '<a href="#" class="page_prev page_disable">'._t('이전').'</a>';
			}

			for ($p=$paging['pageStart']; $p < $paging['pageEnd']+1; $p++) { 	
				if($p == $paging['page']) {
					$result .= '<a href="'.$path.'/?page='.$p.$param.'" class="selected">'.$p.'</a>';
				} else {
					$result .= '<a href="'.$path.'/?page='.$p.$param.'">'.$p.'</a>';
				}
			}
			if($paging['pageNext'] != $paging['page']) {
				$result .= '<a href="'.$path.'/?page='.$paging['pageNext'].$param.'" class="page_next">'._t('다음').'</a>';
			} else {
				$result .= '<a href="#" class="page_next page_disable">'._t('다음').'</a>';
			}
			return $result;
	}

	function addAppMessage($message) {
		global $session,$_SESSION;
		$_SESSION['_app_message_'] = $session['message'] = $message;
	}

	function readAppMessage() {
		global $session;
		return $session['message'];
	}

	function clearAppMessage() {
		global $session,$_SESSION;
		$session['message'] = '';
		unset($_SESSION['_app_message_']);
	}
?>