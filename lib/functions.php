<?php
	// from CackePHP
	function debug($var = false, $showHtml = false, $showFrom = true) {
		if ($showFrom) {
			$calledFrom = debug_backtrace();
			print "<strong>".substr(str_replace(ROOT, "", $calledFrom[0]['file']), 1)."</strong> (line <strong>".$calledFrom[0]['line']."</strong>)";
		}
		print "\n<pre class=\"cake-debug\">\n";
		$var = print_r($var, true);

		if ($showHtml) {
			$var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
		}
		print "{$var}\n</pre>\n";
	}
?>