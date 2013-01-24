<?php

	class Boom {

		function up($itemId) {
			global $database, $db, $session;
			
			$userid = $session['id'];
			$ip = $_SERVER['REMOTE_ADDR'];

			if (isLoggedIn()) {
				if(!Boom::isBoomedUp($itemId, 'userid', $userid)) {  
					$db->execute('INSERT INTO '.$database['prefix'].'Booms (userid,feeditem,type,ip,written) VALUES ("'.$userid.'", '.$itemId.' , "up", "'.$ip.'",'.gmmktime().')');
				} else return false;
			} else {
				if(!Boom::isBoomedUp($itemId, 'ip', $ip)) {  
					$db->execute('INSERT INTO '.$database['prefix'].'Booms (feeditem,type,ip,written) VALUES ('.$itemId.' , "up", "'.$ip.'",'.gmmktime().')');
				} else return false;
			}			

			$db->execute('UPDATE '.$database['prefix'].'FeedItems SET boomUp=boomUp+1 WHERE id="'.$itemId.'"');
			if ($db->affectedRows() == 0)
				return false;

			return true;
		}

		function down($itemId) {
			global $database, $db, $session;
			
			$userid = $session['id'];
			$ip = $_SERVER['REMOTE_ADDR'];

			if (isLoggedIn()) {
				if(!Boom::isBoomedDown($itemId, 'userid', $userid)) {  
					$db->execute('INSERT INTO '.$database['prefix'].'Booms (userid,feeditem,type,ip,written) VALUES ("'.$userid.'", '.$itemId.' , "down", "'.$ip.'",'.gmmktime().')');
				} else return false;
			} else {
				if(!Boom::isBoomedDown($itemId, 'ip', $ip)) {  
					$db->execute('INSERT INTO '.$database['prefix'].'Booms (feeditem,type,ip,written) VALUES ('.$itemId.' , "down", "'.$ip.'",'.gmmktime().')');
				} else return false;
			}			

			$db->execute('UPDATE '.$database['prefix'].'FeedItems SET boomDown=boomDown+1 WHERE id="'.$itemId.'"');
			if ($db->affectedRows() == 0)
				return false;

			return true;
		}

		function upReturn($itemId) {
			global $database, $db, $session;
			
			$userid = $session['id'];
			$ip = $_SERVER['REMOTE_ADDR'];

			if (isLoggedIn()) {
				if(Boom::isBoomedUp($itemId, 'userid', $userid)) {  
				$db->execute('DELETE FROM '.$database['prefix'].'Booms WHERE feeditem = '.$itemId.' AND type="up" AND userid="'.$userid.'"');
				} else return false;
			} else {
				if(Boom::isBoomedUp($itemId, 'ip', $ip)) {  
					$db->execute('DELETE FROM '.$database['prefix'].'Booms WHERE feeditem = '.$itemId.' AND type="up" AND ip="'.$ip.'"');
				} else return false;
			}			

			$db->execute('UPDATE '.$database['prefix'].'FeedItems SET boomUp=boomUp-1 WHERE id="'.$itemId.'"');
			if ($db->affectedRows() == 0)
				return false;

			return true;
		}

		function downReturn($itemId) {
			global $database, $db, $session;
			
			$userid = $session['id'];
			$ip = $_SERVER['REMOTE_ADDR'];

			if (isLoggedIn()) {
				if(Boom::isBoomedDown($itemId, 'userid', $userid)) {  
				$db->execute('DELETE FROM '.$database['prefix'].'Booms WHERE feeditem = '.$itemId.' AND type="down" AND userid="'.$userid.'"');
				} else return false;
			} else {
				if(Boom::isBoomedDown($itemId, 'ip', $ip)) {  
					$db->execute('DELETE FROM '.$database['prefix'].'Booms WHERE feeditem = '.$itemId.' AND type="down" AND ip="'.$ip.'"');
				} else return false;
			}			

			$db->execute('UPDATE '.$database['prefix'].'FeedItems SET boomDown=boomDown-1 WHERE id="'.$itemId.'"');
			if ($db->affectedRows() == 0)
				return false;

			return true;
		}

		function isBoomedUp($itemId,$type='userid', $value='') {
			global $database, $db;
			return $db->queryCell('SELECT userid FROM '.$database['prefix'].'Booms WHERE type="up" AND feeditem=' . $itemId . ' AND '.$type.'="' . $value . '"')!=NULL;
		}

		function isBoomedDown($itemId,$type='userid', $value='') {			
			global $database, $db;
			return $db->queryCell('SELECT userid FROM '.$database['prefix'].'Booms WHERE type="down" AND feeditem=' . $itemId . ' AND '.$type.'="' . $value . '"')!=NULL;
		}

		function getBoomCount($itemId) {
			global $database, $db;

			if (list($boomUp,$boomDown) = $db->pick('SELECT boomUp, boomDown FROM '.$database['prefix'].'FeedItems WHERE id="'.$itemId.'"')) {
				return array($boomUp,$boomDown,$boomUp-$boomDown);
			}
			return false;
		}

		function getRank($itemId) {
			global $database, $db;

			$rankBy = '';
			switch (Settings::get('rankBy')) {
				case 'click':
					$rankBy = 'click';
					break;
				case 'boom':
					$rankBy = 'boomUp-boomDown';
					break;
			}

			$rankLife = (mktime() - (Settings::get('rankLife') * 86400));
			if (!list($topScore) = $db->pick('SELECT max('.$rankBy.') FROM '.$database['prefix'].'FeedItems WHERE written >= ('.$rankLife.')'))
				return 0; // if failed, return 0 rank.
			if (!$topScore) return 0;

			if (!preg_match("/^[0-9]+$/", $topScore)) 
				return 0;

			if (!list($myScore) = $db->pick('SELECT ('.$rankBy.') FROM '.$database['prefix'].'FeedItems WHERE id="'.$itemId.'"'))
				return 0;

			if (!preg_match("/^[0-9]+$/", $myScore)) 
				return 0;

			$rank = 0;
			$r = ceil(($myScore / $topScore) * 100);
			switch(true) {
				case ($r >= 90) :// rank 5 (S grade)
					$rank = 5;
					break;
				case (($r >= 75) && ($r < 90)): // rank 4 (A grade)
					$rank = 4;
					break;
				case (($r >= 50) && ($r < 75)): // rank 3 (B grade)
					$rank = 3;
					break;
				case (($r >= 30) && ($r < 50)): // rank 2 (C grade)
					$rank = 2;
					break;
				case (($r >= 10) && ($r < 30)): // rank 1 (D grade)
					$rank = 1;
					break;
				case ($r < 10): // rank 0 (F grade)
				default:
					$rank = 0;
					break;
			}
			return $rank;
		}
	}

?>