<?php

	class RSSOut {
		function refresh() {
			global $database, $db, $service;
			requireComponent('LZ.PHP.XMLWriter');		

			$rssDir = ROOT . '/cache/rss';
			func::mkpath($rssDir);
			if (!is_dir($rssDir) || !is_writeable($rssDir))
				return false;

			$config = new Settings;
			$myURL = 'http://'.$_SERVER['HTTP_HOST'].$service['path'];

			$xml = new XMLFile($rssDir.'/1.xml');
			$xml->startGroup('rss', array('version'=>'2.0'));

			$xml->startGroup('channel');
			$xml->write('title', htmlspecialchars($config->title));
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

			if ($db->query("SELECT title, permalink, author, description, tags, written FROM {$database['prefix']}FeedItems WHERE allowRedistribute='y' ORDER BY written DESC LIMIT 0,10")) {
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