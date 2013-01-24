<?php

	class Stats {

		function visit($countRobotVisit = 'y') {
			global $database, $db;
			if ($countRobotVisit == 'n' && Stats::isKnownBot($_SERVER["HTTP_USER_AGENT"])) return;

			$now = time();
			$db->query('SELECT date FROM '.$database['prefix'].'DailyStatistics WHERE date="'.date('Ymd', $now).'"');
			if ($db->numRows() < 1) {
				$db->execute('INSERT INTO '.$database['prefix'].'DailyStatistics (date) VALUES ("'.date('Ymd', $now).'")');
			}
			$db->free();
			$db->execute('UPDATE '.$database['prefix'].'DailyStatistics SET visits=visits+1 WHERE date="'.date('Ymd', $now).'"');
			$db->execute('UPDATE '.$database['prefix'].'Settings SET value=value+1 WHERE name = "totalVisit"');
		}

		function getVisits(){
			global $database, $db;
			list($total) = $db->pick('SELECT value FROM '.$database['prefix'].'Settings WHERE name = "totalVisit" LIMIT 1');
			return $total;
		}

		function getTodayVisits(){
			global $database, $db;
			list($today) = $db->pick('SELECT visits FROM '.$database['prefix'].'DailyStatistics WHERE date="'.date('Ymd', time()).'"');
			return $today;
		}

		function getYesterdayVisits(){
			global $database, $db;
			list($ys) = $db->pick('SELECT visits FROM '.$database['prefix'].'DailyStatistics WHERE date="'.date("Ymd",strtotime("-1 day")).'"');
			return $ys;
		}

		function getWeeklyVisits(){
			global $database, $db;
			$lastSunday = date("Ymd",strtotime("last Sunday"));
			list($weekly) = $db->pick('SELECT sum(visits) FROM '.$database['prefix'].'DailyStatistics WHERE date >= "'.$lastSunday.'"');
			return $weekly;
		}

		function resetVisits() {
			global $database, $db;
			return $db->execute('UPDATE '.$database['prefix'].'Settings SET value = 0 WHERE name = "totalVisit"');
		}

		function countFeeds() {
			global $database, $db;
			list($result) = $db->pick('SELECT count(id) FROM '.$database['prefix'].'Feeds');
			return $result;
		}

		function countFeedItems() {
			global $database, $db;
			list($result) = $db->pick('SELECT count(id) FROM '.$database['prefix'].'FeedItems');
			return $result;
		}

		function countUsers() {
			global $database, $db;
			list($result) = $db->pick('SELECT count(id) FROM '.$database['prefix'].'Users');
			return $result;
		}

		function getLatestUpdate() {
			global $database, $db;
			list($result) = $db->pick('SELECT lastUpdate FROM '.$database['prefix'].'Feeds ORDER BY lastUpdate DESC LIMIT 0,1');
			return $result;
		}

		function resetTotalVisits() {
			global $database, $db, $session;
			requireComponent('Bloglounge.Model.Users');
			if (empty($session['id']) || User::get($session['id'], 'is_admin') != 'y') 
				return false;
			return $db->execute('UPDATE '.$database['prefix'].'Settings SET value=0 WHERE name = "totalVisit"');
		}

		function getTotalClicks() {
			global $database, $db;
			list($result) = $db->pick("SELECT sum(click) FROM {$database['prefix']}FeedItems");
			return $result;
		}

		function getTotalBoomUp() {
			global $database, $db;
			list($result) = $db->pick("SELECT sum(boomUp) FROM {$database['prefix']}FeedItems");
			return $result;
		}

		function getTotalBoomDown() {
			global $database, $db;
			list($result) = $db->pick("SELECT sum(boomDown) FROM {$database['prefix']}FeedItems");
			return $result;
		}

		function isKnownBot($agent) {
			$robots = array('1Noonbot', 'Accoona-AI-Agent', 'Allblog.net', 'Baiduspider', 'Blogbeat', 'Crawler', 'DAUMOA', 'DigExt', 'DrecomBot', 'Exabot', 'FeedChecker', 'FeedFetcher', 'Freedom', 'Gigabot', 'Googlebot', 'HMSE_Robot', 'IP*Works!', 'IRLbot', 'Jigsaw', 'LWP::Simple', 'Labrador', 'MJ12bot', 'Mirror Checking', 'Missigua Locator', 'NG/2.0', 'NaverBot', 'NutchCVS', 'PEAR HTTP_Request', 'PostFavorites', 'SBIder', 'W3C_Validator', 'WISEbot', 'Bloglounge', 'Y!J-BSC', 'Yahoo! Slurp', 'ZyBorg', 'archiver', 'carleson', 'cfetch', 'compatible; Eolin', 'favicon', 'feedfinder', 'findlinks', 'genieBot', 'ichiro', 'kinjabot', 'larbin', 'lwp-trivial', 'msnbot', 'psbot', 'sogou', 'urllib/1.15', 'voyager');
			foreach($robots as $robot)
				if (strpos($agent,$robot) !== false)
					return false;
			return true;
		}

	}
?>