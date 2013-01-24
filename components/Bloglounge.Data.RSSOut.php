<?php

	class RSSOut {
		function stop() {
			requireComponent('LZ.PHP.XMLWriter');		
			$rssDir = ROOT . '/cache/rss';
			func::mkpath($rssDir);
			if (!is_dir($rssDir) || !is_writeable($rssDir))
				return false;		
			
			$myURL = 'http://'.$_SERVER['HTTP_HOST'].$service['path'];

			$xml = new XMLFile($rssDir.'/0.xml');
			$xml->startGroup('rss', array('version'=>'2.0'));

			$xml->startGroup('channel');
			$xml->write('title', htmlspecialchars($config->title));
			$xml->write('link', htmlspecialchars($myURL));
			$xml->write('description', htmlspecialchars($config->description));
			$xml->write('language', $config->language);
			$xml->write('pubDate', date("r", time()));
			$xml->write('generator', BLOGLOUNGE.' '.BLOGLOUNGE_VERSION.' '.BLOGLOUNGE_NAME);

				$xml->startGroup('item');		
					$xml->write('title', _t('RSS 출력이 중지되었습니다.'));
					$xml->write('link', $myURL);
					$xml->write('description', _t('운영자가 RSS 출력을 중지하였습니다. 운영자는 이를 환경설정 / 정책에서 수정하실 수 있습니다.'));
				$xml->endGroup();

			$xml->endAllGroups();
			$xml->close();
		}
		function refresh($type = 'recent') {
			global $database, $db, $service;
			requireComponent('LZ.PHP.XMLWriter');		

			$rssDir = ROOT . '/cache/rss';
			func::mkpath($rssDir);
			if (!is_dir($rssDir) || !is_writeable($rssDir))
				return false;

			$config = new Settings;
			$rssCount = $config->feeditemsOnRss;
			$myURL = 'http://'.$_SERVER['HTTP_HOST'].$service['path'];

			if($type == 'focus') {
				$xml = new XMLFile($rssDir.'/1_focus.xml');
			} else {
				$xml = new XMLFile($rssDir.'/1.xml');
			}

			$xml->startGroup('rss', array('version'=>'2.0'));

			$xml->startGroup('channel');
			$xml->write('title', htmlspecialchars($config->title) . ($type=='focus'?' : ' . _t('포커스 목록'):''));
			$xml->write('link', htmlspecialchars($myURL));
			$xml->write('description', htmlspecialchars($config->description));
			$xml->write('language', $config->language);
			$xml->write('pubDate', date("r", time()));
			$xml->write('generator', BLOGLOUNGE.' '.BLOGLOUNGE_VERSION.' '.BLOGLOUNGE_NAME);

			if (!Validator::is_empty($config->logo)) {
				$xml->startGroup('image');
				$xml->write('title', htmlspecialchars($config->title));
				$xml->write('url', htmlspecialchars($myURL.'/cache/logo/'.$config->logo));
				list($width, $height) = getimagesize(ROOT.'/cache/logo/'.$config->logo);
				$xml->write('width', $width);
				$xml->write('height', $height);
				$xml->write('description', '');
				$xml->endGroup();
			}

			if($type == 'focus') { 
				$result = $db->query("SELECT title, permalink, author, description, tags, written FROM {$database['prefix']}FeedItems WHERE allowRedistribute='y' AND focus='y' AND visibility = 'y' ORDER BY written DESC LIMIT 0,{$rssCount}");
			} else {
				$result = $db->query("SELECT title, permalink, author, description, tags, written FROM {$database['prefix']}FeedItems WHERE allowRedistribute='y' AND visibility = 'y' ORDER BY written DESC LIMIT 0,{$rssCount}");
			}

			if ($result) {
				while ($item = $db->fetch()) {
					$xml->startGroup('item');
					$xml->write('title', htmlspecialcharS($item->title));
					$xml->write('link', htmlspecialchars($item->permalink));
					$xml->write('description', htmlspecialchars($item->description));
					foreach (explode(',', $item->tags) as $tag) {
						$xml->write('category', $tag);
					}
					$xml->write('author', htmlspecialchars($item->author));
					$xml->write('guid', htmlspecialchars($item->permalink));
					$xml->write('pubDate', date("r", $item->written));
					$xml->endGroup();
				}
			}

			$xml->endAllGroups();
			$xml->close();

			return true;
		}
	}

?>