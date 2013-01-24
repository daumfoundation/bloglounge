<?php
	Class Category {

		function add($categoryName) {
			global $database, $db;
			if (empty($categoryName)) {
				return false;
			}

			$categoryName = $db->escape($categoryName);
			
			$priority = Category::getNextPriority();
			$result = $db->execute('INSERT INTO '.$database['prefix'].'Categories (name, priority) VALUES ("'.$categoryName.'",'.$priority.')');

			return $result;
		}

		function doesNameExists($name) {
			global $database, $db;
			if (!isset($name) || empty($name)) {
				return false;
			}
			$n = $db->count('SELECT id FROM '.$database['prefix'].'Users WHERE name="'.$db->escape($name).'"');
			return Validator::getBool($n);
		}

		function delete($id) {
			global $database, $db;

			if (empty($id) || !isset($id))
				return false;			
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;
			$db->execute('UPDATE '.$database['prefix'].'FeedItems SET category = 0 WHERE category ="'.$id.'"');
			return $db->execute('DELETE FROM '.$database['prefix'].'Categories WHERE id="'.$id.'"');
		}

		function edit($id, $name) {
			global $database, $db;

			if (empty($id) || !isset($id)) 
				return false;
			if (!preg_match("/^[0-9]+$/", $id)) 
				return false;

			$id = $db->escape($id);
			$name = $db->escape($name);
			$result = $db->execute('UPDATE '.$database['prefix'].'Categories SET name = "'.$name.'" WHERE id='.$id);
			Category::rebuildCount($id);
			return $result;
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

		function rebuildCount($id) {			
			global $database, $db;

			if(empty($id) || !isset($id)) 
				return false;
			if(!preg_match("/^[0-9]+$/", $id)) 
				return false;
			
			
			$category = Category::getAll($id);

			if($category) {
				$count = 0;
				if($result = $db->queryCell('SELECT count(*) AS count FROM '.$database['prefix'].'FeedItems WHERE category ="'.$id.'"')) {
					$count = $result;
				} 

				
				$db->execute('UPDATE '.$database['prefix'].'Categories SET count = '.$count.' WHERE id='.$category['id']);
			}
		}

		function getNextPriority() {	
			global $database, $db;
			$result = $db->queryCell('SELECT priority FROM '.$database['prefix'].'Categories ORDER BY priority DESC LIMIT 1');
			if(!$result) $result = 0;

			return $result + 1;
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
		
		function getCategories($count = -1) {		
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Categories ORDER BY priority ASC'. $count);
		}		
		
		function getRandomCategories($count = -1) {		
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Categories ORDER BY RAND()'. $count);
		}
	}
?>
