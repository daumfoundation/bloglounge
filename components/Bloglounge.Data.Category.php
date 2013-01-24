<?php
	Class Category {	

		function add($categoryName, $categoryFilter) {
			global $database, $db;
			if (empty($categoryName)) {
				return false;
			}

			$categoryName = $db->escape($categoryName);
		
			$categoryFilter = $db->escape($categoryFilter);
			
			$priority = Category::getNextPriority();
			$result = $db->execute('INSERT INTO '.$database['prefix'].'Categories (name, priority, filter) VALUES ("'.$categoryName.'",'.$priority.',"'.$categoryFilter.'")');
			$id = $db->insertId();
	
				
			Category::inputFilters($id,$categoryFilter);	
	
			Category::rebuildFilters($id,$categoryFilter);	
		
			Category::rebuildCount($id);

			return $result;
		}

		function doesNameExists($name) {
			global $database, $db;
			if (!isset($name) || empty($name)) {
				return false;
			}
			$n = $db->count('SELECT id FROM '.$database['prefix'].'Categories WHERE name="'.$db->escape($name).'"');
			return Validator::getBool($n);
		}

		function delete($id) {
			global $database, $db;

			if (empty($id) || !isset($id))
				return false;			
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$db->execute('DELETE FROM '.$database['prefix'].'CategoryRelations WHERE category ="'.$id.'"');
	
			$db->execute('DELETE FROM '.$database['prefix'].'TagRelations WHERE item = "'.$id.'" AND type = "category"');
			return $db->execute('DELETE FROM '.$database['prefix'].'Categories WHERE id="'.$id.'"');
		}

		function edit($id, $name, $filter) {
			global $database, $db;

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$id = $db->escape($id);
			$name = $db->escape($name);
			
			$filter = $db->escape($filter);

			$result = $db->execute('UPDATE '.$database['prefix'].'Categories SET name = "'.$name.'", filter = "'.$filter.'" WHERE id='.$id);	
			
			
			
			Category::inputFilters($id,$filter);	
			
			Category::rebuildFilters($id,$filter);	
			Category::rebuildCount($id);

			return $result;
		}
		
		function get($id, $field) {
			global $database, $db;

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$result = $db->queryCell('SELECT '.$field.' FROM '.$database['prefix'].'Categories WHERE id="'.$db->escape($id).'"');
			return $result;
		}

		function getById($id) {
			global $database, $db;		

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$db->query('SELECT * FROM '.$database['prefix'].'Categories WHERE id="'.$db->escape($id).'"');
			$result = $db->fetchArray();
			$db->free();
			return $result;
		}	
		
		function getByName($name) {
			global $database, $db;		

			if (empty($name) || !isset($name)) 
				return false;

			$db->query('SELECT * FROM '.$database['prefix'].'Categories WHERE name="'.$db->escape($name).'"');
			$result = $db->fetchArray();
			$db->free();
			return $result;
		}		

		function getAll($itemId) {
			global $database, $db;
			$db->query('SELECT * FROM '.$database['prefix'].'Categories WHERE id='.$itemId);
			return $db->fetchArray();
		}				
		
		function getList($count = -1) {		
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Categories ORDER BY priority ASC'. $count);
		}		
		
		function getRandomList($count = -1) {		
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Categories ORDER BY RAND()'. $count);
		}

		
		function buildCategoryRelations($itemId, $tags, $oldtags = null) {		
			global $database, $db;
			if(empty($tags) || !isset($tags) || !isset($itemId) || !Validator::getBool($itemId))
				return false;
	
			$tagChunk = array();
			foreach ($tags as $tag) {
				if (!Validator::is_empty($tag)) {
					$tag = trim($tag);
					array_push($tagChunk, "'$tag'");
				}
			}	
			
			$tagString = implode(',', $tagChunk);
			if(!$db->query('SELECT id FROM '.$database['prefix'].'Tags WHERE name IN ('.$tagString.')')) return false;
	
			$tagIds = array();
			
			while($taglist = $db->fetchRow()) {
				array_push($tagIds, $taglist[0]);
			}
				
			if(!$db->query('SELECT item FROM '.$database['prefix'].'TagRelations WHERE tag IN ('. implode(',', $tagIds) .') AND type = "category"')) return false;
			
			$categoryIds = array();
	
			while ($categorylist = $db->fetchRow()) { 
				array_push($categoryIds, $categorylist[0]); 
			}
			$db->free();

			$relationList = array();	
			foreach ($categoryIds as $categoryId) {
				array_push($relationList, "('$itemId', '$categoryId', UNIX_TIMESTAMP(), 'n')");
			}
			$relationStr = implode(',', $relationList); // ('itemId','tagId'),('itemId','tagId')...

			$db->execute("INSERT IGNORE INTO {$database['prefix']}CategoryRelations (item, category, linked, custom) VALUES $relationStr");
		
			foreach($categoryIds as $categoryId) {
				Category::rebuildCount($categoryId);
			}
			
			if (!isset($oldtags) || empty($oldtags)) return true;

			$deletedTags = array_diff($oldtags, $tags);
			if (count($deletedTags) > 0) {
				$delTags = array();
				$dTagStr = '\'' . implode('\' , \'', $deletedTags) . '\'';
				if (!$db->query("SELECT id FROM {$database['prefix']}Tags WHERE name IN ($dTagStr)")) return false;
				while ($dlist = $db->fetchRow()) { 
					array_push($delTags, $dlist[0]); 
				}
				$db->free();
			
				$delTagStr = implode(', ', $delTags);
			
				if(!$db->query('SELECT item FROM '.$database['prefix'].'TagRelations WHERE tag IN ('.$delTagStr.') AND type = "category"')) return false;
			
				$delCategories = array();
		
				while ($dlist = $db->fetchRow()) { 
					array_push($delCategories, $dlist[0]); 
				}

				$db->free();
			
				$delCategoryStr = implode(', ', $delCategories);
		
				$db->execute("DELETE FROM {$database['prefix']}CategoryRelations WHERE item='$itemId' AND category IN ($delCategoryStr)");
			
				foreach($delCategories as $categoryId) {
					Category::rebuildCount($categoryId);
				}
			}

		}
	

		function move($id, $type = 'up') {
			global $database, $db;

			if(empty($id) || !isset($id)) 
				return false;
			if(!preg_match("/^[0-9]+$/", $id)) 
				return false;
			if(in_array($type, array('up', 'down'))) {
				$category = Category::getAll($id);
				if($category) {
					if($type=='up') {
						$prevCategory = $db->queryRow('SELECT * FROM '.$database['prefix'].'Categories WHERE priority < '. $category['priority'] .' ORDER BY priority DESC LIMIT 1');
						if($prevCategory['id'] != $category['id']) {
							$db->execute('UPDATE '.$database['prefix'].'Categories SET priority = '.$category['priority'].' WHERE id='.$prevCategory['id']);
							$db->execute('UPDATE '.$database['prefix'].'Categories SET priority = '.$prevCategory['priority'].' WHERE id='.$category['id']);
						}
					} else {
						$nextCategory = $db->queryRow('SELECT * FROM '.$database['prefix'].'Categories WHERE priority > '. $category['priority'] .' ORDER BY priority ASC LIMIT 1');
						if($nextCategory['id'] != $category['id']) {
							$db->execute('UPDATE '.$database['prefix'].'Categories SET priority = '.$category['priority'].' WHERE id='.$nextCategory['id']);
							$db->execute('UPDATE '.$database['prefix'].'Categories SET priority = '.$nextCategory['priority'].' WHERE id='.$category['id']);
						}
					}

					return true;
				} else return false;
			} else {
				return false;
			}
		}
				
		
		
		function inputFilters($itemId, $filter) {	
			
			global $database, $db;
		
			
			$db->execute('DELETE FROM '.$database['prefix'].'TagRelations WHERE item = "'.$itemId.'" AND type = "category"');
		
	$tags = explode(',',$filter);

			
			$tagChunk = array();
			$tagInsertChunk = array();
			
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
			if (!$db->query("SELECT id FROM {$database['prefix']}Tags WHERE name IN ($tagStr)")) return;
			while ($taglist = $db->fetchRow()) { 
					array_push($tagIdList, $taglist[0]); 
			}
			$db->free();

			$relationList = array();
			foreach ($tagIdList as $tagId) {
				array_push($relationList, "('$itemId', '$tagId', UNIX_TIMESTAMP(),'category')");
			}
			$relationStr = implode(',', $relationList); // ('itemId','tagId'),('itemId','tagId')...

			$db->execute("INSERT IGNORE INTO {$database['prefix']}TagRelations (item, tag, linked, type) VALUES $relationStr");
		
		}
		
		
				
			
		function rebuildFilters($category) {
			
			global $database, $db;

			if(empty($category) || !isset($category)) 
				return false;

		
	if(is_array($category)) {
				
	
			}			
			
			else if(!preg_match("/^[0-9]+$/", $category)) { 
				return false;
			
			} else {
	
					$category = Category::getAll($category);
	
			}

			if($category) {
				
				$db->execute('DELETE FROM '.$database['prefix'].'CategoryRelations WHERE category ="'.$category['id'].'" AND custom = "n"');
	
				$tags = $db->queryAll('SELECT DISTINCT t2.item, t2.tag FROM '.$database['prefix'].'TagRelations t1 LEFT JOIN '.$database['prefix'].'TagRelations t2 ON (t2.tag = t1.tag AND t2.type = "feed") WHERE t1.item = "' . $category['id'] . '" AND t1.type = "category" GROUP BY t2.item');
				
				
				if($tags) {	
	
				$relationList = array();

					foreach ($tags as $tag) {
			
						if (!Validator::is_empty($tag['item'])) {		
						
	array_push($relationList, "('{$tag['item']}', '{$category['id']}', UNIX_TIMESTAMP(), 'n')");
						
						}
					}
					
							
					$relationStr = implode(',', $relationList); // ('itemId','tagId'),('itemId','tagId')...
					$db->execute("INSERT IGNORE INTO {$database['prefix']}CategoryRelations (item, category, linked, custom) VALUES $relationStr");
			
				}
		
			}

		}

		function rebuildCount($category) {			
			global $database, $db;

			if(empty($category) || !isset($category)) 
				return false;
		
	
			if(is_array($category)) {
				
				$categoryId = $category['id'];
			
			} else if(!preg_match("/^[0-9]+$/", $category)) { 
				return false;
			
			} else {
		
				$categoryId = $category;
	
				$category = Category::getAll($category);
	
			}

			if($category) {
				$count = 0;
				if($result = $db->queryCell('SELECT count(DISTINCT cr.item) AS count FROM '.$database['prefix'].'CategoryRelations cr LEFT JOIN '.$database['prefix'].'FeedItems fi ON (fi.id = cr.item) WHERE cr.category = ' . $categoryId . ' AND fi.visibility = "y"')) {
					$count = $result;
				} 		
				
				
				$countOnLogin = $count;

				if($result = $db->queryCell('SELECT count(DISTINCT cr.item) AS count FROM '.$database['prefix'].'CategoryRelations cr LEFT JOIN '.$database['prefix'].'FeedItems fi ON (fi.id = cr.item) WHERE cr.category = ' . $categoryId . ' AND fi.visibility != "d"')) {
					$countOnLogin = $result;
				} 
				
				
				$db->execute('UPDATE '.$database['prefix'].'Categories SET count = '.$count.', countOnLogin = '.$countOnLogin.' WHERE id= ' . $categoryId);
			}
		}
		

		function getNextPriority() {	
			global $database, $db;
			$result = $db->queryCell('SELECT priority FROM '.$database['prefix'].'Categories ORDER BY priority DESC LIMIT 1');
			if(!$result) $result = 0;

			return $result + 1;
		}	
		
			
		function setItemCategory($itemId, $category) {
	
			global $db, $database;
			

			if (!preg_match("/^[0-9]+$/", $itemId)) 
				return false;		
		
			if (!preg_match("/^[0-9]+$/", $category)) 
				return false;
	
			if($db->execute('DELETE FROM '.$database['prefix'].'CategoryRelations WHERE item = "'.$itemId.'" AND custom = "y"')) {
		
				if($category>0 && $db->execute('INSERT IGNORE INTO '.$database['prefix'].'CategoryRelations (item, category, linked, custom) VALUES ("'.$itemId.'","'.$category.'",UNIX_TIMESTAMP(),"y")')) {
		
					return true;
				
				}
			
			}
			
		
			return false;
	
		}
	}
?>
