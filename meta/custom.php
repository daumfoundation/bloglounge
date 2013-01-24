<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	include ROOT . '/lib/begin.php';

	$customData = $event->on('Disp.custom');
	if(!empty($customData)) { // plugin
		$s_content = $customData;
		$skin->dress('content', $s_content);
	} else {
		$src_error = $skin->cutSkinTag('error');	

		if(!empty($src_error)) {		
			$s_error = '';
			$skin->dress('error', $src_error);
		} else {
			$s_error = '<div class="error_wrap">';
			$s_error .= '<h3>' . _t('페이지를 찾을 수 없습니다.') . '</h3>';
			$s_error .= _f('페이지 (http://%1)가 존재하지 않습니다.', $_SERVER['HTTP_HOST'].$service['path'].$accessInfo['fullpath']);
			$s_error .= '</div>';

			$skin->dress('content', $s_error);
		}
	}

	include ROOT . '/lib/end.php';
?>