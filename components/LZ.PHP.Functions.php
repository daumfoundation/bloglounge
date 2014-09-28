<?php
	Class func {
		function printRespond($result) {
				header('Content-Type: text/xml; charset=utf-8');
				$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
				$xml .= "<response>\n";
				foreach ($result as $key => $value) {
					if(is_null($value))
							continue;
					$xml .= "<$key><![CDATA[".str_replace(']]>', ']]&gt;', $value)."]]></$key>\n";
				}
				$xml .= "</response>\n";
				die($xml);
		}

		function generatePassword(){
				return strtolower(substr(base64_encode(rand(0x10000000,0x70000000)),3,8));
		}

		function ext2mime($filename)  { 
			if (!isset($filename)) {
				return;
			}

			// same as getExt()
			$filename = trim(basename($filename));
			$right = strrchr($filename, '.'); 
			$ext = strtolower(substr($right,1));

			switch($ext){
				case 'jpg':
				case 'jpeg':
					return 'image/jpeg';
					break;
				case 'gif':
					return 'image/gif';
					break;
				case 'png':
					return 'image/png';
					break;
				case 'bmp':
					return 'image/x-windows-bmp';
					break;
				case 'txt':
					return 'text/plain';
					break;
				case 'xml':
					return 'text/xml';
					break;
				case 'html':
				case 'htm':
				case 'xhtml':
					return 'text/html';
					break;
				case 'css':
					return 'text/css';
					break;
				case 'tif':
				case 'tiff':
					return 'image/tiff';
					break;
				case 'avi':
				case 'wmv':
					return 'video/x-msvideo';
					break;
				case 'wma':
					return 'audio/x-msaudio';
					break;
				case 'ra':
					return 'audio/x-realaudio';
					break;
				case 'wav':
					return 'audio/x-msaudio';
					break;
				case 'mpeg':
				case 'mpg':
				case 'mpe':
					return 'video/mpeg';
					break;
				case 'qt':
				case 'mov':
					return 'video/quicktime';
					break;
				case 'pdf':
					return 'application/pdf';
					break;
				case 'bz2':
					return 'application/x-bzip2';
					break;
				case 'gz':
				case 'tgz':
					return 'applilcation/x-gzip';
					break;
				case 'tar':
					return 'application/x-tar';
					break;
				case 'zip':
					return 'application/zip';
					break;
				case 'rar':
					return 'application/x-rar-compressed';
					break;
				case '7z':
					return 'application/x-7z-compressed';
					break;
			}
			return 'application/octet-stream';
		}

		function getExt($filename) {
			$filename = trim(basename($filename));
			$right = strrchr($filename, '.'); 
			$ext = strtolower(substr($right,1));

			return $ext;
		}

		function stripHTML($text, $allowTags = '') {
			/*	$text = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/si', '', $text);
				if(count($allowTags) == 0)
						$text = preg_replace('/<[\w\/!]+[^>]*>/', '', $text);
				else {
						preg_match_all('/<\/?([\w!]+)[^>]*?>/s', $text, $matches);
						for($i=0; $i<count($matches[0]); $i++) {
								if (!in_array(strtolower($matches[1][$i]), $allowTags))
										$text = str_replace($matches[0][$i], '', $text);
						}
				}
				$text = preg_replace('/&nbsp;?|\xc2\xa0\x20/', ' ', $text);
				$text = trim(preg_replace('/\s+/', ' ', $text));
				if(!empty($text))
						$text = str_replace(array('&#39;', '&apos;', '&quot;'), array('\'', '\'', '"'), $text);
				return $text;*/

				// 특정 서버에서 제대로 삭제되지 않아 함수 변경 2009-04-07

				return strip_tags($text,$allowTags);
		}

		function escapeJSInAttribute($str) {
				return htmlspecialchars(str_replace(array('\\', '\r', '\n', '\''), array('\\\\', '\\r', '\\n', '\\\''), $str));
		}
		 
		function escapeJSInCData($str) {
				return preg_replace(array('/</', '/>/', '/\r*\n|\r/'), array('\x3C', '\x3E', '\\\\$0'), addslashes($str));
		}
		 
		function escapeCData($str) {
				return str_replace(']]>', ']]&gt;', $str);
		}

		function filterJavascript($str, $removeScript = true) {
				if ($removeScript) {
						preg_match_all('/<.*?>/s', $str, $matches);
						foreach ($matches[0] as $tag) {
								$strippedTag = $tag;
								preg_match_all('/\s+on\w+?\s*?=\s*?("|\').*?\1/s', $strippedTag, $subMatches);
								foreach ($subMatches[0] as $attribute)
										$strippedTag = str_replace($attribute, '', $strippedTag);
								preg_match_all('/\s+on\w+?\s*?=\s*?[^\s>]*/s', $tag, $subMatches);
								foreach ($subMatches[0] as $attribute)
										$strippedTag = str_replace($attribute, '', $strippedTag);
								$str = str_replace($tag, $strippedTag, $str);
						}
						$str = preg_replace('/&#x0*([9ad]);?/ie', "chr(hexdec('\\1'))", $str);
						$patterns = array(
								'/<\/?iframe.*?>/si',
								'/<script.*?<\/script>/si',
								'/<object.*?type=["\']?text\/x-scriptlet["\']?.*?>(.*?<\/object>)?/si',
								'/j\s*?a\s*?v\s*?a\s*?s\s*?c\s*?r\s*?i\s*?p\s*?t\s*?:/si'
						);
						$str = preg_replace($patterns, '', $str);
				} else
						$str = str_replace('<script', '<script defer="defer"', $str);
				return $str;
		}

		function printError($str, $type = 'normal', $button = 'logout') {
			global $service;
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo BLOGLOUNGE;?> :: <?php echo $str;?></title>
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/login.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $service['path'];?>/style/box.css" type="text/css" />

<script type="text/javascript">var _path = '<?php echo $service['path'];?>';</script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/jquery.js"></script>
<script type="text/javascript" src="<?php echo $service['path'];?>/scripts/login.js"></script>
</head>

<body>
	<div id="container">
		<div class="box">
			<div class="box_l"><div class="box_r"><div class="box_t"><div class="box_b">
				<div class="box_lt"><div class="box_rt"><div class="box_lb"><div class="box_rb">
					<div class="box_data">
						
						
						<img src="<?php echo $service['path'];?>/images/admin/<?php echo Locale::get();?>/login_bloglounge_logo.gif" alt="<?php echo BLOGLOUNGE;?>" />
						<hr class="line" />
						
						<div id="login_wrap">
							<div class="messages">
								<div class="<?php echo $type;?>">
<?php
							switch($type) {
								case 'error':
?>	
									<strong>오류 :</strong> 
<?php
								break;
							}
?>
									<?php echo $str; ?>
								</div>
							</div>
							<div class="toolbar">
<?php
						switch($button) {
							case 'admin':
?>
								<a href="#" class="normalbutton" onclick="history.back(); return false;"><span><?php echo _t('뒤로');?></span></a>
								<a href="<?php echo $service['path'];?>/admin/" class="normalbutton"><span class="boldbutton"><?php echo _t('관리자');?></span></a>&nbsp;
<?php	
							break;
							case 'loglout':
							default:
?>
								<a href="#" class="normalbutton" onclick="history.back(); return false;"><span><?php echo _t('뒤로');?></span></a>
								<a href="<?php echo $service['path'];?>/logout/" class="normalbutton"><span class="boldbutton"><?php echo _t('로그아웃');?></span></a>&nbsp;
<?php	
							break;
						}
?>
							</div>
						</div>

						<div id="temp_images">
							<a href="http://itcanus.net" target="_blank"><img src="<?php echo $service['path'];?>/images/admin/login_itcanus.gif" alt="ITcanus" /></a>
						</div>

						<div class="clear"></div>

					</div>
				</div></div></div></div>
			</div></div></div></div>
		</div>
	</div>
</body>
</html>
	<?php
			exit;
		}

		function goBackWithMsg($str, $url = null) {
			if (!isset($url))
				$url = $_SERVER['HTTP_REFERER'];
			Header('Location: '.$url.'?message='.rawurlencode($str));
			exit;
		}

		function getAttributesFromString($str, $caseSensitive=true) {
			$attributes = array();
			preg_match_all('/([^=\s]+)\s*=\s*"([^"]*)/', $str, $matches); 
			for($i=0; $i<count($matches[0]); $i++) {
				if(!$caseSensitive)
					$matches[1][$i] = strtolower($matches[1][$i]);
				$attributes[$matches[1][$i]] = $matches[2][$i];
			}
			preg_match_all('/([^=\s]+)\s*=\s*\'([^\']*)/', $str, $matches);
			for($i=0; $i<count($matches[0]); $i++) {
				if(!$caseSensitive)
					$matches[1][$i] = strtolower($matches[1][$i]);
				$attributes[$matches[1][$i]] = $matches[2][$i];
			}
			preg_match_all('/([^=\s]+)=([^\'"][^\s]*)/', $str, $matches);
			for($i=0; $i<count($matches[0]); $i++) {
				if(!$caseSensitive)
					$matches[1][$i] = strtolower($matches[1][$i]);
				$attributes[$matches[1][$i]] = $matches[2][$i];
			}
			return $attributes;
		}

		function printHeadHTML() {
			global $service;

			$output = 	"<script type='text/javascript'>var _path = '{$service['path']}';</script>\n".						
					"<script type='text/javascript' src='".$service['path']."/scripts/jquery.js'></script>\n".				
					"<script type='text/javascript' language='javascript' src='".$service['path']."/scripts/common.js'></script>\n".
					"<!-- \n
		".BLOGLOUNGE." v".BLOGLOUNGE_VERSION." ".BLOGLOUNGE_NAME."
		Homepage: ".BLOGLOUNGE_HOMEPAGE."
		".BLOGLOUNGE_COPYRIGHT." \n-->\n</head>";

			return $output;
		}

		function printFootHTML() {
			global $service;

			$output = "</body>";

			return $output;
		}

		function printLinkerHeadHTML() {
			global $service;

			$output = 	"<script type='text/javascript'>var _path = '{$service['path']}';</script>\n".						
					"<script type='text/javascript' src='".$service['path']."/scripts/jquery.js'></script>\n".				
					"<script type='text/javascript' src='".$service['path']."/scripts/linker.js'></script>\n".
					"<!-- \n
		".BLOGLOUNGE." v".BLOGLOUNGE_VERSION." ".BLOGLOUNGE_NAME."
		Homepage: ".BLOGLOUNGE_HOMEPAGE."
		".BLOGLOUNGE_COPYRIGHT." \n-->\n</head>";

			return $output;
		}

		function printLinkerFootHTML() {
			global $service;

			$output = "</body>";

			return $output;
		}

		function printPluginMenu($menu, $selectMenu) {
			global $event, $service;
			if(isset($event->admin[$menu]['text']) && count($event->admin[$menu]['text'])>0) {
				$links = array();
				foreach($event->admin[$menu]['text'] as $plugin=>$func) {
					include_once(ROOT . '/plugins/'.$plugin.'/index.php');		
					if (function_exists($func)) {
						array_push($links, call_user_func($func, $plugin, Plugin::getConfig($plugin)));
					}
				}

				if(count($links)>0) {
					for($i=0;$i<count($links);$i++) {
						$link = $links[$i];
						if($i==0) {
							$class = 'first_plugin_menu';
						} else if($i==count($links)-1) {
							$class = 'last_plugin_menu';
						} else {
							$class = 'sep';
						}
?>
			<li class="plugin_menu <?php echo $class;?> <?php echo $link['class'];?> <?php echo $selectMenu==$link['link']?'selected':'';?>"><span><a href="<?php echo $service['path'];?>/admin/<?php echo $menu;?>/<?php echo $link['link'];?>"><?php echo $link['text'];?></a></span></li>
<?php
					}
				}
			}
		}

		
	
		function printPaging($paging, $param = '') {
				global $accessInfo, $service;
				$path = $service['path'] . $accessInfo['subpath'];
				$result = '';
				if($paging['pagePrev'] != $paging['page']) {
					$result .= '<a href="'.$path.'/?page='.$paging['pagePrev'].$param.'" class="page_prev">'._t('이전').'</a>';
				}else {
					$result .= '<a href="#" class="page_prev page_disable">'._t('이전').'</a>';
				}

				for ($p=$paging['pageStart']; $p < $paging['pageEnd']+1; $p++) { 	
					if($p == $paging['page']) {
						$result .= '<a href="'.$path.'/?page='.$p.$param.'" class="selected">'.$p.'</a>';
					} else {
						$result .= '<a href="'.$path.'/?page='.$p.$param.'">'.$p.'</a>';
					}
				}
				if($paging['pageNext'] != $paging['page']) {
					$result .= '<a href="'.$path.'/?page='.$paging['pageNext'].$param.'" class="page_next">'._t('다음').'</a>';
				} else {
					$result .= '<a href="#" class="page_next page_disable">'._t('다음').'</a>';
				}
				return $result;
		}

		function alert($str, $closeType = null) {
			echo '<script type="text/javascript">';
			echo 'alert("'.$str.'");';
			if (!empty($closeType)) {
				switch ($closeType) {
					case 'dialog':
						echo 'parent.hideDialog();';
						break;
					case 'window':
						echo 'self.close();';
						break;
				}
			}
			echo '</script>';
			exit;
		}

		function implode_string($glue, $pieces) {
			array_walk($pieces, create_function('&$elem','$elem = "\'".trim($elem)."\'";'));
			return implode($glue, $pieces); 
		}

		function multiarray_keys($ar) {				
			foreach($ar as $k => $v) {
				$keys[] = $k;
				if (is_array($ar[$k]))
					$keys = array_merge($keys, func::multiarray_keys($ar[$k]));
			}
			return $keys;
		}

		function multiarray_values($ar,$key) {		
			$values = array();
			foreach($ar as $k => $v) {
				if($k === $key) {
					$values[] = $v;
				}
				if (is_array($ar[$k]))
					$values = array_merge($values, func::multiarray_values($ar[$k],$key));
			}
			return $values;
		}

		function array_to_lower($array,$round = 0){
			foreach($array as $key => $value){
				if(is_array($value)) $array[strtolower($key)] =  func::array_to_lower($value,$round+1);
				else $array[strtolower($key)] = strtolower($value);
			}
			return $array;
		} 

		function array_keys_exist($keys,$array) {
			if(count(array_intersect($keys,array_keys($array)))>0) {
				return true;
			}
		}

		function array_trim($arr) {
			$result = array();
			foreach ($arr as $key=>$value) {
				if (strlen(func::fulltrim($value)) > 0) {
					$result[$key] = $value;
				}
			}
			return $result;
		}

		function array_filter($arr, $needle) {
			$result = array();
			foreach ($arr as $key=>$value) {
				if ($value !== $needle) {
					$result[$key] = $value;
				}
			}
			return $result;
		}

		// array_columnsort('col1', SORT_DESC, SORT_NUMERIC, 'col2', SORT_ASC, SORT_STRING, $test);
		// from php.net, array_multisort() user contributed notes (php a-t-the-r-a-t-e chir.ag)
		function array_columnsort() { 
			$n = func_num_args();
			$ar = func_get_arg($n-1);
			if (!is_array($ar))
				return false;

			$iar = $ar; // copy
			for ($i = 0; $i < $n-1; $i++)
				$col[$i] = func_get_arg($i);

			foreach ($iar as $key => $val)
				foreach ($col as $kkey => $vval)
					if (is_string($vval))
						${"subar$kkey"}[$key] = $val[$vval];

			$arv = array();
			foreach($col as $key => $val)
				$arv[] = (is_string($val) ? ${"subar$key"} : $val);
			$arv[] = $iar;

			call_user_func_array('array_multisort', $arv);
			return $iar;
		}

		function hostname($url) {
			if (strpos($url, '://') === false)
				$url = 'http://'.$url;
			if (!$a = parse_url($url))
				return $url;
			return (strtolower(substr($a['host'], 0, 4)) == 'www.') ? substr($a['host'], 4) : $a['host'];
		}

		function fulltrim($str) {
			$str = preg_replace("/([\r]|[\n]|[\s])+/", '', $str);
			$str = str_replace('%20', '', $str);
			$str = str_replace('　', '', $str);
			$str = str_replace('&nbsp;', '', $str);
			return $str;
		}

		function htmltrim($str) {
			return str_replace('&nbsp;','',trim($str));
		}

		function mkpath($path) {
			$dirs = array();
			$path = preg_replace('/(\/){2,}|(\\\){1,}/', '/', $path);
			$dirs = explode('/',$path);

			$path='';
			foreach ($dirs as $element) {
				$path.=$element.'/';
				if (!is_dir($path)) {
					if (!mkdir($path)) return false;
					@chmod($path, 0777);
				 }
			}
			return true;
		} // end mkpath

		function rmpath($path) {
			if (is_dir($path)) {
				if (version_compare(PHP_VERSION, '5.0.0') < 0) {
					$entries = array();
					if ($handle = opendir($path)) {
						while (false !== ($file = readdir($handle))) $entries[] = $file;
						closedir($handle);
					}
				} else {
					$entries = scandir($path);
					if ($entries === false) $entries = array(); // just in case scandir fail...
				}

				foreach ($entries as $entry) {
					if ($entry != '.' && $entry != '..') {
						func::rmpath($path.'/'.$entry);
					}
				}
				return rmdir($path);
			} else {
				return unlink($path);
			}
		} // end rmpath

		function makePaging($page, $pageCount, $totalCount, $pageCut = 3, $pageDatas = null) {
			$paging = array();

			$paging['page'] = $page;
			$paging['pageCount'] = $pageCount;
			$paging['totalFeeds'] = $totalCount;
			$paging['totalPages'] = intval(($totalCount - 1) / $pageCount) + 1;
			if ($paging['totalPages'] == 0) $paging['totalPages'] = 1;

			$paging['pageCut'] = $pageCut + 1;

			$paging['pageStart'] = ($page - $paging['pageCut'] < 1) ? 1 : $page - $paging['pageCut'];// //($paging['pageCut'] * floor($page/$paging['pageCut']));
			if ($paging['pageStart'] == 0) $paging['pageStart'] = 1;
			$paging['pageEnd'] = ($page + $paging['pageCut'] > $paging['totalPages']) ? $paging['totalPages'] : ($page + $paging['pageCut']);
			$paging['pagePrev'] = ($page-1 <= 1) ? 1:($page - 1);
			$paging['pageNext'] = (($page + 1) > $paging['totalPages']) ? $paging['totalPages'] : ($page + 1);

			if(empty($pageDatas)) {
				$paging['pageDatas'] = array();
				$pageStart = $paging['pageStart'];
				if($pageStart > $paging['pagePrev']) $pageStart = $paging['pagePrev'];
				$pageEnd = $paging['pageEnd'];
				if($pageEnd < $paging['pageNext']) $pageEnd = $paging['pageNext'];

				for($i=$pageStart;$i<=$pageEnd;$i++) {
					$paging['pageDatas'][$i] = '/?page='.$i;
				}

				$paging['pageDatas'][$paging['totalPages']] = '/?page='.$paging['totalPages'];
			} else {
				$paging['pageDatas'] = $pageDatas;
			}
			
			return $paging;
		}		
		
		function firstSlashDelete($str) {
			if(substr($str,0,1) == '/') {
				return Func::firstSlashDelete(substr($str, 1));
			}
			return $str;
		}

		function lastSlashDelete($str) {
			if(substr($str,strlen($str)-1,1) == '/') {
				return Func::lastSlashDelete(substr($str, 0, strlen($str)-1));
			}
			return $str;
		}

		function isNew($date, $dday = 1) {		
			$today = mktime();
			$diff = $today - $date;		
			$day =  round($diff/60/60/24);

			if($day < $dday) {
				return true;
			}
			return false;
		}

		function longURLtoShort($url,$checkURL=55,$leftURL=39,$rightURL=8) {
			//$checkURL	= 55; // 체크할 URL 길이
			//$leftURL	= 39; // '...'의 왼쪽에 나타낼 URL 길이
			//$rightURL	= 8; // '...'의 오른쪽에 나타낼 URL의 길이
			
			$link = $url;

			$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);
			$url  = "http://".htmlspecialchars($url);
			$url = strtolower(str_replace("http://http://","http://",$url));
			$templink = strip_tags($link);
			$templink = "http://".htmlspecialchars($templink);
			$templink = strtolower(str_replace("http://http://","http://",$templink));

			if (strpos($url, 'www.') === 0)
				$full_url = 'http://'.$full_url;
			else if (strpos($url, 'ftp.') === 0)
				$full_url = 'ftp://'.$full_url;
			else if (!preg_match('#^([a-z0-9]{3,6})://#', $url, $bah))
				$full_url = 'http://'.$full_url;

			$link = ($templink == '' || $templink == $url) ? ((strlen($url) > $checkURL) ? UTF8::lessenAsEm($url, $leftURL, '').' &hellip; '.substr(rawurlencode($url), ($rightURL*-1)) : stripslashes($link)) : stripslashes($link);

			return $link;
		}

		function dateToString($date) {
			$today = mktime();

			$diff = $today - $date;
		
			$day =  floor($diff/60/60/24);
			$hour =  floor($diff/60/60);
			$min = floor($diff/60);
			$sec = $diff;
		
			$result = null;

			if($day == 0) {
				if($min < 60) {
					if($sec < 60) {
						if($sec == 0) {
							$result = _f('방금', '');
						} else {
							$result = _f('%1초 전', $sec);
						}
					} else {
						$result = _f('%1분 전', $min);
					}
				} else {
					$result = _f('%1시간 전', $hour);
				}
			} else {
				switch($day) {
					case 1: $result = _t('어제'); break;
					case 2: $result = _t('그저께'); break;
					case 3: $result = _t('그끄저께'); break;
					default:
						$result = _f('%1일 전', $day);
					break;
				}
			}

			return $result;

		}
		// url1 의 대표도메인에 url2 를 붙인다.
		function unionAddress($url1, $url2) {
			$url1 = str_replace('http://','',$url1);
			if($i = strpos($url1, '/')) {
				$url1 = substr($url1,0,$i);
			}
		
			$_url1 = func::lastSlashDelete($url1);
			$_url2 = func::firstSlashDelete($url2);

			return 'http://' . $_url1 . '/' . $_url2;
		}

		function filterURLModel($url, $className = 'point') {
			return preg_replace('/http:\/\/(.*)\//i', 'http://<span class="' . $className . '">$1</span>/', $url);
		}

		function isWhatBlog($url) {
			$url = isset($url)?$url:'';
			
			if (preg_match('/http:\/\/(.*).tistory.com*/i', $url, $match)) { // tistory
				return 'tistory';
			} else if (preg_match('/http:\/\/(.*).textcube.com*/i', $url, $match)) { // textcube
				return 'textcube';
			} else if (preg_match('/http:\/\/(.*).egloos.com*/i', $url, $match)) { // egloos
				return 'egloos';
			} else if (preg_match('/http:\/\/blog.(.*).(com|net)\/(.*)*/i', $url, $match)) { // naver, paran, empas, daum
				switch($match[1]) {
					case 'naver':
					case 'empas':
					case 'paran':
					case 'daum':
						return $match[1];
					break;
				}
				return '';
			} else if (preg_match('/http:\/\/kr.blog.yahoo.com\/(.*)*/i', $url, $match)) { // empas
				return 'yahoo_kr';
			}
		}

		function encode($str) {
			return str_replace('%2F','%252F',urlencode($str));
		}

		function decode($str) {
			return urldecode(str_replace('%252F', '%2F', $str));
		}
		
		function translate_uri($url) {
			$url = str_replace('http://','',$url);
			$parts = explode('/', $url);
			for ($i = 0; $i < count($parts); $i++) {
			  $parts[$i] = rawurlencode($parts[$i]);
			}
			$url = 'http://'.implode('/', $parts);

			$url = str_replace('%3F','?',$url);
			$url = str_replace('%3D','=',$url);
			$url = str_replace('%26','&',$url);
			return $url;
		}

		function explode_uri($url) {
			$url = str_replace('?','',$url);
			$result = array();
			$list = explode('&',$url);
			foreacH($list as $item) {
				$data = explode('=',$item);
				$result[$data[0]] = $data[1];
			}
			return $result;
		}
	}
?>
