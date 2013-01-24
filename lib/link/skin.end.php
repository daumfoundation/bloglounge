<?php	
	$event->handleTags();

	$skin->output = str_replace('</body>', func::printFootHTML(), $skin->output);
	$skin->output = str_replace('</body>', $event->on('Disp.linker.foot')."\n</body>\n", $skin->output);
	$skin->clearScopes();
	$skin->clearSkinTags();  // ncloud

	$skin->flush();
	flush();	
?>