<?php
	function requireComponent($name) {
		if (!ereg('^[[:alnum:]]+[[:alnum:].]+$', $name)) {
			return false;
		}
		include_once(ROOT . '/components/'.$name.'.php');
	}

	function stripPath($path) {
		$path = rtrim($path, '/');
		while (strpos($path, '//') !== false)
			$path = str_replace('//', '/', $path);
		return $path;
	}

	function errorPrint($error) {
		switch($error) {
				case 1: return _t("입력항목 중 빠진 내용이 있습니다."); break;
				case 2: return _t("테이블 식별자에 사용할 수 없는 문자 혹은 숫자가 포함되어 있습니다."); break;
				case 3: return _t("데이터베이스 서버에 접속할 수 없습니다. 입력하신 내용을 확인해주세요."); break;
				case 4: return _t("테이블이 이미 존재합니다."); break;
				case 5: return _t("설정파일(config.php)을 생성할 수 없습니다. 이미 존재하는 파일이라면 삭제하셔야 새로 설치를 진행하실 수 있습니다."); break;
				case 6: return _t("테이블 생성에 실패했습니다."); break;
				case 7: return _t("설정파일(config.php)을 찾을 수 없어 업그레이드를 진행할 수 없습니다."); break;
				case 8: return _t("데이터베이스 서버에 접속할 수 없습니다. 설정파일(config.php) 내용을 확인해주세요."); break;
				case 9: return _t("필수 테이블이 존재하지 않아 업그레이드를 진행할 수 없습니다. 새로 설치해 주세요."); break;
				case 10: return _t("관리자 정보를 입력해주세요."); break;
				case 11: return _t("관리자 정보를 확인할 수 없습니다."); break;
				case 12: return _t("관리자 비밀번호가 잘못되었습니다."); break;
				case 13: return _t("관리자가 아닙니다."); break;
				default: return false;
		}
	}

	function errorPrintForConfigMake($error) {
		switch($error) {
				case 1: return _t("잘못된 접근입니다."); break;
				case 2: return _t("입력항목 중 빠진 내용이 있습니다."); break;
				case 3: return _t("테이블 식별자에 사용할 수 없는 문자 혹은 숫자가 포함되어 있습니다."); break;
				case 4: return _t("데이터베이스 서버에 접속할 수 없습니다. 입력하신 내용을 확인해주세요."); break;
				case 5: return _t("설정파일(config.php)을 생성할 수 없습니다. 이미 존재하는 파일이라면 삭제하셔야 새로 설치를 진행하실 수 있습니다."); break;							
				default: return false;
		}
	}

	requireComponent('Eolin.PHP.UnifiedEnvironment'); 
	requireComponent('LZ.PHP.Core');
	requireComponent('LZ.DB.Core');
	requireComponent('LZ.PHP.Locale');

	Locale::setDirectory(ROOT . '/language/locale');
	Locale::set('auto'); // set locale by accept-language header
?>