<?php	
	if (!isset($service['path']) || empty($service['path'])) $service['path'] = '';
	if (!isset($service['timeout']) || empty($service['timeout'])) $service['timeout'] = 3600;
	if (!isset($database['type'])) $database['type'] = 'mysql'; 
	
	function requireComponent($name) {
		include_once(ROOT . '/components/'.$name.'.php');
	}

	requireComponent('Eolin.PHP.UnifiedEnvironment'); 
	requireComponent('LZ.PHP.Core');
	requireComponent('LZ.DB.Core');
	$db = DB::start($database['type']);

	requireComponent('LZ.PHP.Locale');
	requireComponent('LZ.PHP.XMLStruct');
	requireComponent('LZ.PHP.Feeder');
	requireComponent('LZ.PHP.Functions');

	requireComponent('Bloglounge.Data.Settings');
	requireComponent('Bloglounge.Data.Category'); // ncloud
	requireComponent('Bloglounge.Model.Event');
	$event = new Event;

	Locale::setDirectory(ROOT . '/language/locale');
	Locale::set('ko'); // temporarily fix to korean
?>
