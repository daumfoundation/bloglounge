<?php
	if (!function_exists('htmlspecialchars_decode')) {
		function htmlspecialchars_decode($str, $options = ENT_COMPAT) {
			return strtr($str, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $options)));
		}
	}

	Class Feed {
		var $updated=0;

		function add($feedURL, $visibility = 'y', $filter = '', $filterType = 'tag', $group = '0') {
			global $database, $db, $session;
			if (empty($feedURL) || empty($session['id'])) {
				return false;
			}

			list($status, $feedInfo) = Feed::getRemoteFeed($feedURL);
			if ($status > 0) {
				return false;
			}

			$filter = implode(',', array_unique(func::array_trim((explode(',', $filter)))));
			if(!in_array($filterType, array('tag','title','tag+title'))) $filterType = 'tag';
			
			if(!empty($feedInfo['logo'])) {
				requireComponent('LZ.PHP.Media');
				$media = new Media();
				$feedInfo['logo'] = $media->downloadFile($feedInfo['logo'], ROOT.'/cache/feedlogo/');
			}

			foreach ($feedInfo as $key=>$value) {
				$feedInfo[$key] = $db->escape($value);
			}

			if (!$db->execute('INSERT INTO '.$database['prefix'].'Feeds (owner, `group`, xmlURL, xmlType, blogURL, title, description, language, lastUpdate, created, visibility, filter, filterType, logo) VALUES ("'.$session['id'].'", "'.$group.'", "'.$feedInfo['xmlURL'].'", "'.$feedInfo['xmlType'].'", "'.$feedInfo['blogURL'].'", "'.$feedInfo['title'].'", "'.$feedInfo['description'].'", "'.$feedInfo['language'].'", 0, UNIX_TIMESTAMP(), "'.$db->escape($visibility).'","'.$db->escape($filter).'", "'.$filterType.'", "'.$feedInfo['logo'].'")')) {
				return false;
			}	
			
			requireComponent('Bloglounge.Data.Groups');
			Group::rebuildCount($group);
			
			return $db->insertId();
		}

		function edit($feedId, $arguments) {
			global $database, $db;
			if (empty($feedId) || !preg_match("/^[0-9]+$/", $feedId) || empty($arguments) || !is_array($arguments)) {
				return false;
			}
			
			$feedVisibilityChanged = false;
			
			$old_group = '';
			$new_group = '';

			$modStack = array();
			foreach ($arguments as $key=>$value) {
				if (!Validator::enum($key, 'group, xmlURL,blogURL,title,description,language,lastUpdate,filter,filterType,autoUpdate,allowRedistribute,author,visibility,feedCount,everytimeUpdate,logo,isVerified')) 
					continue;
				if ($key == 'filter') {
					$filterArray = func::array_trim((explode(',', $value)));
					$value = implode(',', array_slice($filterArray, 0, 3));
					;
				} else if($key == 'visibility') {
					$feedVisibilityChanged = $value;
				} else if($key == 'group') {
					$group = Feed::get($feedId, 'group');
					if($group != $value) {
						$old_group = $group;
					} 
					
					$new_group = $value;
				}

				if (!Validator::enum(strtolower($value), 'unix_timestamp(),time(),date()')) {
					$value = '"'.$db->escape($value).'"';
				}
				array_push($modStack, '`'.$key.'`='.$value);
			}

			if (!count($modStack)) 
				return false;

			if($feedVisibilityChanged !== false) {
				$db->execute('UPDATE '.$database['prefix'].'FeedItems SET feedVisibility = "'.$feedVisibilityChanged .'" WHERE feed='.$feedId);
			}

			$modQuery = implode(',', $modStack);
			$result = ($db->execute('UPDATE '.$database['prefix'].'Feeds SET '.$modQuery.' WHERE id='.$feedId))?true:false;
			
			requireComponent('Bloglounge.Data.Groups');

			if(!empty($old_group)) {
				Group::rebuildCount($old_group);
			}
			if(!empty($new_group)) {
				Group::rebuildCount($new_group);
			}

			return $result;
		}

		function delete($feedId) {
			global $database, $db;
			if (empty($feedId) || !preg_match("/^[0-9]+$/", $feedId)) {
				return false;
			}
			if (!$db->execute("DELETE FROM {$database['prefix']}DeleteHistory WHERE feed='$feedId'"))
				return false;

			$old_group = Feed::get($feedId,'group');

			requireComponent('Bloglounge.Data.FeedItems');
			FeedItem::deleteByFeedId($feedId);	

			$result = ($db->execute('DELETE FROM '.$database['prefix'].'Feeds WHERE id='.$feedId))?true:false;
			
			requireComponent('Bloglounge.Data.Groups');
			Group::rebuildCount($old_group);

			return $result;
		}

		function get($feedId, $field) {
			global $database, $db;
			if (empty($feedId) || !preg_match("/^[0-9]+$/", $feedId)) {
				return false;
			}
			$result = $db->queryCell('SELECT `'.$field.'` FROM '.$database['prefix'].'Feeds WHERE id='.$feedId);
			return $result;
		}

		function gets($feedId, $fields) {
			global $database, $db;
			if (empty($feedId) || !preg_match("/^[0-9]+$/", $feedId)) {
				return false;
			}
			
			$result = array();
			$db->query('SELECT '.$fields.' FROM '.$database['prefix'].'Feeds WHERE id='.$feedId);
			$data = $db->fetchRow();
			foreach ($data as $row) 
				array_push($result, $row);
			$db->free();
			return $result;
		}

		function getAll($feedId) {
			global $database, $db;
			if (empty($feedId) || !preg_match("/^[0-9]+$/", $feedId)) {
				return false;
			}
			$db->query('SELECT * FROM '.$database['prefix'].'Feeds WHERE id='.$feedId);
			return $db->fetchArray();
		}

		function getFeedsAll($fields = 'id, title, blogURL') {	
			global $database, $db;
			return $db->queryAll('SELECT '.$fields.' FROM '.$database['prefix'].'Feeds');
		}

		function getIdList() {	
			global $database, $db;
			$ids = array();
			$result = $db->queryAll('SELECT id FROM '.$database['prefix'].'Feeds');
			foreach($result as $item)
				array_push($ids, $item['id']);
			return $ids;
		}

		function getIdListByOwner($owner) {	
			global $database, $db;
			if (empty($owner) || !preg_match("/^[0-9]+$/", $owner)) {
				return false;
			}
			$ids = array();
			$result = $db->queryAll('SELECT id FROM '.$database['prefix'].'Feeds WHERE owner = ' . $owner);
			foreach($result as $item)
				array_push($ids, $item['id']);
			return $ids;
		}

		function getIdByUrl($url) {
			global $database, $db;
			if (empty($url)) {
				return false;
			}
			$ids = array();
			$result = $db->queryCell('SELECT id FROM '.$database['prefix'].'Feeds WHERE url = ' . $url);
			if($result) {
				return $result;
			}
			return false;
		}

		function getRemoteFeed($url, $depth=0) {
			global $db;
			if($depth>3) {
				return array(2, null, null);
			}
			requireComponent('LZ.PHP.HTTPRequest');
			$request = new HTTPRequest();
			$xml = $request->getPage($url);
			if (empty($xml)) {
				return array(2, null, null);
			}
			$feed = array('xmlURL' => $url);
			
			$encoding = '';
			if (preg_match('/^<\?xml[^<]*\s+encoding=["\']?([\w-]+)["\']?/', $xml, $matches))
				$encoding = $matches[1];
			if (strcasecmp($encoding, 'utf-8') != 0) {
				$xml = UTF8::bring($xml, $encoding);
				$xml = preg_replace('/^(<\?xml[^<]*\s+encoding=)["\']?[\w-]+["\']?/', '$1"utf-8"', $xml, 1);
			}

			if(preg_match_all('/<meta[ \t].*?http-equiv\s*=\s*[\'"]?refresh.*?>/i', $xml, $matches)) { // 야후코리아 때문 ..
				foreach($matches[0] as $link) {
					$attributes = func::getAttributesFromString($link);
					if (isset($attributes['content'])) {
						$attributes = explode(';', $attributes['content']);
						$contentURL = substr($attributes[1],4);
						if(substr($contentURL,0,7) == 'http://') {
							$url = $contentURL;
						} else {
							$url = func::unionAddress($url,$contentURL);
						}
						if(!empty($url)) {
							return Feed::getRemoteFeed($url, ++$depth);
						}
					}
				}
			}

			$xmls = new XMLStruct();
			if (!$xmls->open($xml)) {
				if(preg_match_all('/<link .*?rel\s*=\s*[\'"]?alternate.*?>/i', $xml, $matches)) {
					foreach($matches[0] as $link) {
						$attributes = func::getAttributesFromString($link);
						if (isset($attributes['href'])) {
							$urlInfo = parse_url($url);
							$rssInfo = parse_url($attributes['href']);
							$rssURL = false;
							if (isset($rssInfo['scheme']) && $rssInfo['scheme'] == 'http')
								$rssURL = $attributes['href'];
							else if (isset($rssInfo['path'])) {
								if ($rssInfo['path']{0} == '/')
									$rssURL = "{$urlInfo['scheme']}://{$urlInfo['host']}{$rssInfo['path']}";							
								else
									$rssURL = "{$urlInfo['scheme']}://{$urlInfo['host']}".(isset($urlInfo['path']) ? rtrim($urlInfo['path'], '/') : '').'/'.$rssInfo['path'];
							}
							if ($rssURL && $url != $rssURL)
								return Feed::getRemoteFeed($rssURL);
						}
					}
				}		
				return array(3, null, null);
			}

			$xmlType = '';
			$feed['blogTool'] = Func::isWhatBlog($url);
			if ($xmls->getAttribute('/rss', 'version')) {	
				$xmlType = 'rss';
				$feed['blogURL'] = $xmls->getValue('/rss/channel/link');
				$feed['title'] = $xmls->getValue('/rss/channel/title');
				$feed['description'] = $xmls->getValue('/rss/channel/description');
				if (Validator::language($xmls->getValue('/rss/channel/language')))
					$feed['language'] = $xmls->getValue('/rss/channel/language');
				else if (Validator::language($xmls->getValue('/rss/channel/dc:language')))
					$feed['language'] = $xmls->getValue('/rss/channel/dc:language');
				else
					$feed['language'] = 'en-US';
				$feed['modified'] = gmmktime();
				$feed['logo'] = $xmls->getValue('/rss/channel/image/url');
			} else if ($xmls->doesExist('/feed')) {
				$xmlType = 'atom';
				$feed['blogURL'] = $xmls->getAttribute('/feed/link', 'href');
				$feed['title'] = $xmls->getValue('/feed/title');
				if (!$feed['description'] = $xmls->getValue('/feed/tagline'))
					$feed['description'] = $xmls->getValue('/feed/subtitle');
				if (Validator::language($xmls->getAttribute('/feed', 'xml:lang')))
					$feed['language'] = $xmls->getAttribute('/feed', 'xml:lang');
				else
					$feed['language'] = 'en-US';
				$feed['modified'] = gmmktime();
			} else if ($xmls->getAttribute('/rdf:RDF', 'xmlns')) {
				$xmlType = 'rss';
				if ($xmls->getAttribute('/rdf:RDF/channel/link', 'href'))
					$feed['blogURL'] = $xmls->getAttribute('/rdf:RDF/channel/link', 'href');
				else if ($xmls->getValue('/rdf:RDF/channel/link'))
					$feed['blogURL'] = $xmls->getValue('/rdf:RDF/channel/link');
				else
					$feed['blogURL'] = '';
				$feed['title'] = $xmls->getValue('/rdf:RDF/channel/title');
				$feed['description'] = $xmls->getValue('/rdf:RDF/channel/description');
				if (Validator::language($xmls->getValue('/rdf:RDF/channel/dc:language')))
					$feed['language'] = $xmls->getValue('/rdf:RDF/channel/dc:language');
				else if (Validator::language($xmls->getAttribute('/rdf:RDF', 'xml:lang')))
					$feed['language'] = $xmls->getAttribute('/rdf:RDF', 'xml:lang');
				else
					$feed['language'] = 'en-US';
				$feed['modified'] = gmmktime();
			} else
				return array(3, null, null, null);
			
			$feed['xmlURL'] = $db->escape($db->lessen(UTF8::correct($feed['xmlURL'])));
			$feed['xmlType'] = $xmlType;
			$feed['blogURL'] = $db->escape($db->lessen(UTF8::correct($feed['blogURL'])));
			$feed['title'] = (empty($feed['title']))?_t('제목없음'):$db->escape($db->lessen(UTF8::correct($feed['title'])));
			$feed['description'] = $db->escape($db->lessen(UTF8::correct(func::stripHTML($feed['description']))));
			$feed['language'] = $db->escape($db->lessen(UTF8::correct($feed['language']), 255));

			return array(0, $feed, $xml, $xmlType);
		}

		function getFeedItems($xml) {		
			if (preg_match('/^<\?xml[^<]*\s+encoding=["\']?([\w-]+)["\']?/', $xml, $matches)) // kor env
				$encoding = $matches[1];
			if (strcasecmp($encoding, 'euc-kr') == 0) {
				$xml = UTF8::bring($xml, $encoding);
				$xml = preg_replace('/^(<\?xml[^<]*\s+encoding=)["\']?[\w-]+["\']?/', '$1"utf-8"', $xml, 1);
			}

			$xmls=new XMLStruct();
			if (!$xmls->open($xml))
				return false;

			$items = array();

			if ($xmls->getAttribute('/rss','version')){ // rss element must have version attribute
				for ($i=1;$link=$xmls->getValue("/rss/channel/item[$i]/link");$i++){
					$item=array('permalink'=>rawurldecode($link));
					if (!$item['author']=$xmls->getValue("/rss/channel/item[$i]/author"))
						$item['author']=$xmls->getValue("/rss/channel/item[$i]/dc:creator");
					$item['title']=$xmls->getValue("/rss/channel/item[$i]/title");
					if (!$item['description']=$xmls->getValue("/rss/channel/item[$i]/content:encoded"))
						$item['description']=htmlspecialchars_decode($xmls->getValue("/rss/channel/item[$i]/description"));
					$item['tags']=array();
					for ($j=1;$tag=$xmls->getValue("/rss/channel/item[$i]/category[$j]");$j++)
						if (!empty($tag)) {
						//	array_push($item['tags'],$tag);
							$tags = explode('/', $tag); // allblog, blogkorea types
							foreach($tags as $tag) {
								array_push($item['tags'], trim($tag));
							}
						}

					for ($j=1;$tag=$xmls->getValue("/rss/channel/item[$i]/subject[$j]");$j++)
						if (!empty($tag))
							array_push($item['tags'],$tag);
					if ($youtubeTags = $xmls->getValue("/rss/channel/item[$i]/media:category")) { // for Youtube,Flickr Feed
						array_push($item['tags'], ''); // blank. first tag not equals category
						foreach (explode(' ', $youtubeTags) as $tag) {
							$tag = trim($tag);
							if(!empty($tag))
								array_push($item['tags'], $tag);
						}
					}

					$item['enclosures']=array();
					for ($j=1;$result=$xmls->getAttributes("/rss/channel/item[$i]/enclosure[$j]",array('url','type'));$j++) {
						if (!empty($result)) {
							array_push($item['enclosures'],array('url'=>$result[0],'type'=>$result[1]));
						}
					}
					$flickrContent=$xmls->getAttributes("/rss/channel/item[$i]/media:content[$j]",array('url','type')); // for flickr feed
					if(!empty($flickrContent)) {
							array_push($item['enclosures'],array('url'=>$flickrContent[0],'type'=>$flickrContent[1]));
					}

					if ($xmls->getValue("/rss/channel/item[$i]/pubDate"))
						$item['written']=Feed::parseDate($xmls->getValue("/rss/channel/item[$i]/pubDate"));
					elseif ($xmls->getValue("/rss/channel/item[$i]/dc:date"))
						$item['written']=Feed::parseDate($xmls->getValue("/rss/channel/item[$i]/dc:date"));
					else
						$item['written']=0;
					if (!$item['generator']=$xmls->getValue("/rss/channel/generator")) {
						if (strpos($item['permalink'], 'tvpot.daum.net') !== false)
							$item['generator'] = 'Daum Tvpot';
						else 
							$item['generator'] = 'Unknown';
					}
					if (!$item['guid']=$xmls->getValue("/rss/channel/item[$i]/guid"))
						$item['guid'] = $item['permalink'];
					
					array_push($items, $item);
				}
			} elseif ($xmls->doesExist('/feed')){ // atom 0.3
				for ($i=1;$link=$xmls->getValue("/feed/entry[$i]/id");$i++){
					$item['enclosures']=array();

					for ($j=1;$rel=$xmls->getAttribute("/feed/entry[$i]/link[$j]",'rel');$j++){
						if ($rel=='alternate'){
							$link=$xmls->getAttribute("/feed/entry[$i]/link[$j]",'href');
							break;
						} else if($rel=='enclosure') {
							$result = $xmls->getAttributes("/feed/entry[$i]/link[$j]",array('href','type'));
							if($result) {
								array_push($item['enclosures'],array('url'=>$result[0],'type'=>$result[1]));
							}
						}
					}
					$item=array('permalink'=>rawurldecode($link));
					$item['author']=$xmls->getValue("/feed/entry[$i]/author/name");
					$item['title']=$xmls->getValue("/feed/entry[$i]/title");
					if (!$item['description']=htmlspecialchars_decode($xmls->getValue("/feed/entry[$i]/content")))
						$item['description']=htmlspecialchars_decode($xmls->getValue("/feed/entry[$i]/summary"));
					$item['tags']=array();
					for ($j=1;$tag=$xmls->getValue("/feed/entry[$i]/dc:subject[$j]");$j++) {
						if (!empty($tag)) array_push($item['tags'],trim($tag));
					}
					for ($j=1;$tag=$xmls->getAttribute("/feed/entry[$i]/category[$j]", 'term');$j++) {
						if (!empty($tag)) array_push($item['tags'],trim($tag));
					}
					if (!$item['written']= $xmls->getValue("/feed/entry[$i]/issued")) {
						if (!$item['written'] = $xmls->getValue("/feed/entry[$i]/published")) {
							$item['written'] = $xmls->getValue("/feed/entry[$i]/updated");
						}
					}
					$item['written'] = Feed::parseDate($item['written']);
					if (!$item['generator'] = $xmls->getValue("/feed/generator"))
						$item['generator'] = 'Unknown';
	
					array_push($items, $item);
				}
			} elseif ($xmls->getAttribute('/rdf:RDF','xmlns')){ // rss 1.0, rdf
				for ($i=1;$link=$xmls->getValue("/rdf:RDF/item[$i]/link");$i++){
					$item=array('permalink'=>rawurldecode($link));
					if (!$item['author']=$xmls->getValue("/rdf:RDF/item[$i]/dc:creator"))
						$item['author']=$xmls->getValue("/rdf:RDF/item[$i]/author"); // for NaverBlog rss 1.0
					$item['title']=$xmls->getValue("/rdf:RDF/item[$i]/title");
					if (!$item['description']=$xmls->getValue("/rdf:RDF/item[$i]/content:encoded"))
						$item['description']=htmlspecialchars_decode($xmls->getValue("/rdf:RDF/item[$i]/description"));
					$item['tags']=array();
					$item['enclosures']=array();
					$item['written']=Feed::parseDate($xmls->getValue("/rdf:RDF/item[$i]/dc:date"));

					array_push($items, $item);
				}
			} else
				return false;

			return $items;
		}

		
		function verifyFeed($feedURL){
			global $database, $db, $event;

			if (preg_match("/^[0-9]+$/", $feedURL))
				$feedURL = $db->queryCell('SELECT xmlURL FROM '.$database['prefix'].'Feeds WHERE id="'.$feedURL.'"');

			list($feedId, $isVerified, $owner) = $db->pick('SELECT id, isVerified, owner FROM '.$database['prefix'].'Feeds WHERE xmlURL="'.$feedURL.'"');

			$result = $event->on('Add.checkFeed', array($feedURL, $feedId));
			if(is_array($result) && count($result)>=3) {
				list($status, $feed, $xml) = $result;
			} else {
				$feedURL = trim('http://' . str_replace('http://','',$feedURL));
				list($status, $feed, $xml) = Feed::getRemoteFeed($feedURL);
			}

			if(Validator::getBool(Settings::get('useVerifier')) && $owner != 1 && !Validator::getBool($isVerified)) {
				if(!$this->checkVerifier($feedId,$feedURL,$xml)) {
					return array(false, $feed['title'], false);
				} else {
					$db->execute("UPDATE {$database['prefix']}Feeds SET isVerified = 'y' WHERE xmlURL = '{$feedURL}'");
				}
			}

			return array($result, $feed['title'], true);
		}

		function updateFeed($feedURL){
			global $database, $db, $event;

			if (preg_match("/^[0-9]+$/", $feedURL))
				$feedURL = $db->queryCell('SELECT xmlURL FROM '.$database['prefix'].'Feeds WHERE id="'.$feedURL.'"');

			list($feedId, $lastUpdate, $autoUpdate, $feedVisibility, $isVerified, $owner) = $db->pick('SELECT id, lastUpdate, autoUpdate, visibility, isVerified, owner FROM '.$database['prefix'].'Feeds WHERE xmlURL="'.$feedURL.'"');

			$result = $event->on('Add.checkFeed', array($feedURL, $feedId));
			if(is_array($result) && count($result)>=3) {
				list($status, $feed, $xml) = $result;
			} else {
				$feedURL = trim('http://' . str_replace('http://','',$feedURL));
				list($status, $feed, $xml) = Feed::getRemoteFeed($feedURL);
			}

			if ($status > 0){
				$db->execute("UPDATE {$database['prefix']}Feeds SET lastUpdate = ".gmmktime()." WHERE xmlURL = '{$feedURL}'");
				return array(0, $db->queryCell('SELECT title FROM '.$database['prefix'].'Feeds WHERE xmlURL = "' . $feedURL . '"'), 0);
			} else{
				$sQuery = (Validator::getBool($autoUpdate)) ? "title = '{$feed['title']}', description = '{$feed['description']}', " : '';
				$db->execute("UPDATE {$database['prefix']}Feeds SET blogURL = '{$feed['blogURL']}', $sQuery language = '{$feed['language']}', lastUpdate = ".gmmktime()." WHERE xmlURL = '{$feedURL}'");

				if(Validator::getBool(Settings::get('useVerifier')) && $owner != 1 && !Validator::getBool($isVerified)) {
					if(!$this->checkVerifier($feedId,$feedURL,$xml)) {
						return array(false, $feed['title']);
					} else {
						$db->execute("UPDATE {$database['prefix']}Feeds SET isVerified = 'y' WHERE xmlURL = '{$feedURL}'");
					}
				}

				$this->updated = 0;
				$result = $this->saveFeedItems($feedId,$feedVisibility,$xml)?0:1;
				return array($result, $feed['title'], $this->updated);
			}
		}

		function updateAllFeeds() {
			global $database, $db;
			$result = $db->query('SELECT xmlURL FROM '.$database['prefix'].'Feeds ORDER BY title');
			while ($feed = $db->fetch($result)) {
				$this->updateFeed($feed->xmlURL);
			}
			$db->free();
			return true;
		}
		
		function updateEveryTimeFeed() {	
			global $database, $db;
			$result = $db->query('SELECT xmlURL FROM '.$database['prefix'].'Feeds WHERE everytimeUpdate = "y" ORDER BY title');
			while ($feed = $db->fetch($result)) {
				$this->updateFeed($feed->xmlURL);
			}
			$db->free();
			return true;
		}

		function updateNextFeed(){
			global $database, $db;

			list($updateCycle, $restrictJoin) = Settings::gets('updateCycle,restrictJoin');
			if ($updateCycle!=0){
				$notinStr = '';
				if (Validator::getBool($restrictJoin)) {
					$notin = array();
					$result = $db->query("SELECT id FROM {$database['prefix']}Users WHERE is_accepted = 'n'");
					while ($item = $db->fetch($result)) {
						array_push($notin, "'{$item->id}'");
					}
					$db->free();
					if(count($notin) > 0)
						$notinStr = ' owner NOT IN ('.implode(',', $notin).') AND ';
				}

				if ($feedURL = $db->queryCell("SELECT xmlURL FROM {$database['prefix']}Feeds WHERE {$notinStr} lastUpdate < ".(gmmktime()-round($updateCycle*60))." ORDER BY lastUpdate ASC LIMIT 1")) {
					$result = $this->updateFeed($feedURL);
					return array(!$result[0],$result[1],$result[2],$feedURL); // status, title, updated, feedUrl
				}
			}
			return array(0,_t('모든 블로그가 최신상태입니다.'));
		}

		function updateRandomFeed(){
			global $database, $db;

			list($updateCycle, $restrictJoin) = Settings::gets('updateCycle,restrictJoin');
			if ($updateCycle!=0){
				$notinStr = '';
				if (Validator::getBool($restrictJoin)) {
					$notin = array();
					$result = $db->query("SELECT id FROM {$database['prefix']}Users WHERE is_accepted = 'n'");
					while ($item = $db->fetch($result)) {
						array_push($notin, "'{$item->id}'");
					}
					$db->free();
					if(count($notin) > 0)
						$notinStr = ' owner NOT IN ('.implode(',', $notin).') AND ';
				}

				if ($feedURL = $db->queryCell("SELECT xmlURL FROM {$database['prefix']}Feeds WHERE {$notinStr} lastUpdate < ".(gmmktime()-round($updateCycle*60))." ORDER BY RAND() LIMIT 1")) {
					$result = $this->updateFeed($feedURL);
					return array($result[0],$result[1],$result[2],$feedURL); // status, title, updated, feedUrl
				}
			}
			return array(1,'No feeds to update');
		}

		function getVerifier($xmlURL) {
			$verifier = 'v'.substr(md5($_SERVER['HTTP_HOST'].'|'.$xmlURL),0,8);
			return $verifier;
		}

		function checkVerifier($feedId, $xmlURL, $xml) {
			global $database, $db;

			$result = $this->getFeedItems($xml);
			if($result === false) return false; 
			else {
				list($cUseVerifier,$cVerfierType, $cVerifier) = Settings::gets('useVerifier,verifierType,verifier');
				if(!Validator::getBool($cUseVerifier)) return false;

				$verifier = $this->getVerifier($xmlURL);
				if($cVerfierType == 'custom') {
					$verifier = $cVerifier;
				}

				foreach($result as $item) {
					$tags = implode(', ',$item['tags']);
					if(strpos($item['title'],$verifier) !== false) return true;
					if(strpos($item['description'], $verifier) !== false) return true;
					if(strpos($tags, $verifier) !== false) return true;
				}
			}

			return false;
		}

		function saveFeedItems($feedId,	$feedVisibility, $xml, $callbackName = null){
			global $database, $db, $event;

			if (isset($callbackName)) {
				$callback = array();
				foreach (explode('::', $callbackName) as $z)
					array_push($callback, $z);
			}

			$result = $this->getFeedItems($xml);
			if($result === false) return false; 
			else {
				foreach($result as $item) {
					if (!isset($callback))
						$this->saveFeedItem($feedId, $feedVisibility, $item);
					else
						call_user_func($callback, $feedId, $item);
				}
			}

			if (!isset($callback)) {
				$deadLine=0;
				$feedLife =  $db->queryCell("SELECT archivePeriod FROM {$database['prefix']}Settings");

				if ($feedLife>0) {
					$deadLine=gmmktime()-$feedLife*86400;
				}
				$db->execute('DELETE FROM '.$database['prefix'].'FeedItems WHERE written < '.$deadLine);
			}

			$event->on('Add.updateFeedItems', array($feedId, $result));

			return true;
		}

		function saveFeedItem($feedId,$feedVisibility,$item){
			global $database, $db, $event;

			$db->query("SELECT id FROM {$database['prefix']}DeleteHistory WHERE feed='$feedId' and permalink='{$item['permalink']}'");
			if ($db->numRows() > 0) 
				return false;
	
			if ($item['written']>gmmktime()+86400)
				return false;

			$item['title']=$db->escape($db->lessen(UTF8::correct($item['title'])));

			list($useRssOut) = Settings::gets('useRssOut');
		
			list($feedCreated,$localFilter,$localFilterType) = Feed::gets($feedId, 'created,filter,filterType');

			$tagString=$db->escape($db->lessen(UTF8::correct(implode(', ',$item['tags']))));

			list($globalFilter,$blackFilter,$globalFilterType,$blackFilterType) = Settings::gets('filter,blackfilter,filterType,blackfilterType');
			$filter = empty($globalFilter)?$localFilter:$globalFilter;
			$filterType = empty($globalFilter)?$localFilterType:$globalFilterType;


			if (!Validator::is_empty($filter)) {
				$filtered = true;				
				$allowTags = explode(',', $filter);

				if($filterType == 'tag' || $filterType == 'tag+title') {					
					foreach ($allowTags as $ftag) {
						if (Validator::enum($ftag, $tagString)) {
							$filtered = false;
							break;
						}
					}
				}

				if($filtered && ($filterType == 'title' || $filterType == 'tag+title')) {
					foreach ($allowTags as $ftag) {
						if(strpos($item['title'],$ftag)!==false) {
							$filtered = false;
							break;
						}
					}
				}

				if ($filtered) return false;
			}

			if (!Validator::is_empty($blackFilter)) {
				$filtered = false;
				$denyTags = explode(',', $blackFilter);
				if($blackFilterType == 'tag' || $blackFilterType == 'tag+title') {					
					foreach ($denyTags as $ftag) {
						if (Validator::enum($ftag, $tagString)) {
							$filtered = true;
							break;
						}
					}
				}
				if($filtered && ($filterType == 'title' || $filterType == 'tag+title')) {
					foreach ($denyTags as $ftag) {
						if(strpos($item['title'],$ftag)!==false) {
							$filtered = true;
							break;
						}
					}
				}

				if ($filtered) return false;
			}

			if (preg_match('/\((.[^\)]+)\)$/Ui', trim($item['author']), $_matches)) $item['author'] = $_matches[1];
			$item['author']=$db->escape($db->lessen(UTF8::correct($item['author'])));
			$item['permalink']=$db->escape($db->lessen(UTF8::correct($item['permalink'])));
			$item['description']=$db->escape($db->lessen(UTF8::correct(trim($item['description'])),65535));
			
			$enclosures = array();
			foreach($item['enclosures'] as $en) {
				array_push($enclosures, $en['url']);
			}

			$enclosureString=$db->escape($db->lessen(UTF8::correct(implode('|',$enclosures))));				

			$deadLine=0;
			$feedLife = Settings::get('archivePeriod');
			if ($feedLife > 0) $deadLine=gmmktime()-($feedLife*86400);

			requireComponent('Bloglounge.Data.FeedItems');

			$oldTags = null;
			$id = FeedItem::getIdByURL($item['permalink']);
			if($id === false && isset($item['guid'])) {
				$item['guid']=$db->escape($db->lessen(UTF8::correct($item['guid'])));
				$id = FeedItem::getIdByURL($item['guid']);
			}

			$item['author'] = Feed::getAuthor($item, $feedId, $id);
			$item['title'] = Feed::getTitle($item, $feedId, $id);
			$item['title'] = html_entity_decode($item['title'],ENT_QUOTES,"UTF-8");

			$affected = 0;
			$isRebuildData = false;

			$summarySave = Settings::get('summarySave');
			$description = $item['description'];
			$description = html_entity_decode($description,ENT_QUOTES,"UTF-8");
			if(Validator::getBool($summarySave)) { // summarySave
				$description = func::stripHTML($item['description'].'>');
				if (substr($description, -1) == '>') $description = substr($description, 0, strlen($description) - 1);
				$description = $db->lessen(func::htmltrim($description), 1000, '');
			}
			
			if (preg_match("/^[0-9]+$/",$id)) {
				$baseItem = FeedItem::getFeedItem($id);
				$baseItem['title']=$db->escape(UTF8::correct($baseItem['title']));
				$baseItem['description']=$db->escape(UTF8::correct(trim($baseItem['description'])));

				if(($baseItem['title']!=$item['title']) || (strlen($baseItem['description']) != strlen($item['description']))) {
					$isRebuildData = true;
					$tags = FeedItem::get($id, 'tags');
					requireComponent('LZ.PHP.Media');
					Media::delete($id);

					$oldTags = func::array_trim(explode(',', $tags));
					$db->execute("UPDATE {$database['prefix']}FeedItems SET author = '{$item['author']}', title = '{$item['title']}', description = '{$description}', tags = '$tagString', enclosure = '$enclosureString', written = {$item['written']} WHERE id = $id");
				}
			} else {
				if ($item['written']==0)
					$item['written']=gmmktime();
				if ($item['written']>$deadLine) {
					$db->execute("INSERT INTO {$database['prefix']}FeedItems (feed, author, permalink, title, description, tags, enclosure, written, feedVisibility) VALUES ($feedId, '{$item['author']}', '{$item['permalink']}', '{$item['title']}', '{$description}', '$tagString', '$enclosureString', {$item['written']},'{$feedVisibility}')");

					$id =$db->insertId();
					$db->execute('UPDATE '.$database['prefix'].'Feeds SET feedCount=feedCount+1 WHERE id="'.$feedId.'"');
					if (isset($this)) $this->updated++;
				}
				$isRebuildData = true;
			}			

			if(Validator::getBool(Settings::get('saveImages'))) {
				if($description = FeedItem::saveImages($feedId, $id, $item)) {
					$db->execute("UPDATE {$database['prefix']}FeedItems SET description = '{$description}' WHERE id = $id");
				}
			}
			
			$item = $event->on('Add.updateFeedItem', array($feedId, $id, $item));
			if(count($item)==3) $item = $item[2];
	
			$result = false;
			if($isRebuildData) {
				requireComponent('Bloglounge.Data.Groups');
				GroupCategory::buildGroupCategory($id, $feedId, $item['tags']);

				Tag::buildTagIndex($id, $item['tags'], $oldTags);
					

				Category::buildCategoryRelations($id, $item['tags'], $oldTags);
				
				$isSaveThumbnail = FeedItem::cacheThumbnail($id, $item);

				// 썸네일 저장 이벤트
				$event->on('Add.thumbnailSave',array($item, $feedId, $id, $isSaveThumbnail));
				
				$result = true;
			}

			return $result;
		}

		function parseDate($str){
			if (preg_match('/^(\d{4})년 (\d{2})월 (\d{2})일  (\d{2}):(\d{2}):(\d{2})$/',$str,$matches))
				return Feed::parseDate("{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}");
			if (preg_match('/^(\d{2})-(\d{2})-(\d{4}) (\d{2}):(\d{2})$/',$str,$matches))
				return Feed::parseDate("{$matches[3]}-{$matches[1]}-{$matches[2]} {$matches[4]}:{$matches[5]}:00}");
			if (empty($str))
				return 0;
			$time=strtotime($str);
			if ($time!==-1)
				return $time;
			$gmt=(substr($str,strpos($str,"GMT"))=="GMT")?9:0;
			$str=str_replace("년 ","-",$str);
			$str=str_replace("월 ","-",$str);
			$str=str_replace("일 ","",$str);
			$str=str_replace("GMT","",$str);
			$str=str_replace("KST","+0900",$str);
			if (preg_match("/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})Z/", $str, $matches))
				return strtotime("{$matches[1]} {$matches[2]} +0000");
			if (strpos($str,"T")){
				list($date,$time)=explode("T",$str);
				list($y,$m,$d)=explode("-",$date);
				list($time)=explode("+",$time);
				@list($h,$i,$s)=explode(":",$time);
			} elseif (strpos($str,":")&&strpos($str,"-")){
				list($str)=explode(".",$str);
				list($date,$time)=explode(" ",$str);
				list($y,$m,$d)=explode("-",$date);
				if ($d>1900){
					$t=$y;
					$y=$d;
					$d=$m;
					$m=$t;
				}
				@list($h,$i,$s)=explode(":",$time);
			} elseif (preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec), (\d{2}) (\d{4}) (\d{2}:\d{2}:\d{2})/', $str)) {
				return strtotime(str_replace(',', '', $str));
			} elseif (strpos($str,",")&&strpos($str,":")){
				list($temp,$str)=explode(",",$str);
				$str=trim(Feed::str_month_check($str));
				list($d,$m,$y,$time)=explode(" ",$str);
				list($h,$i,$s)=explode(":",$time);
			} else{
				return gmmktime();
			}
			if (!$h)
				$h="00";
			if (!$i)
				$i="00";
			if (!$s)
				$s="00";
			$h+=$gmt;
			return mktime($h,$i,$s,$m,$d,$y);
		}

		function str_month_check($str){
			$str=str_replace("Jan","01",$str);
			$str=str_replace("Feb","02",$str);
			$str=str_replace("Mar","03",$str);
			$str=str_replace("Apr","04",$str);
			$str=str_replace("May","05",$str);
			$str=str_replace("Jun","06",$str);
			$str=str_replace("Jul","07",$str);
			$str=str_replace("Aug","08",$str);
			$str=str_replace("Sep","09",$str);
			$str=str_replace("Oct","10",$str);
			$str=str_replace("Nov","11",$str);
			return str_replace("Dec","12",$str);
		}

		function doesHaveOwnership($feedId) {
			global $database, $db, $session;
			$feedOwner = Feed::get($feedId, 'owner');
			return ($session['id'] == $feedOwner)?true:false;
		}

		function getAuthor($item, $feedId, $feedItemId = null) {
			$author = array();
			$autoUpdate = array();
			$author['result'] = $item['author'];

			list($autoUpdate['feed'], $author['feed']) = Feed::gets($feedId, 'autoUpdate,author');
			$autoUpdate['feed'] = Validator::getBool($autoUpdate['feed']);

			if (!$autoUpdate['feed'] && !Validator::is_empty($author['feed']))
				$author['result'] = $author['feed'];

			if (isset($feedItemId) || ($feedItemId !== false)) { // update
				requireComponent('Bloglounge.Data.FeedItems');
				list($autoUpdate['item'], $author['item']) = FeedItem::gets($feedItemId, 'autoUpdate,author');
				$autoUpdate['item'] = Validator::getBool($autoUpdate['item']);

				if (!$autoUpdate['item'] && !Validator::is_empty($author['item']))
					$author['result'] = $author['item'];
			}

			return $author['result'];
		}

		function getTitle($item, $feedId, $feedItemId = null) {
			$title = array();
			$autoUpdate = array();
			$title['result'] = $item['title'];

			/*list($autoUpdate['feed'], $title['feed']) = Feed::gets($feedId, 'autoUpdate,title');
			$autoUpdate['feed'] = Validator::getBool($autoUpdate['feed']);

			if (!$autoUpdate['feed'] && !Validator::is_empty($title['feed']))
				$title['result'] = $title['feed'];*/ // 피드의 제목을 피드아이템 제목에 덮어 씌우는일이 없도록..

			if (isset($feedItemId) || ($feedItemId !== false)) { // update
				requireComponent('Bloglounge.Data.FeedItems');
				list($autoUpdate['item'], $title['item']) = FeedItem::gets($feedItemId, 'autoUpdate,title');
				$autoUpdate['item'] = Validator::getBool($autoUpdate['item']);

				if (!$autoUpdate['item'] && !Validator::is_empty($title['item']))
					$title['result'] = $title['item'];
			}

			return $title['result'];
		}

		function doesExistFeedId($feedId) {
			global $database, $db;
			if (empty($feedId)) return false;
			return $db->exists("SELECT id FROM {$database['prefix']}Feeds WHERE id='{$feedId}'");
		}		
		
		function doesExistBlogURL($blogURL) {
			global $database, $db;
			if (empty($blogURL)) return false;
			return $db->exists("SELECT id FROM {$database['prefix']}Feeds WHERE blogURL='{$blogURL}'");
		}	

		function doesExistXmlURL ($XmlURL) {
			global $database, $db;
			if (empty($XmlURL)) return false;
			return $db->exists("SELECT id FROM {$database['prefix']}Feeds WHERE xmlURL='{$XmlURL}'");
		}

		function blogURL2Id($blogURL) {
			global $database, $db;
			if (empty($blogURL)) return false;
			return $db->queryCell("SELECT id FROM {$database['prefix']}Feeds WHERE blogURL LIKE '{$blogURL}%'");
		}

		/** gets **/

		function getLatestPost($feedId) {
			global $database, $db;
			if (!isset($feedId) || !Validator::is_digit($feedId) || !Feed::doesExistFeedId($feedId))
				return false;

			if (!$db->query("SELECT id, permalink, title, written FROM {$database['prefix']}FeedItems WHERE feed='{$feedId}' AND visibility!='d' ORDER BY written DESC LIMIT 0,1"))
				return false;
			if ($db->numRows() < 1)
				return false;

			$post = $db->fetch();
			$db->free();

			$recent = array();	

			$recent['id'] = $post->id;
			$recent['url'] = $post->permalink;
			$recent['title'] = $post->title;
			$recent['date'] = date("Y.m.d H:i", $post->written);

			return $recent;
		}

		function getRandomFeed() {
			global $db, $database;	
			$qFeeds = 'WHERE visibility="y"';
			return $db->queryRow('SELECT id,blogURL, title, created FROM '.$database['prefix'].'Feeds '.$qFeeds.' ORDER BY RAND() DESC LIMIT 1');		
		}

		function getRecentFeeds($count, $feedOrder = 'created') {
			global $db, $database;	
			$qFeeds = 'WHERE visibility="y"';
			return $db->queryAll('SELECT id,blogURL, title, created, logo FROM '.$database['prefix'].'Feeds '.$qFeeds.' ORDER BY '.$feedOrder.' DESC LIMIT 0,'.$count);		
		}

		function getRecentFeedsByOwner($owner, $count, $feedOrder = 'created') {
			global $db, $database;	
			$qFeeds = 'WHERE visibility="y" AND owner='.$owner;
			return $db->queryAll('SELECT id,blogURL, title, created,logo FROM '.$database['prefix'].'Feeds '.$qFeeds.' ORDER BY '.$feedOrder.' DESC LIMIT 0,'.$count);		
		}

		function getFeedCount($filter='') {
			global $db, $database;		
			if (!list($totalFeeds) = $db->pick('SELECT count(i.id) FROM '.$database['prefix'].'Feeds i '.$filter))
					$totalFeeds = 0;
			return $totalFeeds;
		}	
		
		function getPredictionPage($id, $pageCount, $searchQuery='', $feedListPageOrder = 'created') {
			global $db, $database;

			$page = 1;

			$created = Feed::get($id,'created');
			if(!empty($created)) {	
				$searchQuery = 'created > ' . $created . (!empty($searchQuery)?' AND '.$searchQuery:'');
			}			

			if(!isAdmin()) {
				$sQuery = 'WHERE visibility = "y"';
			} else {
				$sQuery = 'WHERE 1=1';
			}

			if(!empty($searchQuery)) {
				$sQuery .= ' AND ' . $searchQuery;
			}

			$count = $db->queryCell('SELECT count(*) as count FROM '.$database['prefix'].'Feeds '.$sQuery.' ORDER BY '.$feedListPageOrder.' DESC');
			if($count > 0) {
				$page = ceil(($count + 1) / $pageCount);
			}
			
			return $page;
		}

		function getFeeds($page, $pageCount = 15, $searchQuery = '', $feedListPageOrder = 'created') {
			global $db, $database;
			
			if(!isAdmin()) {
				$sQuery = 'WHERE visibility = "y"';
			} else {
				$sQuery = 'WHERE 1=1';
			}

			if(!empty($searchQuery)) {
				$sQuery .= ' AND ' . $searchQuery;
			}

			if (!list($totalFeeds) = $db->pick('SELECT count(id) FROM '.$database['prefix'].'Feeds '.$sQuery))
				$totalFeeds = 0;

			if($page == 'all') {
				$pageQuery = '';
			} else {
			 	$pageStart = ($page-1) * $pageCount; // 처음페이지 번호
				$pageQuery = 'LIMIT '.$pageStart.','.$pageCount;
			}

			$feeds = $db->queryAll('SELECT id, owner, `group`, blogURL, title, description, lastUpdate, created, feedCount, logo, visibility, isVerified FROM '.$database['prefix'].'Feeds '.$sQuery.' ORDER BY '.$feedListPageOrder.' DESC ' . $pageQuery);

			return array($feeds, $totalFeeds);	
		}

		function getFeedsByOwner($owner, $page, $pageCount = 15, $searchQuery = '', $feedListPageOrder = 'created') {
			global $db, $database;
			
			//if(!isAdmin()) {
			//	$sQuery = 'WHERE visibility = "y" AND owner = ' . $owner;
			//} else {
				$sQuery = 'WHERE owner = ' . $owner;
			//}

			if(!empty($searchQuery)) {
				$sQuery .= ' AND ' . $searchQuery;
			}

			if (!list($totalFeeds) = $db->pick('SELECT count(id) FROM '.$database['prefix'].'Feeds '.$sQuery))
				$totalFeeds = 0;
			
			if($page == 'all') {
				$pageQuery = '';
			} else {
			 	$pageStart = ($page-1) * $pageCount; // 처음페이지 번호
				$pageQuery = 'LIMIT '.$pageStart.','.$pageCount;
			}

			$feeds = $db->queryAll('SELECT id, owner, `group`, blogURL, title, description, lastUpdate, created, feedCount, logo, visibility, isVerified FROM '.$database['prefix'].'Feeds '.$sQuery.' ORDER BY '.$feedListPageOrder.' DESC ' . $pageQuery);

			return array($feeds, $totalFeeds);	
		}

		function getFeedLastUpdate($filter = '') {
			global $db, $database;		
			if (!list($result) = $db->pick('SELECT i.lastUpdate FROM '.$database['prefix'].'Feeds i '.$filter.' ORDER BY i.lastUpdate DESC LIMIT 1'))
				$result = 0;
			return $result;
		}
	}
?>
