<?php
	function getNDBlogThumbnails($input, $config) {
		global $database, $db, $skin, $event, $service, $accessInfo;

		list($item, $feedId, $itemId, $isSaveThumbnail) = $input;
		$pluginURL = $event->pluginURL;

		requireComponent('LZ.PHP.HTTPRequest');

		$request = new HTTPRequest();

		$blogService = '';

		if(substr($item['permalink'],0,strlen('http://blog.naver.com/')) ==  'http://blog.naver.com/') {
			$blogService = 'naver';
		} else if(substr($item['permalink'],0,strlen('http://blog.daum.net/')) ==  'http://blog.daum.net/') {
			$blogService = 'daum';
		}

		switch($blogService) {
			case 'naver':
				$input = explode('/', substr($item['permalink'], strlen('http://blog.naver.com/')));
				$url = 'http://blog.naver.com/PostView.nhn?blogId=' . $input[0] . '&logNo=' . $input[1];				
				$page = $request->getPage($url);

				// 본문영역만 읽어 오기
				$page = substr($page, strpos($page,'<div id="post-view"'), strpos($page,'<div class="post_footer_contents"'));

				$item['description'] = $page;
				FeedItem::cacheThumbnail($itemId, $item);
			break;
			case 'daum':
				if(!$isSaveThumbnail) {
					$input = explode('/', substr($item['permalink'], strlen('http://blog.naver.com/')));
					$url = $item['permalink'];
					$page = $request->getPage($url);

					if(preg_match('/blogid\=(.*?)\&/', $page, $matche)) {
						$blogId = $matche[1];
						$url = 'http://blog.daum.net/_blog/hdn/ArticleContentsView.do?blogid=' . $blogId . '&articleno=' . $input[1] . '&looping=0&longOpen=';
						$page = $request->getPage($url);
						
						$item['description'] = $page;
						FeedItem::cacheThumbnail($itemId, $item);
					}
				}
			break;
		}
		return $input;
	}	
?>