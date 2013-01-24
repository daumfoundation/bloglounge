<?php
	define('ROOT', '..');
	include ROOT . '/lib/config.php';
	include ROOT . '/lib/setup/common.php';

	$step = isset($_GET['step']) ? $_GET['step'] : '1';
	$error = isset($_GET['error']) ? $_GET['error'] : 0;
	$path = $accessInfo['path'];								

	$step_desc =  '';

	switch($step) {
		case '1':
			$step_text = _t('설치') . ' - ' . _t('첫번째 단계') . ' : 1/4';
		break;
		case '2':
			$step_text = _t('설치') . ' - ' . _t('두번째 단계') . ' : 2/4';
		break;
		case '3':
			if (!isset($_POST['type'])) {
				Header("Location: {$path}/setup/?step=2");
				exit;
			} else if ($_POST['type'] == 'uninstall') {
				Header("Location: {$path}/setup/?step=uninstall");
				exit;
			} else if ($_POST['type'] == 'install') {
				Header("Location: {$path}/setup/?step=install");
				exit;
			} else if($_POST['type'] == 'migration') {
				Header("Location: {$path}/setup/?step=migration");
				exit;
			}
		break;
		case 'migration':
			$step_text = _t('업그레이드') . ' - ' . _t('세번째 단계') . ' : 3/4';
			$step_desc = _t('블로그라운지를 새로운 버전으로 업그레이드합니다.');

			/*if(!file_exists(ROOT.'/config.php')) {
				Header("Location: {$path}/setup/?step=3");
				exit;
			}*/
		break;
		case 'install':
			$step_text = _t('설치') . ' - ' . _t('세번째 단계') . ' : 3/4';
			$step_desc = _t('블로그라운지를 새롭게 설치하기 위한 정보를 입력해주세요.');

			if(file_exists(ROOT.'/config.php')) {
				Header("Location: {$path}/setup/?step=3");
				exit;
			}
		break;
		case '4': // 마지막 단계
			if (!isset($_POST['type']) || (($_POST['type'] != 'install') && ($_POST['type'] != 'migration'))) {
				Header("Location: {$path}/setup/?step=3");
				exit;
			}

			$step_text = _t('설치') . ' - ' . _t('마지막 단계') . ' : 4/4';

			$IV = array();
			$IV['type'] = $_POST['type'];

			if ($IV['type'] == 'install') { // 새로 설치하는 경우
				foreach ($_POST as $key=>$value) {
					if (Validator::enum($key, 'dbtype,dbserver,dbuserid,dbuserpw,dbname,dbprefix,userid,userpw,username,useremail'))
						$IV[$key] = addslashes($value);
				}
				$_GET = array();
				$_POST = array();
				
				$redirectValues = '';
				foreach (explode(',', 'dbtype,dbserver,dbuserid,dbname,dbprefix,userid,username,useremail') as $item) {
					if (!isset($IV[$item]) || !Validator::is_empty($IV[$item])) {
						$redirectValues .= '&' . $item . '=' . urlencode($IV[$item]);
					}
				}

				foreach (explode(',', 'dbtype,dbserver,dbuserid,dbuserpw,dbname,dbprefix,userid,userpw,username,useremail') as $item) {
					if (!isset($IV[$item]) || Validator::is_empty($IV[$item])) {
						Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=1" . $redirectValues . '&value=' . $item); 
						exit;
					}
				}

				$database['type'] = $IV['dbtype'];
				$database['server'] = $IV['dbserver'];
				$database['database']  = $IV['dbname'];
				$database['username'] = $IV['dbuserid'];
				$database['password'] = $IV['dbuserpw'];
				$database['prefix'] = $IV['dbprefix'];

				if (!preg_match('/[a-z0-9_]+$/i', $IV['dbprefix'])) {						
					Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=2" . $redirectValues); 
					exit;
				}

				$db = DB::start($database['type']);
				if (!$db->alive) {					
					Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=3" . $redirectValues); 
					exit;
				}

				$IV['userpw']  = Encrypt::hmac($IV['userid'], md5(md5($IV['userpw'])));
				$prefix = $IV['dbprefix'];

				$charset = '';
				switch ($database['type']) { // DBMS 별 Charset 세팅 구문
					default:
					case 'mysql':
						$charset = 'TYPE=MyISAM DEFAULT CHARSET=utf8';
						if (!$database['utf8']) $charset = 'TYPE=MyISAM';
						@$db->execute('SET SESSION collation_connection = \'utf8_general_ci\'');
						break;
				}
				

				// 테이블이 존재하는지 검사
				$check = $db->doesExistTableArray($prefix, explode(',', "{$prefix}Booms,{$prefix}DailyStatistics,{$prefix}DeleteHistory,{$prefix}FeedItems,{$prefix}Feeds,{$prefix}Sessions,{$prefix}SessionsData,{$prefix}SessionVisits,{$prefix}Settings,{$prefix}SkinSettings,{$prefix}Users"));
				if ($check['exist'] > 0) {
					Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=4" . $redirectValues); 
					exit;
				}

				$defaultTitle = 'Bloglounge';

				// 새 테이블 생성
				$scheme = "
					CREATE TABLE `{$prefix}Booms` (
					  `userid` INT( 11 ) NOT NULL default '0',
					  `feeditem` INT( 11 ) NOT NULL ,
					  `type` ENUM( 'up', 'down' ) NOT NULL ,
					  `ip` VARCHAR( 15 ) NOT NULL ,
					  `written` INT( 11 ) NOT NULL ,
					  INDEX ( `userid` , `written` )
					){$charset};

					CREATE TABLE `{$prefix}Categories` (
					  `id` int(11) NOT NULL auto_increment,
					  `name` varchar(255) NOT NULL,			
					  `count` int(11) NOT NULL default '0',					
					  `priority` int(11) NOT NULL,
					  PRIMARY KEY  (`id`),
					  UNIQUE KEY `name` (`name`)
					){$charset};

					CREATE TABLE `{$prefix}DailyStatistics` (
					  `date` int(11) NOT NULL default '0',
					  `visits` int(11) NOT NULL default '0',
					  PRIMARY KEY  (`date`)
					){$charset};

					CREATE TABLE `{$prefix}DeleteHistory` (
					  `id` int(11) NOT NULL auto_increment,
					  `feed` int(11) NOT NULL default '0',
					  `permalink` varchar(255) NOT NULL default '',
					  PRIMARY KEY  (`id`)
					){$charset};

					CREATE TABLE `{$prefix}FeedItems` (
					  `id` int(11) NOT NULL auto_increment,
					  `feed` int(11) NOT NULL default '0',
					  `author` varchar(255) NOT NULL default '',
					  `category` int(11) NOT NULL default '0',
					  `permalink` varchar(255) NOT NULL default '',
					  `title` varchar(255) NOT NULL default '',
					  `description` text NOT NULL,
					  `tags` varchar(255) NOT NULL default '',
					  `enclosure` varchar(255) NOT NULL default '',
					  `written` int(11) NOT NULL default '0',
					  `focus`  enum('y','n') NOT NULL default 'n',
					  `click` int(11) unsigned NOT NULL default '0',
					  `boomUp` int(11) NOT NULL default '0',
					  `boomDown` int(11) NOT NULL default '0',
					  `visibility` enum('y','n','d') default 'y',
					  `autoUpdate` enum('y','n') NOT NULL default 'y',
					  `allowRedistribute` enum('y','n') NOT NULL default 'y',
					  `thumbnailId` int(11) NOT NULL default '0',
					  PRIMARY KEY  (`id`),
					  KEY `feed` (`feed`),
					  KEY `written` (`written`),
					  KEY `permalink` (`permalink`)
					){$charset};

					CREATE TABLE `{$prefix}Feeds` (
					  `id` int(11) NOT NULL auto_increment,
					  `owner` int(11) NOT NULL default '0',
					  `xmlURL` varchar(255) NOT NULL default '',
					  `xmlType` varchar(10) NOT NULL default '',
					  `blogURL` varchar(255) NOT NULL default '',
					  `author` varchar(255) NOT NULL default '',
					  `title` varchar(255) NOT NULL default '',
					  `description` varchar(255) NOT NULL default '',
					  `logo` varchar(255) default NULL,
					  `language` varchar(5) NOT NULL default 'ko',
					  `lastUpdate` int(11) NOT NULL default '0',
					  `feedCount` int(11) NOT NULL default '0',
					  `created` int(11) NOT NULL default '0',
					  `visibility` enum('y','n') NOT NULL default 'y',
					  `filter` text NOT NULL,
					  `autoUpdate` enum('y','n') NOT NULL default 'y',
					  `allowRedistribute` enum('y','n') NOT NULL default 'y',	
					  `everytimeUpdate` enum('y','n') NOT NULL default 'n',
					  PRIMARY KEY  (`id`),
					  UNIQUE KEY `xmlURL` (`xmlURL`)
					){$charset};

					CREATE TABLE `{$prefix}Medias` (
					  `id` int(11) NOT NULL auto_increment,
					  `feeditem` int(11) NOT NULL,
					  `thumbnail` varchar(255) NOT NULL default '',
					  `source` varchar(255) NOT NULL default '',
					  `width` int(11) NOT NULL default '0',
					  `height` int(11) NOT NULL default '0',
					  `type` enum('image','movie') NOT NULL default 'image',			
					  `via` varchar(255) NOT NULL default '',
					  PRIMARY KEY  (`id`),
					  INDEX ( `feeditem` )
					){$charset};	
					
					CREATE TABLE `{$prefix}Plugins` (
					  `id` int(11) unsigned NOT NULL auto_increment,
					  `name` VARCHAR( 255 ) NOT NULL default '',
					  `settings` TEXT NOT NULL,
					  `status` ENUM( 'on', 'off' ) NOT NULL default 'off',
					  PRIMARY KEY  (`id`),
					  UNIQUE KEY `name` (`name`)
					){$charset};

					CREATE TABLE `{$prefix}ServiceSettings` (
					  name varchar(64) NOT NULL default '',
					  value text NOT NULL,
					  PRIMARY KEY  (name)
					) {$charset};

					CREATE TABLE `{$prefix}SessionVisits` (
					  `id` varchar(32) NOT NULL default '',
					  `address` varchar(15) NOT NULL default '',
					  `blog` int(11) NOT NULL default '0',
					  PRIMARY KEY  (`id`,`address`,`blog`)
					){$charset};

					CREATE TABLE `{$prefix}Sessions` (
					  `id` varchar(32) NOT NULL default '',
					  `address` varchar(11) NOT NULL default '0',
					  `userid` int(11) default NULL,
					  `preexistence` int(11) default NULL,
					  `server` varchar(64) NOT NULL default '',
					  `request` varchar(255) NOT NULL default '',
					  `referer` varchar(255) NOT NULL default '',
					  `timer` float NOT NULL default '0',
					  `created` int(11) NOT NULL default '0',
					  `updated` int(11) NOT NULL default '0',
					  PRIMARY KEY  (`id`,`address`)
					){$charset};

					CREATE TABLE `{$prefix}SessionsData` (
					  `id` varchar(32) NOT NULL default '',
					  `data` mediumtext,
					  `address` varchar(11) NOT NULL default '0',
					  `updated` int(11) NOT NULL default '0',
					  PRIMARY KEY  (`id`)
					){$charset};

					CREATE TABLE `{$prefix}Settings` (
					  `id` int(11) unsigned NOT NULL auto_increment,
					  `metaskin` varchar(200) NOT NULL default '',
					  `linkskin` varchar(200) NOT NULL default '',
					  `title` varchar(100) NOT NULL default '',
					  `description` varchar(240) NOT NULL default '',
					  `logo` varchar(100) NOT NULL default '',
					  `updateCycle` int(6) NOT NULL default '60',
					  `updateProcess` enum('repeat','random') NOT NULL default 'repeat',	
					  `archivePeriod` int(6) NOT NULL default '0',
					  `totalVisit` int(11) NOT NULL default '0',
					  `filter` text NOT NULL,
					  `blackfilter` text NOT NULL,
					  `restrictJoin` enum('y','n') NOT NULL default 'n',
					  `restrictBoom` enum('y','n') NOT NULL default 'n',
					  `rankBy` enum('boom','click') NOT NULL default 'boom',
					  `rankPeriod` int(6) NOT NULL default '6',
					  `rankLife` int(11) unsigned NOT NULL default '30',
					  `welcomePack` varchar(200) NOT NULL default 'default',
					  `language` varchar(10) NOT NULL default 'ko',
					  `boomDownReactor` enum('none','hide','delete') NOT NULL default 'none',
					  `boomDownReactLimit` int(11) NOT NULL default '20',
					  `useRssOut` enum('y','n') NOT NULL default 'y',
					  `countRobotVisit` enum('y','n') NOT NULL default 'y',
					  `cacheThumbnail` enum('y','n') NOT NULL default 'y',
					  `thumbnailLimit` int(11) NOT NULL default '3',	
					  PRIMARY KEY  (`id`)
					){$charset};

					CREATE TABLE `{$prefix}SkinSettings` (
					  `id` int(11) NOT NULL auto_increment,  				
					  `postList` int(11) NOT NULL default '10',
					  `postTitleLength` int(11) NOT NULL default '40',
					  `postDescLength` int(11) NOT NULL default '400',
					  `postNewLife` int(3) unsigned NOT NULL default '6',
					  `feedList` int(11) NOT NULL default '20',
					  `feedOrder` enum('created','lastUpdate') NOT NULL default 'created',
					  `feedTitleLength` int(11) NOT NULL default '40',
					  `boomList` int(11) NOT NULL default '10',
					  `boomTitleLength` int(11) NOT NULL default '40',
					  `feedListPage` int(11) unsigned NOT NULL default '20',
					  `feedListPageOrder` enum('created','lastUpdate') NOT NULL default 'created',
					  `feedListPageTitleLength` int(11) unsigned NOT NULL default '40',		
					  `feedListRecentFeedList` int(11) unsigned NOT NULL default '4',
					  `focusList` int(11) unsigned NOT NULL default '4',
					  `focusTitleLength` int(11) unsigned NOT NULL default '40',
					  `focusDescLength` int(11) unsigned NOT NULL default '100',
					  `tagCloudOrder` enum('name','frequency','random') NOT NULL default 'random',
					  `tagCloudLimit` int(11) unsigned NOT NULL default '10',
					  PRIMARY KEY  (`id`)
					){$charset};

					CREATE TABLE `{$prefix}TagRelations` (
					  `item` int(11) unsigned NOT NULL default '0',
					  `tag` int(11) unsigned NOT NULL default '0',	
					  `linked` int(11) NOT NULL default '0',
					  PRIMARY KEY  (`tag`,`item`)
					){$charset};

					CREATE TABLE `{$prefix}Tags` (
					  `id` int(11) NOT NULL auto_increment,
					  `name` varchar(255) NOT NULL default '',
					  PRIMARY KEY  (`id`),
					  UNIQUE KEY `name` (`name`)
					){$charset};

					CREATE TABLE `{$prefix}Users` (
					  `id` int(11) unsigned NOT NULL auto_increment,
					  `loginid` varchar(64) NOT NULL default '',
					  `password` varchar(32) NOT NULL default 'NULL',
					  `name` varchar(32) NOT NULL default '',
					  `email` varchar(255) NOT NULL default '',
					  `created` int(11) NOT NULL default '0',
					  `lastLogin` int(11) NOT NULL default '0',
					  `host` int(11) default '0',
					  `is_admin` enum('y','n') default 'n',
					  `is_accepted` enum('y','n') NOT NULL default 'y',
					  PRIMARY KEY  (`id`),
					  UNIQUE KEY `loginid` (`loginid`,`name`)
					){$charset};

					INSERT INTO {$prefix}Settings (metaskin,linkskin,title) VALUES ('basic','link_basic','{$defaultTitle}');
					INSERT INTO {$prefix}SkinSettings (id) VALUES (1);
					INSERT INTO {$prefix}Users (loginid, password, name, email, created, is_admin) VALUES ('{$IV['userid']}', '{$IV['userpw']}', '{$IV['username']}', '{$IV['useremail']}', UNIX_TIMESTAMP(), 'y');
				";

				$query = explode(';', trim($scheme));
				foreach ($query as $sub) {
					if (Validator::is_empty($sub)) continue;
					if (!$db->execute($sub)) {
						$db->execute("DROP TABLE 				
									{$prefix}Booms,
									{$prefix}Categories,
									{$prefix}DailyStatistics,
									{$prefix}DeleteHistory,
									{$prefix}FeedItems,
									{$prefix}Feeds,
									{$prefix}Medias,
									{$prefix}Sessions,
									{$prefix}SessionsData,
									{$prefix}SessionVisits,
									{$prefix}Settings,
									{$prefix}SkinSettings,
									{$prefix}Plugins,
									{$prefix}Users,
									{$prefix}Tags,
									{$prefix}TagRelations");

						Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=6" . $redirectValues); 
						exit;

					}
				}

				// config.php 생성
				
				$fp = fopen(ROOT.'/config.php', 'w+');
				if ($fp) {
					$confCon = "<?php
						\$database['type'] = '".$IV['dbtype']."';
						\$database['server'] = '".$IV['dbserver']."';
						\$database['database']  = '".$IV['dbname']."';
						\$database['username'] = '".$IV['dbuserid']."';
						\$database['password'] = '".$IV['dbuserpw']."';
						\$database['prefix'] = '".$prefix."';

						\$service['path'] = '".$path."';
						\$service['timeout'] = 3600;
					?>";
					fwrite($fp, $confCon);	
					fclose($fp);
					@chmod(ROOT . '/config.php', 0666);
				} else {
					Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=5" . $redirectValues); 
					exit;
				}

				// remove.lock 생성

				$fp = fopen(ROOT.'/remove.lock','w+');
				if($fp) {
					fwrite($fp, 'remove lock');	
					fclose($fp);				
					@chmod(ROOT . '/remove.lock', 0666);
				} else {
					Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=5" . $redirectValues); 
					exit;
				}

				// 새로 설치 완료
				Header("Location: {$path}/setup/?step=complete");
				exit;
			}  else if ($IV['type'] == 'migration') {
					// db 연결
					if (!file_exists(ROOT . '/config.php')) {
						Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=7"); 
						exit;
					}

					include ROOT . '/config.php'; // $database
					if (!isset($database['type'])) $database['type'] = 'mysql';
					$db = DB::start($database['type']);
					if (!$db->alive) {
						Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=8"); 
						exit;
					}

					$charset = '';
					switch ($database['type']) { // DBMS 별 Charset 세팅 구문
						default:
						case 'mysql':
							$charset = 'TYPE=MyISAM DEFAULT CHARSET=utf8';
							if (!$database['utf8']) $charset = 'TYPE=MyISAM';
							@$db->execute('SET SESSION collation_connection = \'utf8_general_ci\'');
							break;
					}
					$prefix = $database['prefix'];
					

					// 모든 버전에 공통으로 존재하는 테이블의 확인
					// 테이블이 존재하는지 검사
					$tables = explode(',', "{$prefix}DailyStatistics,{$prefix}FeedItems,{$prefix}Feeds,{$prefix}Settings,{$prefix}Users");
					$check = $db->doesExistTableArray($prefix, $tables);
					if ($check['exist'] != count($tables)) {
							Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=9"); 
							exit;
					}
					
					$IV2 = array();
					foreach ($_POST as $key=>$value) {
						if (Validator::enum($key, 'userid,userpw'))
							$IV2[$key] = $db->escape($value);
					}

					if(empty($IV2['userid']) || empty($IV2['userpw'])) {	// 관리자정보
						Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=10"); 
						exit;
					} else {		
						if (list($loginid, $password, $is_admin) = $db->pick("SELECT loginid, password, is_admin FROM {$prefix}Users WHERE loginid='{$IV2['userid']}'")) {
							if($password != Encrypt::hmac($IV2['userid'], md5(md5($IV2['userpw'])))) { // 비밀번호 다름
								Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=12"); 
								exit;
							}
							if($is_admin == 'n') { // 관리자아님
								Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=13"); 
								exit;
							}
						} else { // 찾을수 없음
							Header("Location: {$path}/setup/?step=" . $IV['type'] . "&error=11"); 
							exit;
						}
					}

					if (!$db->doesExistTable("{$prefix}Sessions")) { // alpha1
						// create table Sessions, SessionsData, SessionVisits
						$sessionQueries = "CREATE TABLE `{$prefix}SessionVisits` (
						  `id` varchar(32) NOT NULL default '',
						  `address` varchar(15) NOT NULL default '',
						  `blog` int(11) NOT NULL default '0',
						  PRIMARY KEY  (`id`,`address`,`blog`)
						){$charset};

						CREATE TABLE `{$prefix}Sessions` (
						  `id` varchar(32) NOT NULL default '',
						  `address` varchar(11) NOT NULL default '0',
						  `userid` int(11) default NULL,
						  `preexistence` int(11) default NULL,
						  `server` varchar(64) NOT NULL default '',
						  `request` varchar(255) NOT NULL default '',
						  `referer` varchar(255) NOT NULL default '',
						  `timer` float NOT NULL default '0',
						  `created` int(11) NOT NULL default '0',
						  `updated` int(11) NOT NULL default '0',
						  PRIMARY KEY  (`id`,`address`)
						){$charset};

						CREATE TABLE `{$prefix}SessionsData` (
						  `id` varchar(32) NOT NULL default '',
						  `data` mediumtext,
						  `address` varchar(11) NOT NULL default '0',
						  `updated` int(11) NOT NULL default '0',
						  PRIMARY KEY  (`id`)
						){$charset};";

						foreach (explode(";", $sessionQueries) as $query) {
							$db->execute($query);
						}
					}

					if ($db->queryCell("DESC {$prefix}Sessions address", 'Type') != 'varchar(11)') { // from alpha2, upgraded beta1
						$db->execute("ALTER TABLE {$prefix}Sessions CHANGE address address varchar(11) NOT NULL default '0'");
						$db->execute("ALTER TABLE {$prefix}SessionsData CHANGE address address varchar(11) NOT NULL default '0'");
					}

				/*	if (!$db->doesExistTable("{$prefix}BoomHistory")) { // alpha1
						$db->execute("CREATE TABLE `{$prefix}BoomHistory` (
						  `userid` int(11) NOT NULL default '0',
						  `boomUp` text NOT NULL,
						  `boomDown` text NOT NULL,
						  PRIMARY KEY  (`userid`)
						){$charset}");
					} */

					if (!$db->doesExistTable("{$prefix}DeleteHistory")) { // alpha1
						$db->execute("CREATE TABLE `{$prefix}DeleteHistory` (
						  `id` int(11) NOT NULL auto_increment,
						  `feed` int(11) NOT NULL default '0',
						  `permalink` varchar(255) NOT NULL default '',
						  PRIMARY KEY  (`id`)
						){$charset}");
					}

					if (!$db->doesExistTable("{$prefix}SkinSettings")) { // alpha1
						$db->execute("CREATE TABLE `{$prefix}SkinSettings` (
							  `id` int(11) NOT NULL auto_increment,  				
							  `postList` int(11) NOT NULL default '10',
							  `postTitleLength` int(11) NOT NULL default '40',
							  `postDescLength` int(11) NOT NULL default '400',
							  `postNewLife` int(3) unsigned NOT NULL default '6',
							  `feedList` int(11) NOT NULL default '20',
							  `feedOrder` enum('created','lastUpdate') NOT NULL default 'created',
							  `feedTitleLength` int(11) NOT NULL default '40',
							  `boomList` int(11) NOT NULL default '10',
							  `boomTitleLength` int(11) NOT NULL default '40',
							  `feedListPage` int(11) unsigned NOT NULL default '20',
							  `feedListPageOrder` enum('created','lastUpdate') NOT NULL default 'created',
							  `feedListPageTitleLength` int(11) unsigned NOT NULL default '40',		
							  `feedListRecentFeedList` int(11) unsigned NOT NULL default '4',
							  `focusList` int(11) unsigned NOT NULL default '4',
							  `focusTitleLength` int(11) unsigned NOT NULL default '40',
							  `focusDescLength` int(11) unsigned NOT NULL default '100',
							  `tagCloudOrder` enum('name','frequency','random') NOT NULL default 'random',
							  `tagCloudLimit` int(11) unsigned NOT NULL default '10',
						  PRIMARY KEY  (`id`)
						){$charset}");
						$db->execute("INSERT INTO {$prefix}SkinSettings (id) value (1)");			  
					}


					// feedItems 
					if (!$db->exists("DESC {$prefix}FeedItems `click`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `click` INT(11) UNSIGNED NOT NULL default 0 AFTER `written`");
					}
					if (!$db->exists("DESC {$prefix}FeedItems `boomUp`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `boomUp` INT(11) NOT NULL default 0 AFTER `click`");
					}
					if (!$db->exists("DESC {$prefix}FeedItems `boomDown`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `boomDown` INT(11) NOT NULL default 0 AFTER `boomUp`");
					}
/*					if (!$db->exists("DESC {$prefix}FeedItems `comments`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `comments` INT(11) UNSIGNED NOT NULL default 0 AFTER `boomDown`");
					}*/
					if (!$db->exists("DESC {$prefix}FeedItems `visibility`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `visibility` ENUM('y','d','n') NOT NULL default 'y' AFTER `boomDown`");
					}
					if (!$db->exists("DESC {$prefix}FeedItems `autoUpdate`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `autoUpdate` ENUM('y','n') NOT NULL default 'y' AFTER `visibility`");
					}
					if (!$db->exists("DESC {$prefix}FeedItems `allowRedistribute`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `allowRedistribute` ENUM('y','n') NOT NULL default 'y' AFTER `autoUpdate`");
					}
				/*	if (!$db->exists("DESC {$prefix}FeedItems `thumbnail`")) {
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `thumbnail` VARCHAR(100) default NULL AFTER `allowRedistribute`");
					}*/

					// feeds
					if (!$db->exists("DESC {$prefix}Feeds `filter`")) {
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `filter` TEXT NOT NULL AFTER `created`");
					}
				/*	if (!$db->exists("DESC {$prefix}Feeds `isVerified`")) {
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `isVerified` ENUM('y','n') NOT NULL default 'n' AFTER `filter`");
					}
					if (!$db->exists("DESC {$prefix}Feeds `verifier`")) {
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `isVerified` TEXT NOT NULL AFTER `isVerified`");
					}*/
					if (!$db->exists("DESC {$prefix}Feeds `autoUpdate`")) {
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `autoUpdate` ENUM('y','n') NOT NULL default 'y' AFTER `filter`");
					}
					if (!$db->exists("DESC {$prefix}Feeds `allowRedistribute`")) {
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `allowRedistribute` ENUM('y','n') NOT NULL default 'y' AFTER `autoUpdate`");
					}

					// settings
					if (!$db->exists("DESC {$prefix}Settings `blackfilter`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `blackfilter` TEXT NOT NULL AFTER `filter`");
					}
					if (!$db->exists("DESC {$prefix}Settings `restrictBoom`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `restrictBoom` ENUM('y','n') NOT NULL default 'n' AFTER `restrictJoin`");
					}
					if (!$db->exists("DESC {$prefix}Settings `rankBy`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `rankBy` ENUM('boom','click') NOT NULL default 'boom' AFTER `restrictBoom`");
					}
					if (!$db->exists("DESC {$prefix}Settings `rankPeriod`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `rankPeriod` INT(6) NOT NULL default 6 AFTER `rankBy`");
					}
					if (!$db->exists("DESC {$prefix}Settings `rankLife`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `rankLife` INT(11) UNSIGNED NOT NULL default 30 AFTER `rankPeriod`");
					}
					
					/*if (!$db->exists("DESC {$prefix}Settings `useVerifier`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `useVerifier` ENUM('y','n') NOT NULL default 'n' AFTER `rankLife`");
					}
					if (!$db->exists("DESC {$prefix}Settings `verifierPack`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `verifierPack` VARCHAR(200) NOT NULL default 'default' AFTER `useVerifier`");
					}*/

					if (!$db->exists("DESC {$prefix}Settings `welcomePack`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `welcomePack` VARCHAR(200) NOT NULL default 'default' AFTER `rankLife`");
					}
					if (!$db->exists("DESC {$prefix}Settings `language`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `language` VARCHAR(10) NOT NULL default 'ko' AFTER `welcomePack`");
					}
					if (!$db->exists("DESC {$prefix}Settings `boomDownReactor`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `boomDownReactor` ENUM('none','hide','delete') NOT NULL default 'none' AFTER `language`");
					}
					if (!$db->exists("DESC {$prefix}Settings `boomDownReactLimit`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `boomDownReactLimit` INT(11) NOT NULL default 20 AFTER `boomDownReactor`");
					} // from alpha1
/*
					if (!$db->exists("DESC {$prefix}Settings `collectComments`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `collectComments` ENUM('y','n') NOT NULL default 'y' AFTER `boomDownReactLimit`");
					}*/
					if (!$db->exists("DESC {$prefix}Settings `useRssOut`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `useRssOut` ENUM('y','n') NOT NULL default 'y' AFTER `boomDownReactLimit`");
					}
					if (!$db->exists("DESC {$prefix}Settings `countRobotVisit`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `countRobotVisit` ENUM('y','n') NOT NULL default 'y' AFTER `useRssOut`");
					}
					if (!$db->exists("DESC {$prefix}Settings `cacheThumbnail`")) {
						$db->execute("ALTER TABLE {$prefix}Settings ADD `cacheThumbnail` ENUM('y','n') NOT NULL default 'y' AFTER `countRobotVisit`");
					} // from alpha2

					if ($db->exists("DESC {$prefix}Settings `syncToEolin`")) {
						$db->execute("ALTER TABLE {$prefix}Settings DROP `syncToEolin`");
					}

					// skinSettings
					if (!$db->exists("DESC {$prefix}SkinSettings `postNewLife`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `postNewLife` INT(3) UNSIGNED NOT NULL default 6 AFTER `postDescLength`");
					}
					if (!$db->exists("DESC {$prefix}SkinSettings `feedListPage`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `feedListPage` INT(11) UNSIGNED NOT NULL default 20 AFTER `boomTitleLength`");
					}
					if (!$db->exists("DESC {$prefix}SkinSettings `feedListPageOrder`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `feedListPageOrder` ENUM('created','lastUpdate') NOT NULL default 'created' AFTER `feedListPage`");
					}
					if (!$db->exists("DESC {$prefix}SkinSettings `feedListPageTitleLength`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `feedListPageTitleLength` INT(11) UNSIGNED NOT NULL default 40 AFTER `feedListPageOrder`");
					}	

					if (!$db->exists("DESC {$prefix}SkinSettings `tagCloudOrder`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `tagCloudOrder` ENUM('name','frequency','random') NOT NULL default 'random' AFTER `feedListPageTitleLength`");
					}
					if (!$db->exists("DESC {$prefix}SkinSettings `tagCloudLimit`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `tagCloudLimit` INT(11) UNSIGNED NOT NULL default 10 AFTER `tagCloudOrder`");
					}

					// users
					if (!$db->exists("DESC {$prefix}Users `is_accepted`")) {
						$db->execute("ALTER TABLE {$prefix}Users ADD `is_accepted` ENUM('y','n') NOT NULL default 'y' AFTER `is_admin`");
					}

					// SkinSettings 에 allowHTML 추가 (1.1 정식에서)
					/*if (!$db->exists("DESC {$prefix}SkinSettings `allowHTML`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `allowHTML` ENUM('y','n') NOT NULL default 'y' AFTER `tagCloudLimit`");
					}*/

					// 1.2 ncloud added ( Bloglounge )
					
					$checkups = array();

					if (!$db->exists("DESC {$prefix}SkinSettings `focusList`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `focusList` int(11) unsigned NOT NULL default '4' AFTER `feedListPageTitleLength`");	
						
						array_push($checkups, array('success', _t('스킨설정 테이블에 포커스 개수 필드를 생성했습니다.')));
					}

					if (!$db->exists("DESC {$prefix}SkinSettings `focusTitleLength`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `focusTitleLength` int(11) unsigned NOT NULL default '40' AFTER `focusList`");

						array_push($checkups, array('success', _t('스킨설정 테이블에 포커스 제목 글수 필드를 생성했습니다.')));
					}

					if (!$db->exists("DESC {$prefix}SkinSettings `focusDescLength`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD  `focusDescLength` int(11) unsigned NOT NULL default '100' AFTER `focusTitleLength`");
						
						array_push($checkups, array('success', _t('스킨설정 테이블에 포커스 내용 글수 필드를 생성했습니다.')));
					}				


					if ($db->exists("DESC {$prefix}SkinSettings `allowHTML`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings DROP `allowHTML`");

						array_push($checkups, array('success', _t('스킨설정 테이블에서 HTML허용여부 필드를 삭제했습니다.')));
					}			
					
					if (!$db->exists("DESC {$prefix}SkinSettings `feedListRecentFeedList`")) {
						$db->execute("ALTER TABLE {$prefix}SkinSettings ADD `feedListRecentFeedList` int(11) unsigned NOT NULL default '4'  AFTER `feedListPageTitleLength`");

						array_push($checkups, array('success', _t('스킨설정 테이블에서 블로그의 최근 글 개수 필드를 삭제했습니다.')));
					}

					if (!$db->doesExistTable("{$prefix}Categories")) { // 1.2 ncloud
						$db->execute("CREATE TABLE `{$prefix}Categories` (
							  `id` int(11) NOT NULL auto_increment,
							  `name` varchar(255) NOT NULL, 
							  `count` int(11) NOT NULL default '0',					
							  `priority` int(11) NOT NULL,
							  PRIMARY KEY  (`id`),
							  UNIQUE KEY `name` (`name`)
							){$charset};");		
						
						array_push($checkups, array('success', _t('분류 테이블을 추가했습니다.')));
					}			

					if (!$db->doesExistTable("{$prefix}Plugins")) { // 1.2 ncloud
						$db->execute("CREATE TABLE `{$prefix}Plugins` (
						  `id` int(11) unsigned NOT NULL auto_increment,
						  `name` VARCHAR( 255 ) NOT NULL default '',
						  `settings` TEXT NOT NULL,
						  `status` ENUM( 'on', 'off' ) NOT NULL default 'off',
						  PRIMARY KEY  (`id`),
						  UNIQUE KEY `name` (`name`)
						){$charset};");					

						array_push($checkups, array('success', _t('플러그인 테이블을 추가했습니다.')));
					}
					
					if (!$db->doesExistTable("{$prefix}ServiceSettings")) { // 1.2 ncloud
						$db->execute("CREATE TABLE `{$prefix}ServiceSettings` (
							 name varchar(64) NOT NULL default '',
							 value text NOT NULL,
							 PRIMARY KEY  (name)
						) {$charset};");					

						array_push($checkups, array('success', _t('서비스설정 테이블을 추가했습니다.')));
					}


					if (!$db->exists("DESC {$prefix}FeedItems `category`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `category` INT(11) NOT NULL default 0 AFTER `author`");

						array_push($checkups, array('success', _t('피드아이템 테이블에 카데고리필드를 추가했습니다.')));
					}

					if (!$db->exists("DESC {$prefix}FeedItems `focus`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `focus`  enum('y','n') NOT NULL default 'n' AFTER `written`");
						array_push($checkups, array('success', _t('피드아이템 테이블에 포커스필드를 추가했습니다.')));
					}
					
					if (!$db->doesExistTable("{$prefix}Booms")) { // 1.2 ncloud
						$db->execute("CREATE TABLE `{$prefix}Booms` (
						  `userid` INT( 11 ) NOT NULL default '0',
						  `feeditem` INT( 11 ) NOT NULL ,
						  `type` ENUM( 'up', 'down' ) NOT NULL ,
						  `ip` VARCHAR( 15 ) NOT NULL ,
						  `written` INT( 11 ) NOT NULL ,
						  INDEX ( `userid` , `written` )
							){$charset};
						");						
						
						array_push($checkups, array('success', _t('붐 테이블을 추가했습니다.')));

					}
					if (!$db->doesExistTable("{$prefix}Medias")) { // 1.2 ncloud
						$db->execute("CREATE TABLE `{$prefix}Medias` (
						  `id` int(11) NOT NULL auto_increment,
						  `feeditem` int(11) NOT NULL,
						  `thumbnail` varchar(255) NOT NULL default '',
						  `source` varchar(255) NOT NULL default '',
						  `width` int(11) NOT NULL default '0',
						  `height` int(11) NOT NULL default '0',
						  `type` enum('image','movie') NOT NULL default 'image',	
						  `via` varchar(255) NOT NULL default '',
						  PRIMARY KEY  (`id`),
						  INDEX ( `feeditem` )
						){$charset};
						");					
						
						array_push($checkups, array('success', _t('미디어 테이블을 추가했습니다.')));

					}					
					
					if (!$db->exists("DESC {$prefix}Settings `thumbnailLimit`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}Settings ADD `thumbnailLimit` int(11) NOT NULL default '3'");		
						array_push($checkups, array('success', _t('설정 테이블에 썸네일저장개수 필드를 추가했습니다.')));
					}	
					
					if (!$db->exists("DESC {$prefix}Settings `updateProcess`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}Settings ADD `updateProcess` enum('repeat','random') NOT NULL default 'repeat' AFTER `updateCycle`");		
						array_push($checkups, array('success', _t('설정 테이블에 업데이트방식에 대한 필드를 추가했습니다.')));
					}

					if ($db->exists("DESC {$prefix}Settings `skin`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}Settings CHANGE `skin` `metaskin` varchar(200)");	
						array_push($checkups, array('success', _t('설정 테이블에 스킨필드를 메타스킨 필드명으로 변경했습니다.')));

					}

					if (!$db->exists("DESC {$prefix}Settings `linkskin`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}Settings ADD `linkskin` varchar(200) NOT NULL default '' AFTER `metaskin`");		
						array_push($checkups, array('success', _t('설정 테이블에 링크스킨 필드를 추가했습니다.')));
					}

					if (!$db->exists("DESC {$prefix}FeedItems `thumbnailId`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}FeedItems ADD `thumbnailId` INT(11) NOT NULL default '0' AFTER `allowRedistribute`");
						array_push($checkups, array('success', _t('피드아이템 테이블에 대표썸네일 필드를 추가했습니다.')));
					}		

	  				if ($db->exists("DESC {$prefix}FeedItems `thumbnail`")) { // ncloud 1.2
						// Thumbnail 이동

						$thumbnails = array();
						$result = $db->queryAll("SELECT id, thumbnail FROM {$prefix}FeedItems");
						foreach($result as $item) {
							if($item['thumbnail']) {
								$db->execute("INSERT INTO {$prefix}Medias (`feeditem`, `thumbnail`) VALUES ({$item['id']}, '{$item['thumbnail']}')");
								array_push($thumbnails, array( 'id'=>$db->insertId(), 'feedid'=>$item['id'] ));
							}
						}
						foreach($thumbnails as $thumbnail) {
							$db->execute("UPDATE {$prefix}FeedItems SET `thumbnailId` = {$thumbnail['id']} WHERE id = '{$thumbnail['feedid']}'");
						}

						array_push($checkups, array('success', _t('썸네일 필드값을 미디어 테이블로 이동했습니다.')));

						$db->execute("ALTER TABLE {$prefix}FeedItems DROP `thumbnail`");	
						array_push($checkups, array('success', _t('기존 썸네일 필드를 삭제했습니다.')));
					}
					

					if (!$db->exists("DESC {$prefix}Feeds `xmlType`")) { // ncloud 1.2
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `xmlType` varchar(10) NOT NULL default '' AFTER `xmlURL`");
						array_push($checkups, array('success', _t('피드 테이블에 XML 종류 필드를 추가했습니다.')));
					}		

					if ($db->doesExistTable("{$prefix}BoomHistory")) { // 1.2 ncloud
						$result = $db->queryAll("SELECT * FROM {$prefix}BoomHistory");
						foreach($result as $item) {
							$userid = $item['userid'];
							if(!empty($item['boomUp'])) {
								$datas = unserialize($item['boomUp']);
								foreach($datas as $data) {
									$db->execute("INSERT INTO `{$prefix}Booms` (userid,feeditem,type,ip,written) VALUES ($userid, $data, 'up', '', 0)");
								}
							}											
							if(!empty($item['boomDown'])) {
								$datas = unserialize($item['boomDown']);
								foreach($datas as $data) {
									$db->execute("INSERT INTO `{$prefix}Booms` (userid,feeditem,type,ip,written) VALUES ($userid, $data, 'down', '', 0)");
								}
							}
						}

						$db->execute("DROP TABLE `{$prefix}BoomHistory`"); // 1.2
						array_push($checkups, array('success', _t('분류히스토리 테이블을 붐테이블로 옮긴후 삭제했습니다.')));
					}

					if ($db->exists("DESC {$prefix}FeedItems `comments`")) { // 1.2
						$db->execute("ALTER TABLE {$prefix}FeedItems DROP `comments`");
						array_push($checkups, array('success', _t('피드 아이템 테이블에 댓글 필드를 삭제했습니다.')));
					}

					if ($db->exists("DESC {$prefix}Settings `collectComments`")) { // 1.2
						$db->execute("ALTER TABLE {$prefix}Settings DROP `collectComments`");
						array_push($checkups, array('success', _t('설정 테이블에 댓글수집여부 필드를 삭제했습니다.')));
					}	

					if ($db->queryCell("DESC {$prefix}FeedItems visibility", 'Type') != "enum('y','d','n')") { // 1.2
						$db->execute("ALTER TABLE {$prefix}FeedItems CHANGE visibility visibility enum('y','d','n') NOT NULL default 'y'");
						array_push($checkups, array('success', _t('피드 아이템 테이블의 보기필드의 옵션을 변경했습니다.')));
					}

					if ($db->exists("DESC {$prefix}Feeds `isVerified`")) { // 1.2
						$db->execute("ALTER TABLE {$prefix}Feeds DROP `isVerified`");
						$db->execute("ALTER TABLE {$prefix}Feeds DROP `verifier`");

						array_push($checkups, array('success', _t('피드 테이블의 인증시스템과 관련된 필드를 삭제했습니다.')));
					}
					
					if (!$db->exists("DESC {$prefix}Feeds `visibility`")) { // 1.2
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `visibility` ENUM('y','n') NOT NULL default 'y' AFTER `created`");

						array_push($checkups, array('success', _t('피드 테이블에 보기옵션 필드를 생성했습니다.')));
					}	
					if (!$db->exists("DESC {$prefix}Feeds `everytimeUpdate`")) { // 1.2
						$db->execute("ALTER TABLE {$prefix}Feeds ADD `everytimeUpdate` enum('y','n') NOT NULL default 'n' AFTER `allowRedistribute`");
						array_push($checkups, array('success', _t('피드 테이블에 매번 업데이트 여부 확인 필드를 생성했습니다.')));
					}	

					if ($db->exists("DESC {$prefix}Settings `useVerifier`")) {
						$db->execute("ALTER TABLE {$prefix}Settings DROP `useVerifier`");					
						$db->execute("ALTER TABLE {$prefix}Settings DROP `verifierPack`");
						
						array_push($checkups, array('success', _t('설정테이블의 인증시스템과 관련된 필드를 삭제했습니다.')));
					}  					
									
					if ($db->doesExistTable("{$prefix}Notice")) { // 1.2 ncloud
						$db->execute("DROP TABLE `{$prefix}Notice"); // 1.2
						$db->execute("DROP TABLE `{$prefix}NoticeFeeds"); // 1.2
									
						array_push($checkups, array('success', _t('공지사항 테이블을 삭제했습니다.')));
					}

					if (!$db->exists("DESC {$prefix}TagRelations `linked`")) { // 1.2
						$db->execute("ALTER TABLE {$prefix}TagRelations ADD `linked` int(11) NOT NULL default '0' AFTER `tag`");


						array_push($checkups, array('success', _t('태그연관 테이블에 태그연결 날짜 필드를 생성했습니다.')));
					}

					  

					if(!file_exists(ROOT.'/remove.lock')) { // 1.2
						$fp = fopen(ROOT.'/remove.lock','w+');
						if($fp) {
							fwrite($fp, 'remove lock');	
							fclose($fp);				
							@chmod(ROOT . '/remove.lock', 0666);
						}												
					}
					$result = '';
					foreach($checkups as $checkup) {
						$result .= '<li class="'.$checkup[0].'">'.$checkup[1].'</li>';
					}

					if(!empty($result)) {
						$result = '<ul>'.$result.'</ul>';
					}

					$fp = fopen(ROOT.'/cache/checkup','w+'); // 1.2
					if($fp) {
						fwrite($fp, $result);	
						fclose($fp);				
						@chmod(ROOT . '/cache/checkup', 0666);
					}						

					// 마이그레이션 완료			
					Header("Location: {$path}/setup/?step=upgrade");
					exit;
				} else {
					// invalid access
					header("Location: {$path}/setup/?step=2");
				}
		break;
		case 'uninstall':
			$step_text = _t('삭제') . ' - ' . _t('세번째 단계') . ' : 3/4';
			$step_desc = _t('블로그라운지를 삭제하기 위한 정보를 입력해주세요.');
			if(!file_exists(ROOT.'/config.php') || file_exists(ROOT.'/remove.lock')) {
				Header("Location: {$path}/setup/?step=3");
				exit;
			}
		break;
		case 'uninstall_do':
				if (!isset($_POST['doit']) || empty($_POST['userid']) || empty($_POST['userpw'])) {
					header("Location: {$path}/setup/?step=uninstall&error=1");
					exit;
				}	

				$step_text = _t('삭제완료');

				include ROOT . '/config.php'; // $database

				if (!isset($database['type'])) $database['type'] = 'mysql';

				$db = DB::start($database['type']);
				if (!($database['alive'] || $db->alive)) {
					header("Location: {$path}/setup/?step=uninstall&error=8");
					exit;
				}

				$IV = array();
				foreach ($_POST as $key=>$value) {
					if (Validator::enum($key, 'userid,userpw'))
						$IV[$key] = $db->escape($value);
				}

				if (!isset($IV['userid']) || !isset($IV['userpw'])) {
					header("Location: {$path}/setup/?step=uninstall&error=10");
					exit;
				}

				if (!list($loginid, $password, $is_admin) = $db->pick("SELECT loginid, password, is_admin FROM {$database['prefix']}Users WHERE loginid='{$IV['userid']}'")) {
					header("Location: {$path}/setup/?step=uninstall&error=11");
					exit;
				}

				if ($password != Encrypt::hmac($IV['userid'], md5(md5($IV['userpw'])))) {
					header("Location: {$path}/setup/?step=uninstall&error=12");
					exit;
				}

				if (!Validator::getBool($is_admin)) {
					header("Location: {$path}/setup/?step=uninstall&error=13");
					exit;
				}

				$db->execute("DROP TABLE 
									{$database['prefix']}Booms,
									{$database['prefix']}Categories,
									{$database['prefix']}DailyStatistics,
									{$database['prefix']}DeleteHistory,
									{$database['prefix']}FeedItems,
									{$database['prefix']}Feeds,
									{$database['prefix']}Medias,
									{$database['prefix']}Sessions,
									{$database['prefix']}SessionsData,
									{$database['prefix']}SessionVisits,
									{$database['prefix']}Settings,
									{$database['prefix']}SkinSettings,
									{$database['prefix']}Plugins,
									{$database['prefix']}Users,
									{$database['prefix']}Tags,
									{$database['prefix']}TagRelations");
			
				requireComponent('LZ.PHP.Functions');

				if (is_dir(ROOT.'/cache'))
					func::rmpath(ROOT.'/cache');
				if (file_exists(ROOT.'/config.php'))
					unlink(ROOT.'/config.php');
				if (file_exists(ROOT.'/remove.lock'))
					unlink(ROOT.'/remove.lock');
		break;
		case 'complete':
			$step_text = _t('설치') . ' - ' . _t('완료');
		break;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGLOUNGE;?> <?php echo $step_text;?></title>
<script type="text/javascript" src="<?php echo $path;?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $path;?>/scripts/setup.js"></script>
<link rel="stylesheet" href="<?php echo $path;?>/style/common.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $path;?>/style/setup.css" type="text/css" />
<?php
	if($step == 'migration') {
?>
<script type="text/javascript">
	$(window).ready( function() {
		$("#admin_id").focus();
	});
</script>
<?php
	}
?>
</head>
<body>
	<div id="container">

		<div id="logo">
			<img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/logo.gif" alt="<?php echo _t('로고');?>" />
		</div>

		<div id="desc">
<?php
	switch($step) {
		case '1': // ** 첫번째 단계
			$bloglounge_homepages = explode(" ", BLOGLOUNGE_HOMEPAGE);
			$bloglounge_homepages_texts = array();

			foreach($bloglounge_homepages as $bloglounge_homepage) {
				array_push($bloglounge_homepages_texts, '<a href="' . $bloglounge_homepage .'" class="_blank">'. $bloglounge_homepage .'</a>');
			}
?>
			<!-- 1단계 설치 시작 -->
			<div id="title">
				<span class="stepNum"><?php echo _f('%1단계', 1);?>:</span>&nbsp;<span class="stepTitle"><?php echo _t('블로그라운지 설치를 시작합니다');?></span>
			</div> <!-- title close -->

			<div id="blogloungeInformationBox">
				<strong><?php echo BLOGLOUNGE;?> <?php echo BLOGLOUNGE_VERSION;?></strong> <span class="name">: <?php echo BLOGLOUNGE_NAME;?></span><br />
				<?php echo BLOGLOUNGE_COPYRIGHT;?><br />
				Homepage : <?php echo implode(', ', $bloglounge_homepages_texts);?>
			</div>
<?php
			if(file_exists(ROOT . '/config.php')) {
?>
				<div id="infoBox">
					<span><?php echo _t('이미 설치되어있는 블로그라운지가 있습니다.');?></span>
				</div>
<?php
			}

				if (file_exists('./license.'.Locale::get().'.php')) {
					include './license.'.Locale::get().'.php';
				} else {
?>
			<ol id="licenseNotice">
				<li><?php echo _t('메타사이트 구축툴인 <strong>블로그라운지</strong>는 다음세대재단의 <비영리단체를 위한 IT지원센터-아이티캐너스>가 라지엘님의 설치형블로그 포털인 "날개툴"의 소스를 수정하여 배포한 툴의 이름입니다. 블로그라운지는 자체 메타 사이트를 구축하고자 하는 비영리단체를 위해서 개발되었지만 누구든지 이용할 수 있습니다.');?></li>
				<li><?php echo _t('소스를 포함한 소프트웨어에 포함된 원저작물(이하 날개툴)의 저작권자는 김지한(라지엘 : <a href="http://www.laziel.com/" target="_blank">http://www.laziel.com/</a>)님입니다.');?>
					<?php echo _t('단, GPL 또는 그에 준하는 라이센스에 의해 날개툴 내에 포함된 외부 소스에 대해서는 각 파일에 표기한 해당 저작권자에 그 권리가 있으며 해당 소스에 대해서는 원본의 라이센스를 따릅니다.');?></li>
				<li><?php echo _t('블로그라운지는 일반 공중 라이센스 (GPL)로 배포되며, 모든 사람이 자유롭게 이용할 수 있습니다.');?></li>
				<li><?php echo _t('프로그램 사용에 대한 유지 및 보수 등의 의무와, 사용 중 데이터 손실 등의 사고에 대한 책임은 사용자에게 있습니다.');?></li>
				<li><?php echo _t('스킨 및 플러그인의 저작권은 해당 제작자에게 있습니다.');?></li>
			</ol>
<?php
			   }
?>

			<p id="setupStepWrap">
				<a href="#" onclick="return false;"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_disable_back.jpg" alt="<?php echo _t('이전');?>" /></a>&nbsp;&nbsp;<a href="<?php echo $path;?>/setup/?step=2"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_next.jpg" alt="<?php echo _t('다음');?>" /></a>
			</p>
			<!-- 1단계 설치 끝 -->
<?php
		break;
		case '2': // ** 두번째 단계
			$removeLockExist = file_exists(ROOT . '/remove.lock');
?>
			<div id="title">
				<span class="stepNum"><?php echo _f('%1단계', 2);?>:</span>&nbsp;<span class="stepTitle"><?php echo _t('작업 유형을 선택해주세요');?></span>
			</div>

			<form action="<?php echo $path;?>/setup/?step=3" method="post">
			<div class="indesc">
				<ul id="setupType">
<?php
				if(!file_exists(ROOT . '/config.php')) {
?>
					<li>
						<input type="radio" name="type" value="install" id="install" checked="checked" />&nbsp;<label for="install"><?php echo _t('블로그라운지를 새롭게 설치합니다');?></label><br />
						<span class="help"><?php echo _t('블로그라운지를 새로 설치하시는 경우 이 항목을 선택해주세요.');?></span>
					</li>
<?php
				} else {
?>					<li>
						<input type="radio" name="type" value="install" id="install" disabled="disabled" />&nbsp;<label for="install"><span class="dontUse"><?php echo _t('블로그라운지를 새롭게 설치합니다');?></span></label><br />
						<span class="help"><?php echo _t('블로그라운지를 새로 설치하시는 경우 설정파일(config.php) 를 삭제하신 후 계속하실 수 있습니다.');?></span>
					</li>
<?php
				}
?>
					<li>
						<input type="radio" name="type" value="migration" id="migration" />&nbsp;<label for="migration"><?php echo _t('이전 버전의 블로그라운지를 업그레이드 합니다');?></label><br />
						<span class="help"><?php echo _t('이전 버전의 블로그라운지 혹은 날개툴을 덮어 씌우신 경우 이 항목을 선택하세요.');?></span>
					</li>
<?php
					if(!$removeLockExist) {
?>
					<li>
						<input type="radio" name="type" value="uninstall" id="uninstall" <?php echo $removeLockExist?'checked="checked"':'';?> />&nbsp;<label for="uninstall"><?php echo _t('설치되어 있는 블로그라운지를 삭제합니다');?></label><br />
						<span class="help"><?php echo _t('현재 설치되어 있는 블로그라운지를 삭제하시려면 이 항목을 선택하세요,');?></span>
					</li>
<?php
					} else {
?>
					<li>
						<input type="radio" name="type" value="uninstall" id="uninstall" disabled="disabled"/>&nbsp;<label for="uninstall"><span class="dontUse"><?php echo _t('설치되어 있는 블로그라운지를 삭제합니다');?></span></label><br />
						<span class="help"><?php echo _t('현재 설치되어 있는 블로그라운지를 삭제하기 위해서는 remove.lock 파일을 삭제하셔야합니다.');?></span>
					</li>
<?php
					}
?>
				</ul>
			</div>

			<p id="setupStepWrap">
				<a href="#" onclick="javascript: history.back();"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_back.jpg" alt="<?php echo _t('이전');?>" /></a>&nbsp;&nbsp;<input type="image" src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_next.jpg" alt="<?php echo _t('다음');?>" />
			</p>

<?php
		break;
		case 'install': // ** 세번째 단계 ( 설치 )			
		case 'migration':
?>
			<div id="title">
				<span class="stepNum"><?php echo _f('%1단계', 3);?>:</span>&nbsp;<span class="stepTitle"><?php echo _t('설치 정보를 입력합니다.');?></span> 
<?php
		if(!empty($step_desc)) {
?>
				<br />
				<div class="stepDesc"><?php echo $step_desc;?></div>
<?php
		}
?>
			</div>

			<ul id="setupCheck">
				<li>
					<h3><?php echo _t('폴더 쓰기 권한을 확인합니다.');?>  &nbsp;
					<?php if ($iswritable = is_writable(ROOT)) { ?>
						<span class="sblue"><?php echo _t('성공');?></span></h3>
					<?php } else { ?>
						<span class="sopera"><?php echo _t('실패');?></span></h3>
						<p>
						<?php echo _t('블로그라운지가 설치된 폴더의 권한(퍼미션)을 777 로 설정해주세요.');?>
						</p>
					<?php  }  ?>
				</li>
<?php
		if ($step == 'migration') {
?>
				<li>
					<h3><?php echo _t('설정 파일을 확인합니다.');?> &nbsp;
					<?php if ($configExists = file_exists(ROOT.'/config.php')) { ?>
						<span class="sblue"><?php echo _t('성공');?></span></h3>
					<?php } else { ?>
						<span class="sopera"><?php echo _t('실패');?></span></h3>
						<p>
						<?php echo _t('여기를 클릭하시면 설정파일을 지금 만들 수 있습니다.');?>
						</p>
					<?php  }  ?>
				</li>
<?php
		}
?>
			</ul>

<!--
			<div class="incheck">
				<?php echo _t('날개툴 스킨과 블로그라운지 스킨은 호환되지 않습니다.');?>
			</div>
-->

<?php
		if($error > 0) {
?>
			<div id="errorBox">
				<?php echo errorPrint($error); ?>
			</div>
<?php
		}
?>

<?php	
		if (!$iswritable) { 
?>
			<form action="<?php echo $path;?>/setup/?step=install" method="post">
			<input type="hidden" name="type" value="<?php echo $step;?>"/>
<?php
		} else if ($step == 'migration') { 
?>
			<form action="<?php echo $path;?>/setup/?step=4" method="post">
			<input type="hidden" name="type" value="<?php echo $step;?>"/>
			<div id="admininfoInputForm" class="userSetting">
				<dl>
					<dt><?php echo _t('관리자 아이디');?></dt>
					<dd>&nbsp;<input type="text" id="admin_id" class="inputText faderInput" name="userid" tabindex="1"  value=""  /></dd>
				</dl>
				<dl>
					<dt><?php echo _t('관리자 비밀번호');?></dt>
					<dd>&nbsp;<input type="password" id="admin_password" class="inputText faderInput" name="userpw" tabindex="2" value=""/></dd>
				</dl>
			</div>
			<div class="alertBox">
				 <?php echo _t('이전에 설치된 블로그라운지 혹은 날개툴을 새 버전으로 업그레이드 합니다.');?><br />
				 <br />
				 <?php echo _t('예기치 않은 오류로 인해 기존 데이터가 손상될 수 있습니다.');?><br />
 				 <?php echo _t('예전 데이터를 미리 백업하신 뒤 업그레이드를 진행해주세요.');?><br />
			</div>
<?php
		} else { // install 
?>
			<form action="<?php echo $path;?>/setup/?step=4" method="post">
			<input type="hidden" name="type" value="<?php echo $step;?>"/>
			<div id="inputForm">
				<h3><?php echo _t('서버 접속 정보를 입력해주세요');?></h3>

				<div id="dbinfoInputForm">
					<input type="hidden" name="dbtype" value="mysql" />
<?php
	/*
?>
					<dl>
						<dt><?php echo _t('데이터베이스 종류');?></dt>
						<dd>&nbsp;
							<select name="dbtype">
							<option value="mysql">MySQL</option>
							</select>
						</dd>
					</dl>
<?php
	*/
?>
					<dl>
						<dt><?php echo _t('데이터베이스 서버');?></dt>
						<dd>&nbsp;<input type="text" class="inputText faderInput" name="dbserver" tabindex="1"  value="<?php echo isset($_GET['dbserver'])?$_GET['dbserver']:'localhost';?>"  /></dd>
					</dl>
					<dl>
						<dt><?php echo _t('데이터베이스 사용자명');?></dt>
						<dd>&nbsp;<input type="text" class="inputText faderInput" name="dbuserid" value="<?php echo isset($_GET['dbuserid'])?$_GET['dbuserid']:'';?>" tabindex="2" onblur="if (document.getElementById('dbname').value == '') { document.getElementById('dbname').value = this.value; }"/></dd>
					</dl>
					<dl>
						<dt><?php echo _t('데이터베이스 비밀번호');?></dt>
						<dd>&nbsp;<input type="password" class="inputText faderInput" name="dbuserpw" value=""  tabindex="3" /></dd>
					</dl>
					<dl>
						<dt><?php echo _t('데이터베이스 이름');?></dt>
						<dd>&nbsp;<input type="text" id="dbname" class="inputText faderInput" name="dbname" value="<?php echo isset($_GET['dbname'])?$_GET['dbname']:'';?>" tabindex="4" /></dd>
					</dl>
					<dl>
						<dt><?php echo _t('테이블 식별자');?></dt>
						<dd>&nbsp;<input type="text" id="prefix"  class="inputText faderInput" name="dbprefix" tabindex="5"  value="<?php echo isset($_GET['dbprefix'])?$_GET['dbprefix']:'bl_';?>"  /></dd>
					</dl>
				</div>

				<div id="admininfoInputForm">
					<h3><?php echo _t('관리자 정보를 입력해주세요.');?></h3>
					<dl>
						<dt><?php echo _t('관리자 아이디');?></dt>
						<dd>&nbsp;<input type="text" id="userid" name="userid" class="inputText faderInput" tabindex="6" value="<?php echo isset($_GET['userid'])?$_GET['userid']:'';?>"/></dd>
					</dl>
					<dl>
						<dt><?php echo _t('관리자 비밀번호');?></dt>
						<dd>&nbsp;<input type="password" id="userpw" name="userpw" class="inputText faderInput" tabindex="7" /></dd>
					</dl>
					<dl>
						<dt><?php echo _t('관리자 이름');?></dt>
						<dd>&nbsp;<input type="text" id="username" name="username" class="inputText faderInput" tabindex="8" value="<?php echo isset($_GET['username'])?$_GET['username']:'';?>" /></dd>
					</dl>
					<dl>
						<dt><?php echo _t('관리자 이메일');?></dt>
						<dd>&nbsp;<input type="text" id="useremail" name="useremail" class="inputText faderInput" tabindex="9" value="<?php echo isset($_GET['useremail'])?$_GET['useremail']:'';?>" /></dd>
					</dl>
				</div>
			</div>
<?php
		} 
?>

			<div class="installButtons">
				<?php if ($iswritable) { ?><?php echo _t('계속하시려면 "다음" 버튼을 눌러주세요.');?> <?php } else { ?><?php echo _t('권한(퍼미션)을 조정하시고 "다음" 버튼을 눌러 다시 시도해주세요.');?><?php } ?><br />
				<a href="#" onclick="javascript: history.back();"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_back.jpg" alt="<?php echo _t('이전');?>" /></a>&nbsp;&nbsp;<input type="image" src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_next.jpg" alt="<?php echo _t('다음');?>" />
			</div>
			</form>
		</div>
<?php
		break;		
		case 'uninstall': // *** 세번째단계 ( 삭제 )
?>
			<div id="title">
				<span class="stepNum"><?php echo _t("블로그라운지 삭제");?></span>
			</div>
<?php
		if($error > 0) {
?>
			<div id="errorBox">
				<?php echo errorPrint($error); ?>
			</div>
<?php
		}
?>
			<form action="<?php echo $path;?>/setup/?step=uninstall_do" method="post">
			<div class="indesc">
				<ul id="setupType">
					<li>
						<input type="checkbox" name="doit" value="ok" id="uninstall" />&nbsp;<label for="uninstall"><?php echo _t("설치되어 있는 블로그라운지를 삭제합니다.");?></label><br />
						<span class="help" style="margin:25px;">수집된 데이터를 포함하여 모두 완전히 사라지게 됩니다.</span>
					</li>
				</ul>

				<div id="admininfoInputForm">
					<h3>관리자 정보를 입력해주세요.</h3>
					<dl>
						<dt>관리자 아이디</dt>
						<dd>&nbsp;<input type="text" id="userid" name="userid" class="inputText faderInput" tabindex="6" /></dd>
					</dl>
					<dl>
						<dt>관리자 비밀번호</dt>
						<dd>&nbsp;<input type="password" id="userpw" name="userpw" class="inputText faderInput" tabindex="7" /></dd>
					</dl>
				</div>
			</div>

			<div class="installButtons">
				<?php echo _t('계속하시려면 "다음" 버튼을 눌러주세요.');?> <br />
				<a href="#" onclick="javascript: history.back();"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_back.jpg" alt="<?php echo _t('이전');?>" /></a>&nbsp;&nbsp;<input type="image" src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_next.jpg" alt="<?php echo _t('다음');?>" />
			</p>
			</form>
<?php
		break;
		case 'complete':
?>
			<div id="title">
				<span class="stepNum"><?php echo _t('설치완료');?></span>
				<div class="stepDesc"><?php echo _t('블로그라운지 설치가 완료되었습니다.');?></div>
			</div>

			<div class="indesc">
				<?php echo _t('아래의 "관리자" 버튼을 눌러 관리자 모드로 이동하거나');?>,<br />
				<?php echo _t('"홈" 버튼을 눌러 첫페이지로 이동할 수 있습니다.');?><br />
			</div>
			<div class="installButtons">
				<a href="<?php echo $path;?>/"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_home.jpg" alt="<?php echo _t('홈');?>" /></a>&nbsp;&nbsp;<a href="<?php echo $path;?>/admin/"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_admin.jpg" alt="<?php echo _t('관리자페이지');?>" /></a>
			</div>
<?php
		break;
		case 'upgrade':
			$checkupDesc = '';
			$fp = fopen(ROOT.'/cache/checkup','r');
			if($fp) {
				while (!feof($fp)) {
				  $checkupDesc .= fread($fp, 8192);
				}
				fclose($fp);				
			}								
?>
			<div id="title">
				<span class="stepNum"><?php echo _t('업그레이드완료');?></span>
				<div class="stepDesc"><?php echo _t('블로그라운지 업그레이드가 완료되었습니다.');?></div>
			</div>
<?php
		if(!empty($checkupDesc)) {
?>
			<div class="updateDesc">
				<div class="title"><?php echo _t('처리결과');?></div>
				<?php echo $checkupDesc;?>
			</div>
<?php
		}
?>
			<div class="indesc">
				<?php echo _t('아래의 "관리자" 버튼을 눌러 관리자 모드로 이동하거나');?>,<br />
				<?php echo _t('"홈" 버튼을 눌러 첫페이지로 이동할 수 있습니다.');?><br />
			</div>
			<div class="installButtons">
				<a href="<?php echo $path;?>/admin/"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_admin.jpg" alt="<?php echo _t('관리자페이지');?>" /></a>&nbsp;&nbsp;<a href="<?php echo $path;?>/"><img src="<?php echo $path;?>/images/setup/<?php echo Locale::get();?>/bt_home.jpg" alt="<?php echo _t('홈');?>" /></a>
			</div>
<?php
		break;
		case 'uninstall_do':
?>
			<div id="title">
				<span class="stepNum"><?php echo _t('삭제완료');?></span>
				<div class="stepDesc"><?php echo _t('블로그라운지 삭제가 완료되었습니다.');?></div>
			</div>

			<div class="indesc">
				<?php echo _t('블로그라운지 삭제가 완료되었습니다.');?><br />
				<?php echo _t('나머지 파일들은 직접 삭제해주세요.');?><br />
			</div>

<?php
		break;
	}
?>
		</div> <!-- desc close -->
	
	</div> <!-- container close -->

</body>
</html>