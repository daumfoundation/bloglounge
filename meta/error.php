<?php
	define('ROOT', '..');
	include ROOT . '/lib/include.php';
	include ROOT . '/lib/begin.php';

	$error = array('title'=>_t('페이지를 찾을 수 없습니다.'), 'description'=>_t('해당 페이지를 찾을 수 없습니다. 입력하신 페이지 주소가 정확한지 다시 한번 확인해보시기 바랍니다.'), 'type'=>'error404');
	include ROOT . '/lib/piece/error.php';
	include ROOT.'/lib/end.php';
?>