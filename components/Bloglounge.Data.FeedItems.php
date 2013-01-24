<?php
	class FeedItem {
		function get($itemId, $field) { // return as single value
			global $database, $db;
			if (list($value) = $db->pick('SELECT '.$field.' FROM '.$database['prefix'].'FeedItems WHERE id='.$itemId))
				return $value;
			return false;
		}

		function gets($itemId, $fields) { // return as array
			global $database, $db;
			if (empty($itemId) || !preg_match("/^[0-9]+$/", $itemId)) {
				return false;
			}
			
			$result = array();
			if ($db->query('SELECT '.$fields.' FROM '.$database['prefix'].'FeedItems WHERE id='.$itemId)) {
				$data = $db->fetchRow();
				foreach ($data as $row) {
					array_push($result, $row);
				}
				$db->free();
			}
			return $result;
		}

		function getAll($itemId) {
			global $database, $db;
			$db->query('SELECT i.*,c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item=i.id AND c.custom ="y") WHERE i.id='.$itemId);
			
			
			return $db->fetchArray();
		}

		function getIdByURL($url) {
			global $database, $db;
			if (!isset($url)) return false;

			$id = false;
			list($id) = $db->pick('SELECT id FROM '.$database['prefix'].'FeedItems WHERE permalink="'.$url.'"');
			return $id;
		}

		function edit($itemId, $field, $value) {
			global $database, $db;
			return ($db->execute('UPDATE '.$database['prefix'].'FeedItems SET '.$field.'="'.$db->escape($value).'" WHERE id='.$itemId))?true:false;
		}

		function editWithArray($itemId, $arg){
			if (!isset($itemId) || !is_array($arg)) {
				return false;
			}
			foreach ($arg as $key=>$value) {
				if (!Validator::enum($key, 'author,permalink,title,autoUpdate,allowRedistribute,tags,focus,visibility')) {
					return false;
				}
				if (!FeedItem::edit($itemId, $key, $value)) {
					return false;
				}
			}
			return true;
		}

		function delete($itemId) {
			global $database, $db;
			list($feedId, $permalink) = FeedItem::gets($itemId, 'feed,permalink');
			if (!$db->execute("INSERT INTO {$database['prefix']}DeleteHistory (feed, permalink) VALUES ('$feedId', '$permalink')"))
				return false;

			requireComponent('LZ.PHP.Media');
			Media::delete($itemId);
			
			

			requireComponent('Bloglounge.Data.Category');
			$result = $db->queryAll('SELECT category FROM '.$database['prefix'].'Categoryrelations WHERE item = ' . $itemId,MYSQL_ASSOC);
			$categoryIds = array();
			foreach($result as $item) {
				array_push($categoryIds, $item['category']);
			}

			$categoryIds = array_unique($categoryIds);
			
			$db->execute("DELETE FROM {$database['prefix']}CategoryRelations WHERE item = {$itemId}"); // clear CategoryRelations
			
			requireComponent('Bloglounge.Data.Groups');

			$db->execute("DELETE FROM {$database['prefix']}TagRelations WHERE item = {$itemId}"); // clear TagRelations
			if ($db->execute('DELETE FROM '.$database['prefix'].'FeedItems WHERE id='.$itemId)) {
				if (Validator::getBool(Settings::get('useRssOut'))) {
					requireComponent('Bloglounge.Data.RSSOut');
					RSSOut::refresh();
				}
				
				foreach($categoryIds as $categoryId) {
					Category::rebuildCount($categoryId);
				}

				return true;
			} else {
				return false;
			}
		}

		function deleteByFeedId($feedId) {
			global $database, $db;

			$itemIds = array();

			requireComponent('LZ.PHP.Media');
			requireComponent('Bloglounge.Data.Category');
			requireComponent('Bloglounge.Data.Groups');

			$result = $db->queryAll("SELECT id FROM {$database['prefix']}FeedItems WHERE feed='$feedId'");
			if($result) {
				foreach($result as $item) {
					Media::delete($item['id']);
					array_push($itemIds, $item['id']);			
				}

				$itemIds = array_unique($itemIds);
				$categoryIds = array();
				foreach($itemIds as $itemId) {
					$result = $db->queryAll('SELECT category FROM '.$database['prefix'].'Categoryrelations WHERE item = ' . $itemId,MYSQL_ASSOC);
					foreach($result as $item) {
						array_push($categoryIds, $item['category']);
					}				
				}

				$categoryIds = array_unique($categoryIds);

				$itemStr = implode(',', $itemIds);	
				
				
				$db->execute("DELETE FROM {$database['prefix']}CategoryRelations WHERE item IN ($itemStr)"); // clear CategoryRelations

				$db->execute("DELETE FROM {$database['prefix']}TagRelations WHERE item IN ($itemStr)"); // clear TagRelations
				
				if ($db->execute('DELETE FROM '.$database['prefix'].'FeedItems WHERE feed='.$feedId)) {

					foreach($categoryIds as $categoryId) {
						Category::rebuildCount($categoryId);
					}
					return true;
				} else {
					return false;
				}
			}

			return true;
		}

		function click($url) {
			global $database, $db;
			return $db->execute('UPDATE '.$database['prefix'].'FeedItems SET click=click+1 WHERE permalink="'.$db->escape($url).'"');
		}

		function doesHaveOwnership($itemId) {
			global $database, $db, $session;
			$feedId = FeedItem::get($itemId, 'feed');
			return ($db->count('SELECT owner FROM '.$database['prefix'].'Feeds WHERE owner="'.$session['id'].'" and id="'.$feedId.'"') != 0) ? true : false;
		}

		function setThumbnail($itemId, $thumbnailId) {
			global $database, $db;
			if(empty($itemId) || empty($thumbnailId)) {
				return false;
			}
			
			requireComponent('LZ.PHP.Media');
			
			if(Media::checkMedia($thumbnailId)) {
				$db->execute("UPDATE {$database['prefix']}FeedItems SET thumbnailId='$thumbnailId' WHERE id='$itemId'");
				return true;
			}
			return false;
		}
		
		function cacheThumbnail($itemId, $item) {
			global $database, $db;
			if (!isset($item) || !is_array($item) || !defined('ROOT') || !isset($itemId) || !Validator::getBool($itemId))
				return false;

			$cacheDir = ROOT. '/cache/thumbnail';
			if (!is_dir($cacheDir)) func::mkpath($cacheDir);
			if (!is_writeable($cacheDir)) return false;

			$division = ord(substr(str_replace("http://","",$item['permalink']), 0, 1));

			requireComponent('LZ.PHP.Media');
			$media = new Media;
			$media->set('outputPath', $cacheDir.'/'.$division);

			$item['id'] = $itemId; // for uniqueId

			list($thumbnailLimit, $thumbnailSize, $thumbnailType) = Settings::gets('thumbnailLimit, thumbnailSize, thumbnailType');
			if($thumbnailLimit == 0) return false;

			if (!$result = $media->get($item, $thumbnailSize, $thumbnailLimit, $thumbnailType))
				return false;

			foreach($result['movies'] as $m_item) {
				$tFilename = $db->escape(str_replace($cacheDir, '', $m_item['filename']['fullpath']));
				$tSource = $db->escape($m_item['source']);

				if(!empty($tFilename)) {
					$width = $m_item['width'];
					$height = $m_item['height'];
					$via = $m_item['via'];					
					$insertId = $media->add($itemId, $tFilename, $tSource, $width, $height, 'movie', $via);
				}
			}

			foreach($result['images'] as $i_item) {
				$tFilename = $db->escape(str_replace($cacheDir, '', $i_item['filename']['fullpath']));
				$tSource = $db->escape($i_item['source']);

				if(!empty($tFilename) && $i_item['width'] > 100 && $i_item['height'] > 100) {
					$width = $i_item['width'];
					$height = $i_item['height'];
					$insertId = $media->add($itemId, $tFilename, $tSource, $width, $height, 'image');
				}
			}
	
			if(isset($insertId)) {
				$db->execute("UPDATE {$database['prefix']}FeedItems SET thumbnailId='$insertId' WHERE id='$itemId'");
			}

			return true;
		}

		/** gets **/

		function getPageFromWritten($written) {
			if(!isAdmin()) {
				$filter = ' WHERE  (i.visibility = "y") AND (i.feedVisibility = "y") AND (i.written > ' . $written. ')';
			} else {
				$filter = ' WHERE  (i.visibility != "d") AND (i.written > ' . $written. ')';
			}

			return FeedItem::getFeedItemCount($filter) + 1;
		}

		function getIdListFromPage($page, $filter, $count = 5) {
			global $db, $database;	
			
			$page = $page - 2;
			if($page < 0) $page = 0;

			$written = $db->queryCell('SELECT i.written FROM ' . $database['prefix'] . 'FeedItems AS i ' . $filter . ' ORDER BY i.written DESC LIMIT ' . $page . ',1');
			$result = $db->queryAll('SELECT i.id FROM ' . $database['prefix'] . 'FeedItems AS i ' . $filter . ' AND i.written <= ' . $written . ' ORDER BY i.written DESC LIMIT ' . ($count+2));	
			
			return $result;
		}

		function getPredictionPage($id, $pageCount, $searchType='', $searchKeyword='',$searchExtraValue='', $viewDelete = false, $owner = 0) {
			global $db, $database;

			$page = 1;
			$sQuery = FeedItem::getFeedItemsQuery($searchType, $searchKeyword, $searchExtraValue, $viewDelete, $owner);
			$written = FeedItem::get($id,'written');

			if(!empty($written)) {
				$sQuery = str_replace('WHERE', 'WHERE (i.written > '.$written.')'.' AND ',$sQuery);
			}		
			
			$count = $db->queryCell('SELECT count(*) as count FROM '.$database['prefix'].'FeedItems i '.$sQuery.' ORDER BY i.written DESC');

			if($count > 0) {
				$page = ceil(($count + 1) / $pageCount);
			}

			return $page;
		}

		function getPredictionPageByOwner($owner,$id, $pageCount, $searchType='', $searchKeyword='',$searchExtraValue='', $viewDelete = false) {
			return FeedItem::getPredictionPage($id, $pageCount, $searchType, $searchKeyword,$searchExtraValue, $viewDelete, $owner);
		}

		function getFeedItemCount($filter='') {
			global $db, $database;		
			if (!list($totalFeedItems) = $db->pick('SELECT count(DISTINCT i.id) FROM '.$database['prefix'].'FeedItems i '.$filter))
					$totalFeedItems = 0;
					
			return $totalFeedItems;
		}
		
		function getFeedItemsByOwner($owner, $searchType, $searchKeyword, $searchExtraValue, $page, $pageCount, $viewDelete = false) {
			return FeedItem::getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount, $viewDelete, $owner);
		}

		function getFeedItems($searchType, $searchKeyword, $searchExtraValue, $page, $pageCount, $viewDelete = false, $owner = 0) {
			global $db, $database;
			
			$sQuery = FeedItem::getFeedItemsQuery($searchType, $searchKeyword, $searchExtraValue,$viewDelete,$owner);
			
			$pageStart = ($page-1) * $pageCount; // 처음페이지 번호

			

			if($searchType != 'category') {
		
				$categoryQuery = ' AND c.custom ="y" ';
		
			} else {
				$categoryQuery = '';
			}

			$feedList = $db->queryAll('SELECT i.id, i.feed, i.author, i.permalink, i.title, i.description, i.tags, i.written, i.click, i.thumbnailId, i.visibility, i.feedVisibility, i.boomUp, i.boomDown,  c.category AS category,  i.focus FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item = i.id '.$categoryQuery.') '. $sQuery.' ORDER BY i.written DESC LIMIT '.$pageStart.','.$pageCount);
			
			if($searchType == 'category') {
				$sQuery = ' LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item = i.id) ' . $sQuery;
			}


			$feedItemCount = FeedItem::getFeedItemCount($sQuery);
			return array($feedList, $feedItemCount);
		}

		function getFeedItemsQuery($searchType, $searchKeyword, $searchExtraValue,$viewDelete = false,$owner = 0) {	
			global $db, $database, $config;

			$sQuery = '';

			if ($searchType=='tag' && !Validator::is_empty($searchKeyword)) {		
				if (!list($tagId) = $db->pick('SELECT id FROM '.$database['prefix'].'Tags WHERE name="'.$db->escape($searchKeyword).'"')) {
					return array(null,0);
				} else {
					$sQuery = ' LEFT JOIN '.$database['prefix'].'TagRelations r ON (r.item = i.id AND r.type = "feed") WHERE r.tag="'.$tagId.'"';
				}

			} else if ($searchType=='blogURL' && !Validator::is_empty($searchKeyword)){		
				$searchKeyword = UTF8::bring($searchKeyword);
				$searchFeedId = $searchExtraValue;
				if(empty($searchFeedId)) {
					$searchFeedId = Feed::blogURL2Id('http://'.str_replace('http://', '', $searchKeyword));
				} 

				$sQuery = ' WHERE i.feed = '.$searchFeedId;
				
			} else if ($searchType=='title+description' && !Validator::is_empty($searchKeyword)){		
					$searchKeyword = UTF8::bring($searchKeyword);
					$keyword = $db->escape($searchKeyword);

					$sQuery =  ' WHERE i.description LIKE "%'.$keyword.'%"';				
			}  else if ($searchType=='title' && !Validator::is_empty($searchKeyword)){		
					$searchKeyword = UTF8::bring($searchKeyword);
					$keyword = $db->escape($searchKeyword);

					$sQuery =  ' WHERE i.title LIKE "%'.$keyword.'%"';				
			} else if ($searchType=='description' && !Validator::is_empty($searchKeyword)){		
					$searchKeyword = UTF8::bring($searchKeyword);
					$keyword = $db->escape($searchKeyword);

					$sQuery =  ' WHERE i.description LIKE "%'.$keyword.'%"';				
			} else if ($searchType=='focus'){		
					$sQuery =  ' WHERE i.focus = "'.$searchKeyword.'"';		
			} else if ($searchType=='group') {
				requireComponent('Bloglounge.Data.Groups');	
				
				if(!empty($searchExtraValue)) {
					$tagId = $db->pick('SELECT id FROM '.$database['prefix'].'Tags WHERE name="'.$db->escape(urldecode($searchExtraValue)).'"');
					if($tagId) {
						$tagId = $tagId[0];
						$sQuery = ' LEFT JOIN '.$database['prefix'].'TagRelations r ON (r.item = i.id AND r.type = "group_category") ';
					}
				}

				if(!is_numeric($searchKeyword)) {
					$group = Group::getByName($searchKeyword);
					$searchKeyword = $group['id'];
				}
				
				if($searchKeyword) {	
					$feedIds = Group::getFeedIdList($searchKeyword);
					$sQuery .= ' WHERE i.feed IN (' . implode(',',$feedIds) .')';

					if($tagId) {
						$sQuery .= ' AND r.tag="'.$tagId.'"';
					}
				}

			} else if ($searchType=='category') {
				requireComponent('Bloglounge.Data.Category');
				if(is_numeric($searchKeyword)) {
					$category = Category::getById($searchKeyword);
				} else {
					$category = Category::getByName($searchKeyword);
				}

				if($category) {
					$sQuery = ' WHERE c.category = ' . $category['id'];
				}
			} else if ($searchType == 'archive' && !Validator::is_empty($searchKeyword)) {
				if(is_array($searchExtraValue) && array_key_exists('start',$searchExtraValue) && array_key_exists('end',$searchExtraValue)) {
					$tStart = $searchExtraValue['start'];
					$tEnd = $searchExtraValue['end'] + 86400;
				} else {
					$tStart = $searchExtraValue;
					$tEnd = $tStart + 86400;
				}

				$tQuery = ' WHERE i.written > '.$tStart.' AND i.written < '.$tEnd.' ';
				if (strpos($sQuery, 'WHERE') !== false) {
					$sQuery = str_replace('WHERE ', $tQuery.' AND (', $sQuery);
					$sQuery .= ')';
				} else {
					$sQuery .= $tQuery;
				}

			} else {
				if (!Validator::is_empty($searchKeyword)) {
					$searchKeyword = UTF8::bring($searchKeyword);
					$keyword = $db->escape($searchKeyword);
					
					if(empty($searchExtraValue)) { // all : title, description, tags, permlink						
						$sQuery =  ' WHERE i.author LIKE "%'.$keyword.'%" OR i.title LIKE "%'.$keyword.'%" OR i.description LIKE "%'.$keyword.'%" OR i.tags LIKE "%'.$keyword.'%" OR i.permalink LIKE "%'.$keyword.'%"';					
					} else { // custom					
						$sQuery = ' WHERE ' . $searchExtraValue;
					}
				}
			}


			// boomDownReactor, boomDownReactorLimit : 리액터가 숨기기일때 쿼리에서 제외 파트 추가 ( 특정수만큼 붐다운(비추천)한글은 제외하거나 특정기능..
			
			if(isset($config)) {
				
				if (($config->boomDownReactor == 'hide') && ($config->boomDownReactLimit > 0)) {
					$bQuery = ' WHERE (i.boomDown <= '.$config->boomDownReactLimit.') ';
					if (strpos($sQuery, 'WHERE') !== false) {
						$sQuery = str_replace('WHERE ', $bQuery.' AND (', $sQuery);
						$sQuery .= ')';
					} else {
						$sQuery .= $bQuery;
					}
				}
			
			}
								

			if(empty($owner)) {
				if($viewDelete) {
					// 공개된 블로그만 뽑기 + 삭제된 글 보이기		
					if(!isAdmin()) {
						$bQuery = ' WHERE  (i.visibility = "d") AND (i.feedVisibility = "y") ';
					} else {
						$bQuery = ' WHERE  (i.visibility = "d") ';
					}
				} else {
					// 공개된 블로그만 뽑기
					if(!isAdmin()) {
						$bQuery = ' WHERE  (i.visibility = "y") AND (i.feedVisibility = "y") ';
					} else {
						$bQuery = ' WHERE  (i.visibility != "d") ';
					}
				}
			} else {		
				if($viewDelete) {
					// 공개된 블로그만 뽑기		
				//	if(!isAdmin()) {
				//		$bQuery = ' WHERE  (i.visibility = "d") AND (i.feedVisibility = "y") AND (f.owner = ' . $owner . ')';
				//	} else {
						$bQuery = ' WHERE  (i.visibility = "d") AND (f.owner = ' . $owner . ')';
				//	}
				} else {
					// 공개된 블로그만 뽑기		
				//	if(!isAdmin()) {
				//		$bQuery = ' WHERE  (i.visibility = "y") AND (i.feedVisibility = "y") AND (f.owner = ' . $owner . ')';
				//	} else {
						$bQuery = ' WHERE  (i.visibility != "d") AND (f.owner = ' . $owner . ')';
				//	}
				}
			}

			if(strpos($sQuery, 'Feeds f') === false ) {
				$bQuery = ' LEFT JOIN '.$database['prefix'].'Feeds f ON (f.id = i.feed) ' . $bQuery;
			}
			if (strpos($sQuery, 'WHERE') !== false) {
				$sQuery = str_replace('WHERE ', $bQuery.' AND (', $sQuery);
				$sQuery .= ')';
			} else {
				$sQuery .= $bQuery;
			}

			return $sQuery;
		}

		function getFeedItem($id) {		
			global $db, $database;
			return $db->queryRow('SELECT i.*,c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item=i.id) WHERE i.id='. $id);
		}

		function getFeedItemsByFeedId($feedId, $count) {		
			global $db, $database;
			return $db->queryAll('SELECT i.*,c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item=i.id) WHERE i.visibility = "y" AND i.feed = '. $feedId .' ORDER BY i.written DESC LIMIT '. $count);
		}

		function getRecentFeedItems($count) {		
			global $db, $database;
			return $db->queryAll('SELECT i.*,c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item=i.id) WHERE i.visibility = "y" ORDER BY i.written DESC LIMIT '. $count);
		}

		function getRecentFeedItemsByFeed($feeds, $count) {		
			global $db, $database;
			if(is_array($feeds)) {
				return $db->queryAll('SELECT i.*, c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item = i.id) WHERE i.feed IN ('. implode(',',$feeds) .') AND i.visibility = "y" ORDER BY i.written DESC LIMIT '. $count);
			} else {
				return $db->queryAll('SELECT i.*, c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item = i.id) WHERE i.feed = ' . $feeds . ' AND i.visibility = "y" ORDER BY i.written DESC LIMIT '. $count);
			}
		}	
		
		function getRecentFeedItemsByCategory($categories, $count) {		
			global $db, $database;
			if(is_array($categories)) {
				return $db->queryAll('SELECT i.*, c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item = i.id) WHERE c.category IN ('. implode(',',$categories) .') AND i.visibility = "y" ORDER BY i.written DESC LIMIT '. $count);
			} else {
				
				return $db->queryAll('SELECT i.*, c.category AS category FROM '.$database['prefix'].'FeedItems i LEFT JOIN '.$database['prefix'].'CategoryRelations c ON (c.item = i.id) WHERE c.category = ' . $categories . ' AND i.visibility = "y" ORDER BY i.written DESC LIMIT '. $count);
			}
		}

		function getRecentFocusFeedItems($count) {
			global $db, $database;
			return $db->queryAll('SELECT id,permalink,title,description,author,thumbnailId,written FROM '.$database['prefix'].'FeedItems WHERE focus = "y" AND visibility = "y" ORDER BY written DESC LIMIT '. $count);
		}

		function getTopFeedItems($count, $rankBy = 'boom') {		
			global $db, $database;	
			
			switch ($rankBy) {
				case 'click':
					$rankBy = 'i.click';
				break;
				default:
				case 'boom':
					$rankBy = 'i.boomUp-i.boomDown';
				break;
			}

			$qBoom = '';
			return $db->queryAll('SELECT i.permalink, i.title, i.description FROM '.$database['prefix'].'FeedItems AS i LEFT JOIN '.$database['prefix'].'Feeds AS f ON ( f.id = i.feed ) WHERE i.feedVisibility = "y"  '.$qBoom.' ORDER BY ('.$rankBy.') DESC LIMIT 0,'.$count);
		}	
		
		// -- 아래형태로 .. 변경 (추천수) - ((오늘 - 글이 들어온 날)날수 * 100000) // 오늘부터 어제.. 그제.. 그끄저께 순으로 높은 값을 줘서.. 순서를 매긴다. 
		// 가장 최근의 글을 우선적으로 값을 매김 ( 단점 업데이트가 빈번하지 않을경우 최근글이 항상 인기글이 됨.. )
		// 추천 혹은 비추천된 날짜가 아닌 글발행된 날과 관련됨..

		function getTopFeedItemsByLastest($count, $rankBy = 'boom') {		
			global $db, $database;	

			$written = $db->queryCell('SELECT i.written FROM '.$database['prefix'].'FeedItems AS i LEFT JOIN '.$database['prefix'].'Feeds AS f ON ( f.id = i.feed ) WHERE i.feedVisibility = "y" ORDER BY i.written ASC');
			if(!$written) $written = 0;
			$written = date('Ymd', $written);

			//$rankBy = 'mix';
			switch ($rankBy) {
				case 'click':
					$rankBy = 'i.click+((FROM_UNIXTIME(i.written,"%Y%m%d%")-'.$written.')*10000)';
					$min = ' AND i.click > 0 ';
				break;
			//	case 'mix':
			//		$rankBy = 'i.click + ((i.boomUp-i.boomDown) * 100) + ((FROM_UNIXTIME(i.written,"%Y%m%d%")-'.$written.')*10000)';
			//	break;
				default:
				case 'boom':
					$rankBy = '(i.boomUp-i.boomDown)+((FROM_UNIXTIME(i.written,"%Y%m%d")-'.$written.')*10000)';
					$min = ' AND (i.boomUp-i.boomDown) > 0 ';
				break;
			}

			return $db->queryAll('SELECT i.id, i.permalink, i.title, i.description, i.author, i.thumbnailId, i.written FROM '.$database['prefix'].'FeedItems AS i LEFT JOIN '.$database['prefix'].'Feeds AS f ON ( f.id = i.feed ) WHERE i.feedVisibility = "y" '.$min.' ORDER BY ('.$rankBy.') DESC LIMIT 0,'.$count);
		}
	}
?>