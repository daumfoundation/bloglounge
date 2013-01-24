<?php

	// LZ-API.freedom (http://api.laziel.com/)

	class Freedom {
		function getCommentsCount($generator, $url) {
			requireComponent('Eolin.PHP.XMLRPC');
			$rpc = new XMLRPC();
			$rpc->url = 'http://api.laziel.com/freedom/rpc/';
			if (!$rpc->call('getCommentCount', $this->detectType($generator), $url))
				return false;

			if ($rpc->fault || !Validator::is_digit($rpc->result))
				return false;

			return $rpc->result; // always integer
		}

		function getTags($item) {
			requireComponent('Eolin.PHP.XMLRPC');
			$rpc = new XMLRPC();
			$rpc->url = 'http://api.laziel.com/freedom/rpc/';
			if (!$rpc->call('getTags', $this->detectType($item['generator']), $item['permalink']))
				return $item['tags'];

			if ($rpc->fault || !is_array($rpc->result))
				return $item['tags'];
			
			return $rpc->result; // always array
		}

		function detectType($generator) {
			if (!isset($generator)) 
				return 'Unknown';

			$generator = strtolower($generator);
			if (strpos($generator, 'naver') !== false) return 'naver';
			else if (strpos($generator, 'egloos') !== false) return 'egloos';
			else if (strpos($generator, 'tistory') !== false) return 'tistory';
			else if (preg_match("/(tattertools|textcube)/i",$generator)) return 'tt';
			else if (strpos($generator, 'tvpot') !== false) return 'daumtvpot';
			else if (strpos($generator, 'daum') !== false) return 'daum';
			else if (strpos($generator, 'wordpress') !== false) return 'wp';

			return 'Unknown';
		}
	}
?>
