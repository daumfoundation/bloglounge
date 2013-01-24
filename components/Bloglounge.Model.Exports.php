<?php
	class Export {
		var $cacheDir = '';
		var $case = array();
		var $exportURL = '';

		function Export() {
			$this->cacheDir = ROOT . '/cache/events';

			$this->loadMap();
		}
		
		function loadMap() {
			requireComponent('LZ.PHP.XMLStruct');
			$xmls = new XMLStruct;
			if (!file_exists($this->cacheDir .'/export_1.xml.php'))
				$this->createMap();
			if (!$xmls->openFile($this->cacheDir.'/export_1.xml.php'))
				return false;

			for ($i=1; $event = $xmls->getAttribute("/map/event[$i]", 'domain'); $i++) {
				$action = $xmls->getAttribute("/map/event[$i]/bind", 'action');
				$func = $xmls->getValue("/map/event[$i]/bind");
				if (!isset($this->case[$event])) { 
					$this->case[$event] = array();
					$this->case[$event]['program'] = $xmls->getAttribute("/map/event[$i]", 'program');
					$this->case[$event]['events'] = array();
				}
				$this->case[$event]['events'][$action] = $func;
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
			$program = array();

			$xmls = new XMLStruct;

			$db->query("SELECT domain, program FROM {$database['prefix']}Exports WHERE status='on' ORDER BY id ASC"); // 활성화 된 플러그인 목록
			while ($data = $db->fetch()) {
				if (!$xmls->openFile(ROOT . '/exports/'. $data->program . '/index.xml')) continue;
				for ($i=1; $func=$xmls->getValue("/export/binding/listener[$i]"); $i++) {
					$action = $xmls->getAttribute("/export/binding/listener[$i]", 'action');
					if (!isset($case[$data->domain])) $case[$data->domain] = array();
					if (!isset($program[$data->domain])) $program[$data->domain] = $data->program;
					array_push($case[$data->domain], array("program"=>$data->program, "action"=> $action, "listener"=>$func));
				}
			}

			// bloglounge 

			$xml = new XMLFile($this->cacheDir.'/export_1.xml.php');
			$xml->startGroup('map');
			foreach ($case as $domain=>$binders) {
				$xml->startGroup('event', array('domain'=>$domain, 'program'=>$program[$domain]));
				foreach ($binders as $bind) {
					$xml->write('bind', $bind['listener'], false, array('action'=>$bind['action']));
				}
				$xml->endGroup();
			}
			
			$xml->endAllGroups();
			$xml->close();	

			return true;
		}

		function add($domainName, $programName) {
			global $database, $db;

			if (empty($domainName) || empty($programName))
				return false;
			
			$domainName = $db->escape($domainName);
			$programName = $db->escape($programName);

			if ($db->exists("SELECT domain FROM {$database['prefix']}Exports WHERE domain='{$domainName}'")) {
				return -1;
			}

			if (!file_exists(ROOT . '/exports/'. $programName .'/index.xml'))
				return -2;

			if($db->execute("INSERT INTO {$database['prefix']}Exports (domain, program, status) VALUES ('{$domainName}', '{$programName}','on')")) {
				
				$this->createMap();

				return true;
			}
			return false;
		}

		function get($domainName, $field = '*') {
			global $database, $db;

			if (empty($domainName)) 
				return false;

			$result = $db->queryCell('SELECT '.$field.' FROM '.$database['prefix'].'Exports WHERE domain="'.$db->escape($domainName).'"');
			return $result;
		}

		function getProgramNameByDomain($domainName) {
			global $database, $db;

			if (empty($domainName)) 
				return false;

			$result = $db->queryCell('SELECT program FROM '.$database['prefix'].'Exports WHERE domain="'.$db->escape($domainName).'"');
			return $result;
		}

		function getAll($domainName) {
			global $database, $db;
			$db->query('SELECT * FROM '.$database['prefix'].'Exports WHERE domain="'.$db->escape($domainName).'"');
			return $db->fetchArray();
		}			

		function getList($count=-1) {
			global $db, $database;
			if($count!=-1) {
				$count = ' LIMIT ' . $count;
			} else {
				$count = '';
			}
			return $db->queryAll('SELECT * FROM '.$database['prefix'].'Exports ORDER BY id ASC'. $count);
		}

		function delete($domainName) {
			global $database, $db;

			if (empty($domainName))
				return false;		

			$result = $db->execute('DELETE FROM '.$database['prefix'].'Exports WHERE domain ="'.$db->escape($domainName).'"');			
			$this->createMap();

			return $result;
		}

		function updateCount($domainName) {
			global $database, $db;

			if (empty($domainName)) 
				return false;
			return $db->execute('UPDATE '.$field.' '.$database['prefix'].'Exports SET count = count + 1 WHERE domain="'.$db->escape($domainName).'"');
		}

		function getDefaultConfig($programName) {
			requireComponent('LZ.PHP.XMLStruct');
			$xmls = new XMLStruct;
			if (!$xmls->openFile(ROOT . '/exports/'. $programName .'/index.xml'))
				return '';
			
			$config = $xmls->selectNode("/export/config[lang()]");
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

		function getConfig($domainName, $field = null) {
			global $database, $db;

			if (empty($domainName))
				return false;

			if ($settings = $db->queryCell('SELECT settings FROM '.$database['prefix'].'Exports WHERE domain="'.$db->escape($domainName).'"')) {
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

		function setConfig($domainName, $fields) {
			global $database, $db, $event;
			if (empty($domainName))
				return false;

			$domainName = $db->escape($domainName);

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

			if ($result = ($db->exists("SELECT domain FROM {$database['prefix']}Exports WHERE domain='{$domainName}'")) ? 
								$db->execute("UPDATE {$database['prefix']}Exports SET settings='{$configxml}' WHERE domain='{$domainName}'") :
								$db->execute("INSERT INTO {$database['prefix']}Exports (domain, settings) VALUES ('{$domainName}', '{$configxml}')")) {
				$event->createMap();
				$event->on('Export.set', $fields);
				return true;
			}
			return false;
		}
	}
?>