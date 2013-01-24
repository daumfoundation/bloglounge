<?php
	// Event handler
	// Attention : All functions requires valid instance.
	// Do not use '::' operator like as Event::loadMap()
	// Do not use '::' operator like as Event::loadAdminMap()

	class Event {
		var $cacheDir, $case, $tag, $admin = array();
		var $pluginURL = '';

		function Event() {
			global $accessInfo;
			$this->cacheDir = ROOT . '/cache/events';

			if($accessInfo['controller'] == 'admin') {
				$this->loadAdminMap();	
			}

			$this->loadMap();
		}

		function on($event, $input = null) {
			if (empty($event)) return false;
			if (!isset($this->case) || !is_array($this->case) || !isset($this->case[$event]) || empty($this->case[$event])) return $input;
			if (Validator::enum($event, 'Plugin.on,Plugin.off,Plugin.set') && isset($input['plugin']) && !empty($input['plugin'])) { // Plug.on & off & set 이벤트는 연쇄작용 없음
				$pluginName = $input['plugin'];
				include_once(ROOT . '/plugins/'.$input['plugin'].'/index.php');
				if (function_exists($this->case[$event][$pluginName])) {
					$this->pluginURL = $service['path'] . '/plugins/'.$input['plugin'].'/';
					return call_user_func($this->case[$event][$pluginName], $input, Plugin::getConfig($pluginName));
				}
			} else {
				foreach ($this->case[$event] as $plugin=>$func) {
					include_once(ROOT . '/plugins/'.$plugin.'/index.php');		
					if (function_exists($func)) {
						$this->pluginURL = $service['path'] . '/plugins/'.$plugin;
						$input = call_user_func($func, $input, Plugin::getConfig($plugin));
					}
				}
				return $input;
			}
		}

		function loadMap() {
			requireComponent('LZ.PHP.XMLStruct');
			$xmls = new XMLStruct;
			if (!file_exists($this->cacheDir .'/1.xml.php'))
				$this->createMap();
			if (!$xmls->openFile($this->cacheDir.'/1.xml.php'))
				return false;

			for ($i=1; $event = $xmls->getAttribute("/map/event[$i]", 'name'); $i++) {
				$plugin = $xmls->getAttribute("/map/event[$i]/bind", 'plugin');
				$func = $xmls->getValue("/map/event[$i]/bind");
				if (!isset($this->case[$event])) $this->case[$event] = array();
				$this->case[$event][$plugin] = $func;
			}
			for ($i=1; $event = $xmls->getAttribute("/map/tag[$i]", 'name'); $i++) {
				$plugin = $xmls->getAttribute("/map/tag[$i]/bind", 'plugin');
				$func = $xmls->getValue("/map/tag[$i]/bind");
				if (!isset($this->tag[$event])) $this->tag[$event] = array();
				$this->tag[$event][$plugin] = $func;
			}
			// 예: Auth.login 에 bind 하는 플러그인이 linker_tt 의 tt_login, linker_zb4 의 zb_login 이라고 한다면
			// $this->case['Auth.login'] = array('linker_tt' => 'tt_login', 'linker_zb4' => 'zb_login');
			return true;
		}

		function loadAdminMap() {
			requireComponent('LZ.PHP.XMLStruct');
			$xmls = new XMLStruct;
			if (!file_exists($this->cacheDir .'/admin.xml.php'))
				$this->createMap();
			if (!$xmls->openFile($this->cacheDir.'/admin.xml.php'))
				return false;
			
			$nodes = $xmls->selectNodes('/map/event');

			if(count($nodes) > 0) {
				foreach ($nodes as $admin) {
					$name = $admin['.attributes']['name'];
					foreach($admin['bind'] as $blind) {
						
						$func = $blind['.value'];
						$event = $blind['.attributes']['event'];
						$plugin = $blind['.attributes']['plugin'];

						if (!isset($this->admin[$name])) $this->admin[$name] = array();
						if (!isset($this->admin[$name][$event])) $this->admin[$name][$event] = array();

						$this->admin[$name][$event][$plugin] = $func;
					}				
				}
			}

			return true;
		}

		function createMap() {
			global $database, $db;

			func::mkpath($this->cacheDir);
			if (!is_dir($this->cacheDir) || !is_writable($this->cacheDir))
				return false;

			requireComponent('LZ.PHP.XMLStruct');
			requireComponent('LZ.PHP.XMLWriter');

			$case = array();
			$tags = array();
			$admins = array();

			$xmls = new XMLStruct;

			$db->query("SELECT name FROM {$database['prefix']}Plugins WHERE status='on' ORDER BY name ASC"); // 활성화 된 플러그인 목록
			while ($data = $db->fetch()) {
				if (!$xmls->openFile(ROOT . '/plugins/'. $data->name . '/index.xml')) continue;
				for ($i=1; $func=$xmls->getValue("/plugin/binding/listener[$i]"); $i++) {
					$event = $xmls->getAttribute("/plugin/binding/listener[$i]", 'event');
					if (!isset($case[$event])) $case[$event] = array();
					array_push($case[$event], array("plugin"=>$data->name, "listener"=>$func));
				}

				for ($i=1; $func=$xmls->getValue("/plugin/binding/tag[$i]"); $i++) {
					$event = $xmls->getAttribute("/plugin/binding/tag[$i]", 'name');
					if (!isset($tags[$event])) $tags[$event] = array();
					array_push($tags[$event], array("plugin"=>$data->name, "listener"=>$func));
				}		
			
				if ($xmls->doesExist('/plugin/binding/admin')) {
					foreach ($xmls->selectNodes('/plugin/binding/admin') as $admin) {
						$menu = isset($admin['.attributes']['menu'])?$admin['.attributes']['menu']:'plugin';
						if (!isset($admins[$menu])) $admins[$menu] = array();
						$textFunc = isset($admin['text'][0]['.value'])?$admin['text'][0]['.value']:'';
						$pageFunc = isset($admin['page'][0]['.value'])?$admin['page'][0]['.value']:'';

						array_push($admins[$menu], array("plugin"=>$data->name, "text"=>$textFunc,"page"=>$pageFunc));
					}
				}
			}

			// bloglounge 

			$xml = new XMLFile($this->cacheDir.'/1.xml.php');
			$xml->startGroup('map');
			foreach ($case as $event=>$binders) {
				$xml->startGroup('event', array('name'=>$event));
				foreach ($binders as $bind) {
					$xml->write('bind', $bind['listener'], false, array('plugin'=>$bind['plugin']));
				}
				$xml->endGroup();
			}
			foreach ($tags as $event=>$binders) {
				$xml->startGroup('tag', array('name'=>$event));
				foreach ($binders as $bind) {
					$xml->write('bind', $bind['listener'], false, array('plugin'=>$bind['plugin']));
				}
				$xml->endGroup();
			}
			
			$xml->endAllGroups();
			$xml->close();	
			
			// admin

			$xml = new XMLFile($this->cacheDir.'/admin.xml.php');
			$xml->startGroup('map');
			foreach ($admins as $event=>$binders) {
				$xml->startGroup('event', array('name'=>$event));
				foreach ($binders as $bind) {
					$xml->write('bind', $bind['text'], false, array( 'plugin'=>$bind['plugin'],'event'=>'text'));					
					$xml->write('bind', $bind['page'], false, array('plugin'=>$bind['plugin'],'event'=>'page'));

				}
				$xml->endGroup();
			}
			
			// admin

			$xml->endAllGroups();
			$xml->close();


			return true;
		}

		function handleTags() {
			global $skin, $service;
			if(count($this->tag)>0) {
				foreach ($this->tag as $name=>$tag) {
					foreach($tag as $plugin=>$func) {
						include_once(ROOT . '/plugins/'.$plugin.'/index.php');
						if (function_exists($func)) {	
							$this->pluginURL = $service['path'] . '/plugins/'.$plugin;
							$input = call_user_func($func, null, Plugin::getConfig($plugin));
							$skin->replace($name, $input);
						}
					}
				}
			}
		}
	}

	// Plugin handler

	class Plugin {
		function activate($pluginName, $ting = null) {
			global $database, $db, $event;
			if (empty($pluginName)) 
				return false;

			$configxml = $db->escape(Plugin::getDefaultConfig($pluginName));
			if (($db->exists("SELECT name FROM {$database['prefix']}Plugins WHERE name='{$pluginName}'")) ? 
								$db->execute("UPDATE {$database['prefix']}Plugins SET status='on' WHERE name='{$pluginName}'") :
								$db->execute("INSERT INTO {$database['prefix']}Plugins (name, settings, status) VALUES ('{$pluginName}', '{$configxml}', 'on')")) {
				$event->createMap();		

				Plugin::createPluginTable($pluginName);

				$event->on('Plugin.on', array('plugin'=>$pluginName, 'ting'=>$ting)); // Plug.on & off 이벤트는 input 에서 어느 플러그인으로부터 발생한건지 input 에 지정해줘야 한다
				return true;
			}
			return false;
		}

		function deactivate($pluginName, $ting = null) {
			global $database, $db, $event;
			if (empty($pluginName))
				return false;

			$configxml = $db->escape(Plugin::getDefaultConfig($pluginName));
			if ($result = ($db->exists("SELECT name FROM {$database['prefix']}Plugins WHERE name='{$pluginName}'")) ? 
								$db->execute("UPDATE {$database['prefix']}Plugins SET status='off' WHERE name='{$pluginName}'") :
								$db->execute("INSERT INTO {$database['prefix']}Plugins (name, settings, status) VALUES ('{$pluginName}', '{$configxml}', 'off')")) {
				$event->on('Plugin.off', array('plugin'=>$pluginName, 'ting'=>$ting)); // Plug.on & off 이벤트는 input 에서 어느 플러그인으로부터 발생한건지 input 에 지정해줘야 한다
				$event->createMap(); // map 은 켜져있는 것만 대상으로 만드므로, 이벤트 호출을 먼저 하고나서 이벤트맵을 갱신해야만 한다
				return true;
			}
			return false;
		}

		function getDefaultConfig($pluginName) {
			requireComponent('LZ.PHP.XMLStruct');
			$xmls = new XMLStruct;
			if (!$xmls->openFile(ROOT . '/plugins/'. $pluginName .'/index.xml'))
				return '';
			
			$config = $xmls->selectNode("/plugin/config[lang()]");
			if(!isset($config['fieldset'])) return '';

			$configSet = array();
			foreach ($config['fieldset'] as $fieldset) {
				foreach ($fieldset['field'] as $field) {
					$name = $field['.attributes']['name'];
					$value = (isset($field['.attributes']['value'])) ? $field['.attributes']['value'] : '';
					$type = $field['.attributes']['type'];
					$cdata = ($type == 'textarea') ? true : false;
					
					if($type=='checkbox') {
						
						$value = (isset($field['.attributes']['checked'])&&($field['.attributes']['checked']=='true')) ? true : false;
	
					}
					
					array_push($configSet, array('name'=>$name, 'type'=>$type, 'value'=>$value, 'isCDATA'=>$cdata));
				}
			}

			requireComponent('LZ.PHP.XMLWriter');
			$xml = new XMLFile('stdout');
			$xml->startGroup('config', array('version'=>BLOGLOUNGE_VERSION));
			foreach ($configSet as $field) {
				$xml->write('field', $field['value'], $field['isCDATA'], array('name'=>$field['name'], 'type'=>$field['type']));
			}
			$xml->endGroup();
			ob_start();
			$xml->close();
			$configxml = ob_get_clean();

			return $configxml;
		}

		function getConfig($pluginName, $field = null) {
			global $database, $db;
			if (empty($pluginName))
				return false;

			if ($settings = $db->queryCell("SELECT settings FROM {$database['prefix']}Plugins WHERE name='{$pluginName}'")) {
				requireComponent('LZ.PHP.XMLStruct');
				$xmls = new XMLStruct;
				if (!$xmls->open($settings))
					return false;

				$resultConfig = array();
				
				$configs = $xmls->selectNode("/config");

				foreach ($configs as $config) {
					if(!empty($config)) {
						foreach ($config as $field) {
							if(is_array($field)) {
								$name = isset($field['.attributes']['name'])?$field['.attributes']['name']:'';
								$value= $field['.value']=='true'?true:($field['.value']=='false'?false:$field['.value']);						
								if (!empty($name)) {
									$resultConfig[$name] = $value;
								}
							}
						}
					}
				
				}
				return $resultConfig;
			}
			return null;
		}

		function setConfig($pluginName, $fields) {
			global $database, $db, $event;
			if (empty($pluginName))
				return false;

			requireComponent('LZ.PHP.XMLWriter');
			$xml = new XMLFile('stdout');
			$xml->startGroup('config', array('version'=>BLOGLOUNGE_VERSION));
			foreach ($fields as $field) {
				$xml->write('field', $field['value'], $field['isCDATA'], array('name'=>$field['name'], 'type'=>$field['type']));
			}
			$xml->endGroup();
			ob_start();
			$xml->close();
			$configxml = ob_get_clean();
			$configxml = $db->escape($configxml);

			if ($result = ($db->exists("SELECT name FROM {$database['prefix']}Plugins WHERE name='{$pluginName}'")) ? 
								$db->execute("UPDATE {$database['prefix']}Plugins SET settings='{$configxml}' WHERE name='{$pluginName}'") :
								$db->execute("INSERT INTO {$database['prefix']}Plugins (name, settings) VALUES ('{$pluginName}', '{$configxml}')")) {
				$event->createMap();
				$event->on('Plugin.set', $fields);
				return true;
			}
			return false;
		}

		function createPluginTable($pluginName) {		
			requireComponent('LZ.PHP.XMLStruct');
			$xmls = new XMLStruct;
			if (!$xmls->openFile(ROOT . '/plugins/'. $pluginName .'/index.xml'))
				return '';

			if ($xmls->doesExist('/plugin/storage')) {

				foreach ($xmls->selectNodes('/plugin/storage/table') as $table) {
					$storageMappings = array();
					$storageKeymappings = array();					 
					if(empty($table['name'][0]['.value'])) continue;
					$tableName = htmlspecialchars($table['name'][0]['.value']);
					if (!empty($table['fields'][0]['field'])) {
						foreach($table['fields'][0]['field'] as $field) 
						{
							if (!isset($field['name']))
								continue; // Error? maybe loading fail, so skipping is needed.
							$fieldName = $field['name'][0]['.value'];
						
							if (!isset($field['attribute']))
								continue; // Error? maybe loading fail, so skipping is needed.
							$fieldAttribute = $field['attribute'][0]['.value'];
						
							$fieldLength = isset($field['length']) ? $field['length'][0]['.value'] : -1;
							$fieldIsNull = isset($field['isnull']) ? $field['isnull'][0]['.value'] : 1;
							$fieldDefault = isset($field['default']) ? $field['default'][0]['.value'] : null;
							$fieldAutoIncrement = isset($field['autoincrement']) ? $field['autoincrement'][0]['.value'] : 0;
						
							array_push($storageMappings, array('name' => $fieldName, 'attribute' => $fieldAttribute, 'length' => $fieldLength, 'isnull' => $fieldIsNull, 'default' => $fieldDefault, 'autoincrement' => $fieldAutoIncrement));
						}
					}
					if (!empty($table['key'][0]['.value'])) {
						foreach($table['key'] as $key) {
							array_push($storageKeymappings, $key['.value']);
						}
					}
					
					plugin::treatPluginTable($pluginName, $tableName,$storageMappings,$storageKeymappings, $version);
					
					unset($tableName);
					unset($storageMappings);
					unset($storageKeymappings);
				}
			}

		}

		function treatPluginTable($pluginName, $tableName, $fields, $keys, $version) {
			global $database, $db;
			if($db->doesExistTable($database['prefix'] . $tableName)) {	
				requireComponent('Bloglounge.Data.ServiceSettings');

				$keyname = 'Database_' . $tableName;
				$value = $pluginName;		
		
				$result = ServiceSettings::get($keyname);
				if (is_null($result)) {
					$keyname = $db->escape($db->lessen($keyname, 32));
					$value = $db->escape($db->lessen($pluginName . '/' . $version , 255));
					$db->execute("INSERT INTO {$database['prefix']}ServiceSettings SET name='$keyname', value ='$value'");
				} else {
					$keyname = $db->escape($db->lessen($keyname, 32));
					$value = $db->escape($db->lessen($pluginName . '/' . $version , 255));
					$values = explode('/', $result, 2);
					if (strcmp($pluginName, $values[0]) != 0) { // diff plugin
						return false; // nothing can be done
					} else if (strcmp($version, $values[1]) != 0) {
						$db->execute("UPDATE {$database['prefix']}ServiceSettings SET value ='$value' WHERE name='$keyname'");
						$eventName = 'UpdateDB_' . $tableName;
					}
				}
				return true;
			} else {
				$query = "CREATE TABLE {$database['prefix']}{$tableName} (";
				$isaiExists = false;
				$index = '';
				foreach($fields as $field) {
					$ai = '';
					if( strtolower($field['attribute']) == 'int' || strtolower($field['attribute']) == 'mediumint'  ) {
						if($field['autoincrement'] == 1 && !$isaiExists) {
							$ai = ' AUTO_INCREMENT ';
							$isaiExists = true;
							if(!in_array($field['name'], $keys))
								$index = ", KEY({$field['name']})";
						}
					}
					$isNull = ($field['isnull'] == 0) ? ' NOT NULL ' : ' NULL ';
					$defaultValue = is_null($field['default']) ? '' : " DEFAULT '" . $db->escape($field['default']) . "' ";
					$fieldLength = ($field['length'] >= 0) ? "(".$field['length'].")" : '';
					$sentence = $field['name'] . " " . $field['attribute'] . $fieldLength . $isNull . $defaultValue . $ai . ",";
					$query .= $sentence;
				}
				
				$query .= " PRIMARY KEY (" . implode(',',$keys) . ")";
				$query .= $index;
				$query .= ") TYPE=MyISAM ";
				$query .= ($database['utf8'] == true) ? 'DEFAULT CHARSET=utf8' : '';
				if ($db->execute($query)) {
						$keyname = $db->escape($db->lessen('Database_' . $tableName, 32));
						$value = $db->escape($db->lessen($pluginName . '/' . $version , 255));
						$db->execute("INSERT INTO {$database['prefix']}ServiceSettings SET name='$keyname', value ='$value'");
					return true;
				}
				else return false;
				
			}
			return true;
		}

		function clearPluginTable($tableName) {
			global $database;
			$tableName = $db->escape($tableName);
			DBQuery::query("DELETE FROM {$database['prefix']}{$tableName}");
			return (mysql_affected_rows() == 1);
		}

		function deletePluginTable($tableName) {
			global $database;
			$tableName = $db->escape($tableName);
			DBQuery::query("DROP {$database['prefix']}{$tableName}");
			return true;
		}
	}
?>
