<?php
	if(!file_exists('./.htaccess')) { // htaccess 
		$content = "<IfModule mod_url.c>\n
	CheckURL Off\n
</IfModule>\n
<IfModule mod_rewrite.c>\n
   RewriteEngine on\n 
   RewriteCond %{REQUEST_FILENAME} !-d\n
   RewriteCond %{REQUEST_FILENAME} !-f\n
   RewriteRule    (.*) rewrite.php [L]\n
</IfModule>";
		$fp = fopen('./.htaccess','w+');
		fwrite($fp, $content);
		fclose($fp);

		@chmod(ROOT . '/.htaccess', 0666);
		header("Location: /");
	}
	include './rewrite.php';
?>