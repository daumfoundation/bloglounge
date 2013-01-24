<?php

	class Tag {
		function buildTagIndex($itemId, $tags, $oldtags = null) {
			global $database, $db;
			if (!isset($tags) || !is_array($tags) || !isset($itemId) || !Validator::getBool($itemId))
				return false;
			
			$tagChunk = array();
			$tagInsertChunk = array();
			@array_shift($tags); // first tag is category.
			if (empty($tags)) return false;
			foreach ($tags as $tag) {
				if (!Validator::is_empty($tag)) {
					$tag = trim($tag);
					array_push($tagChunk, "'$tag'");
					array_push($tagInsertChunk, "('$tag')");
				}
			}
			$tagInsertStr = implode(',', $tagInsertChunk); // ('tag'),('tag')...
			$tagStr = implode(',', $tagChunk); // 'tag','tag',...

			$db->execute("INSERT IGNORE INTO {$database['prefix']}Tags (name) VALUES $tagInsertStr");

			$tagIdList = array();
			if (!$db->query("SELECT id FROM {$database['prefix']}Tags WHERE name IN ($tagStr)")) return false;
			while ($taglist = $db->fetchRow()) { 
					array_push($tagIdList, $taglist[0]); 
			}
			$db->free();

			$relationList = array();
			foreach ($tagIdList as $tagId) {
				array_push($relationList, "('$itemId', '$tagId', UNIX_TIMESTAMP())");
			}
			$relationStr = implode(',', $relationList); // ('itemId','tagId'),('itemId','tagId')...

			$db->execute("INSERT IGNORE INTO {$database['prefix']}TagRelations (item, tag, linked) VALUES $relationStr");

			if (!isset($oldtags) || empty($oldtags)) return true; // finish here if oldtags empty.

			$deletedTags = array_diff($oldtags, $tags);
			if (count($deletedTags) > 0) {
				$delTags = array();
				$dTagStr = '\'' . implode('\' , \'', $deletedTags) . '\'';
				if (!$db->query("SELECT id FROM {$database['prefix']}Tags WHERE name IN ($dTagStr)")) return false;
				while ($dlist = $db->fetchRow()) { 
					array_push($delTags, $dlist[0]); 
				}
				$db->free();
				$delTagStr = implode(', ', $delTags); // 삭제된 태그의 id 리스트

				$db->execute("DELETE FROM {$database['prefix']}TagRelations WHERE item='$itemId' AND type='feed' AND tag IN ($delTagStr)"); // TagRelation 삭제
			}
		}

		function getTagCloud($type, $amount) {
			switch (strtolower($type)) {
				case 'frequency':
					return Tag::getTagCloudByFrequency($amount);
					break;
				case 'random':
					return Tag::getTagCloudByRandom($amount);
					break;
				case 'name':
					return Tag::getTagCloudByName($amount);
					break;
			}
			return false;
		}

		function getTagCloudByFrequency($amount) {
			global $database, $db;

			$limit = intval($amount * 1.5);
			if (!$db->query("SELECT tag, count(tag) as tagUsed FROM {$database['prefix']}TagRelations WHERE type = 'feed' GROUP BY tag ORDER BY tagUsed DESC LIMIT $limit"))
				return false;
			if ($db->numRows() == 0) 
				return false;
			
			$tagPool = array();
			$tagFrequency = array();
			while ($data = $db->fetchArray()) {
				array_push($tagPool, $data['tag']);
				$tagFrequency[$data['tag']] = $data['tagUsed'];
			}
			shuffle($tagPool);

			$tagIds = array();
			foreach (array_slice($tagPool, 0, $amount) as $tagId) {
				array_push($tagIds, "'$tagId'");
			}
			$tagIdList = implode(',', $tagIds);
			if (!$db->query("SELECT id, name FROM {$database['prefix']}Tags WHERE id IN ($tagIdList)"))
				return false;
			if ($db->numRows() == 0) 
				return false;

			$result = array();
			while ($data = $db->fetchArray()) {
				$frequency = $tagFrequency[$data['id']];
				array_push($result, array("name"=>$data['name'], "frequency"=>$frequency));
			}
			$db->free();

			return array_slice($result, 0, $amount);
		}

		function getTagCloudByRandom($amount) {
			global $database, $db;

			$limit = intval($amount * 1.5);
			if (!$db->query("SELECT tag, count(tag) as tagUsed FROM {$database['prefix']}TagRelations WHERE type = 'feed' GROUP BY tag ORDER BY RAND() LIMIT $limit"))
				return false;
			if ($db->numRows() == 0) 
				return false;

			$tagPool = array();
			$tagFrequency = array();
			while ($data = $db->fetchArray()) {
				array_push($tagPool, $data['tag']);
				$tagFrequency[$data['tag']] = $data['tagUsed'];
			}
			$db->free();

			$tagIds = array();
			foreach ($tagPool as $tagId) {
				array_push($tagIds, "'$tagId'");
			}
			$tagIdList = implode(',', $tagIds);
			if (!$db->query("SELECT id, name FROM {$database['prefix']}Tags WHERE id IN ($tagIdList)"))
				return false;
			if ($db->numRows() == 0) 
				return false;

			$result = array();
			while ($data = $db->fetchArray()) {
				$frequency = $tagFrequency[$data['id']];
				array_push($result, array("name"=>$data['name'], "frequency"=>$frequency));
			}
			$db->free();

			shuffle($result);
			return array_slice($result, 0, $amount);
		}

		function getTagCloudByName($amount) {
			global $database, $db;

			if (!$db->query("SELECT t.name, count(r.tag) as tagUsed FROM {$database['prefix']}TagRelations r ON ( r.type = 'feed' ) LEFT JOIN {$database['prefix']}Tags t ON t.id = r.tag GROUP BY r.tag"))
				return false;
			if ($db->numRows() == 0) 
				return false;

			$tagPool = array();
			while ($data = $db->fetchArray()) {
				array_push($tagPool, array("name"=>$data['name'], "frequency"=>$data['tagUsed'], "cc"=>((ord($data['name']) < 128) ? 2 : 1)));
			}
			$db->free();

			$tagPool = func::array_columnsort('cc', SORT_ASC, SORT_NUMERIC, 'name', SORT_ASC, SORT_STRING, $tagPool);
			return array_slice($tagPool, 0, $amount);
		}

		function getFrequencyRange() {
			global $database, $db;

			list($min) = $db->pick("SELECT count(tag) as frequency FROM {$database['prefix']}TagRelations WHERE type = 'feed' GROUP BY tag ORDER BY frequency ASC LIMIT 1");
			list($max) = $db->pick("SELECT count(tag) as frequency FROM {$database['prefix']}TagRelations WHERE type = 'feed' GROUP BY tag ORDER BY frequency DESC LIMIT 1");

			return array('min'=>$min, 'max'=>$max);
		}

		function getFrequencyClass($myFrequency) {
			$range = Tag::getFrequencyRange();

			$dist = $range['max'] / 3;
			if ($myFrequency == $range['min'])
				$level = 5;
			else if ($myFrequency == $range['max'])
				$level = 1;
			else if ($myFrequency >= $range['min'] + ($dist * 2))
				$level = 2;
			else if ($myFrequency >= $range['min'] + $dist)
				$level = 3;
			else
				$level = 4;

			return 'tagCloud'.strval($level);
		}

		function getTagCount() {		
			global $db, $database;
			if(!list($totalTags) = $db->pick("SELECT count( DISTINCT tag ) FROM {$database['prefix']}TagRelations WHERE type = 'feed' "))
					$totalTags = 0;
			return $totalTags;
		}

		function getIssueTags($count) {
			global $db, $database;
			$linked = $db->queryCell("SELECT linked FROM {$database['prefix']}TagRelations WHERE linked > 0 AND type = 'feed' ORDER BY linked ASC");

			if($linked) {
				$day = date('Ymd', $linked);
				$result = $db->queryAll("SELECT t.name, count(tr.tag) as count, ((count(tr.tag)*10)+sum((FROM_UNIXTIME(tr.linked,'%Y%m%d')-{$day})*1000)) as frequency FROM {$database['prefix']}TagRelations AS tr LEFT JOIN {$database['prefix']}Tags AS t ON (t.id = tr.tag) WHERE (tr.type = 'feed') GROUP BY tr.tag ORDER BY frequency DESC LIMIT {$count}");
			} else {
				$result = array();
			}
			return $result;
		}
	}
?>