<?php
	Class Group {	
		function add($groupName) {
			global $database, $db;
			if (empty($groupName)) {
				return false;
			}

			$groupName = $db->escape($groupName);
		

			$priority = Group::getNextPriority();
			$result = $db->execute('INSERT INTO '.$database['prefix'].'Groups (name, priority) VALUES ("'.$groupName.'",'.$priority.')');
			$id = $db->insertId();
	
				
			Group::rebuildCount($id);
			return $result;
		}

		function doesNameExists($name) {
			global $database, $db;
			if (!isset($name) || empty($name)) {
				return false;
			}
			$n = $db->count('SELECT id FROM '.$database['prefix'].'Groups WHERE name="'.$db->escape($name).'"');
			return Validator::getBool($n);
		}

		function delete($id) {
			global $database, $db;

			if (empty($id) || !isset($id))
				return false;			
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			return $db->execute('DELETE FROM '.$database['prefix'].'Groups WHERE id="'.$id.'"');
		}

		function edit($id, $name) {
			global $database, $db;

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$id = $db->escape($id);
			$name = $db->escape($name);
			

			$result = $db->execute('UPDATE '.$database['prefix'].'Groups SET name = "'.$name.'" WHERE id='.$id);	
			
			
			
			Group::rebuildCount($id);

			return $result;
		}
		
		function get($id, $field = '*') {
			global $database, $db;

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$result = $db->queryCell('SELECT '.$field.' FROM '.$database['prefix'].'Groups WHERE id="'.$id.'"');
			return $result;
		}

		function getById($id) {
			global $database, $db;		

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$db->query('SELECT * FROM '.$database['prefix'].'Groups WHERE id="'.$id.'"');
			$result = $db->fetchArray();
			$db->free();
			return $result;
		}	
		
		function getByName($name) {
			global $database, $db;		

			if (empty($name) || !isset($name)) 
				return false;

			$db->query('SELECT * FROM '.$database['prefix'].'Groups WHERE name="'.$db->escape($name).'"');
			$result = $db->fetchArray();
			$db->free();
			return $result;
		}		

		function getAll($id) {
			global $database, $db;
			$db->query('SELECT * FROM '.$database['prefix'].'Groups WHERE id='.$id);
			return $db->fetchArray();
		}			
			
		function getGroupsAll($fields = 'id, name') {	
			global $database, $db;
			return $db->queryAll('SELECT '.$fields.' FROM '.$database['prefix'].'Groups');
		}

		function getList($count = -1) {		
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Groups ORDER BY priority ASC'. $count);
		}		
		
		function getRandomList($count = -1) {		
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Groups ORDER BY RAND()'. $count);
		}

		function getFeedIdList($id) {
			global $db, $database;
			
			$idlist = array();
			if($result = $db->queryAll('SELECT f.id FROM '.$database['prefix'].'Feeds f WHERE f.group = "'.$id.'" AND f.visibility = "y"')) {
				foreach($result as $item) {
					array_push($idlist, $item['id']);
				}
			}

			return $idlist;
		}


		function move($id, $type = 'up') {
			global $database, $db;

			if(empty($id) || !isset($id)) 
				return false;
			if(!preg_match("/^[0-9]+$/", $id)) 
				return false;
			if(in_array($type, array('up', 'down'))) {
				$group = Group::getAll($id);
				if($group) {
					if($type=='up') {
						$prevGroup = $db->queryRow('SELECT * FROM '.$database['prefix'].'Groups WHERE priority < '. $group['priority'] .' ORDER BY priority DESC LIMIT 1');
						if($prevGroup['id'] != $group['id']) {
							$db->execute('UPDATE '.$database['prefix'].'Groups SET priority = '.$group['priority'].' WHERE id='.$prevGroup['id']);
							$db->execute('UPDATE '.$database['prefix'].'Groups SET priority = '.$prevGroup['priority'].' WHERE id='.$group['id']);
						}
					} else {
						$nextGroup = $db->queryRow('SELECT * FROM '.$database['prefix'].'Groups WHERE priority > '. $group['priority'] .' ORDER BY priority ASC LIMIT 1');
						if($nextGroup['id'] != $group['id']) {
							$db->execute('UPDATE '.$database['prefix'].'Groups SET priority = '.$group['priority'].' WHERE id='.$nextGroup['id']);
							$db->execute('UPDATE '.$database['prefix'].'Groups SET priority = '.$nextGroup['priority'].' WHERE id='.$group['id']);
						}
					}

					return true;
				} else return false;
			} else {
				return false;
			}
		}
		

		function rebuildCount($group) {			
			global $database, $db;

			if(empty($group) || !isset($group)) 
				return false;
		
	
			if(is_array($group)) {
				
				$groupId = $group['id'];
			
			} else if(!preg_match("/^[0-9]+$/", $group)) { 
				return false;
			
			} else {
		
				$groupId = $group;
	
			}

			if($groupId) {
				$count = 0;
				if($result = $db->queryRow('SELECT COUNT(f.id) as count FROM '.$database['prefix'].'Feeds f WHERE f.group = "'.$groupId.'" AND f.visibility = "y"')) {
					$count = $result['count'];
				} 		
	

				$db->execute('UPDATE '.$database['prefix'].'Groups SET count = '.$count.' WHERE id= ' . $groupId);
			}
		}
		

		function getNextPriority() {	
			global $database, $db;
			$result = $db->queryCell('SELECT priority FROM '.$database['prefix'].'Groups ORDER BY priority DESC LIMIT 1');
			if(!$result) $result = 0;

			return $result + 1;
		}	
	
	}

	class GroupCategory {
		function buildGroupCategory($itemId, $feedId, $tags) {
			global $database, $db;

			if (!isset($tags) || !is_array($tags) || !isset($itemId) || !Validator::getBool($itemId) || !isset($feedId) || !Validator::getBool($feedId))
				return false;

			$category = array_shift($tags);
			if (empty($category)) return false;

			// 그룹 태그

			$db->execute("INSERT IGNORE INTO {$database['prefix']}Tags (name) VALUES ('{$category}')");
			$tagId = $db->queryCell("SELECT id FROM {$database['prefix']}Tags WHERE name = '{$category}'");

			$db->execute("INSERT IGNORE INTO {$database['prefix']}TagRelations (item,tag,type,linked) VALUES ($itemId,$tagId,'group_category',UNIX_TIMESTAMP())");
			
			return true;
		}

		function getList($id) {
			global $database, $db;

			if (!isset($id) || !Validator::getBool($id))
				return false;

			$result = $db->queryAll("SELECT t.name FROM {$database['prefix']}TagRelations tr LEFT JOIN {$database['prefix']}Tags t ON (t.id = tr.tag) LEFT JOIN {$database['prefix']}FeedItems fi ON (fi.id = tr.item) LEFT JOIN {$database['prefix']}Feeds f ON (f.id = fi.feed) WHERE tr.type = 'group_category' and f.group = $id GROUP BY t.id");

			return $result;
		}

	}
?>
