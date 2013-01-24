<?php
	define('ROOT', '../..');
	include ROOT . '/lib/include.php';

	if(!empty($accessInfo['action'])) { // export domain

		requireComponent('Bloglounge.Model.Exports');
		
		$domainName = $accessInfo['action'];
		$actionName = $accessInfo['value'];

		$export = new Export;

		if(isset($export->case[$domainName])) {
			$programName = $export->case[$domainName]['program'];

			if(!empty($programName)) {
					if(file_exists(ROOT . '/exports/' . $programName . '/index.php')) {
						include_once(ROOT . '/exports/' . $programName . '/index.php');
						
						$functionName = '';
						if(empty($actionName)) {
							$functionName = $export->case[$domainName]['events']['default'];
						} else {
							$functionName = $export->case[$domainName]['events'][$actionName];
						}
						if (function_exists($functionName)) {
							if (!isset($_COOKIE['export_visited'])) {
								if ($config->countRobotVisit == 'n' && Stats::isKnownBot($_SERVER["HTTP_USER_AGENT"])) {
								} else {
									Export::updateCount($domainName);
								}

								setcookie("export_visited", "bloglounge", time() + 86400, "/", ((substr(strtolower($_SERVER['HTTP_HOST']), 0, 4) == 'www.') ? substr($_SERVER['HTTP_HOST'], 3) : $_SERVER['HTTP_HOST']));
							}	


							$params = array();

							$params['get'] = $_GET;
							$params['post'] = $_POST;

							if(!isset($config)) $config = new Settings;
							
							$export->exportURL = $service['path'] . '/exports/' . $programName;
							$content = call_user_func($functionName, $params, Export::getConfig($domainName));

							echo $content;
						} else {
							// 함수 없음
						}
				} else {
					// 해당되는 프로그램 없음
					header("Location: /");
					exit;
				}
			} else {
				 // 해당되는 프로그램 없음
				 header("Location: /");
				exit;
			}
		} else {
			// 해당되는 익스포트 도메인이 없음
			header("Location: /");
			exit;
		}

	}
?>