<?php
	define('ROOT', '../../..');
	include ROOT . '/lib/includeForAdmin.php';

	requireMembership();

	include ROOT. '/lib/piece/adminHeader.php';

	$output = '';

	$menu = $accessInfo['action'];
	if(isset($event->admin[$menu]['page']) && count($event->admin[$menu]['page'])>0) {
		foreach($event->admin[$menu]['page'] as $plugin=>$func) {
			include_once(ROOT . '/plugins/'.$plugin.'/index.php');		

			$event->pluginURL = $service['path'] . '/plugins/'.$plugin;

			if (function_exists($func)) {
				$output = call_user_func($func, $output, Plugin::getConfig($plugin));
			}
		}
	}
?>

<div class="wrap">
	<?php echo $output;?>
</div>

<?php
	include ROOT. '/lib/piece/adminFooter.php';
?>