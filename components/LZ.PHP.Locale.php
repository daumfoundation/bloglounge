<?php

	// Locale class inherited from Textcube 1.5 / GPL
	// component dependency : Needlworks.Core.Locale, LZ.PHP.XMLStruct

	class Locale { 
		function get() {
			global $__locale;
			return $__locale['locale'];
		}
		
		function set($locale) {
			global $__locale, $__text, $service;
			if (strtolower($locale) == 'auto') {
				$locale = 'ko';
				$supportedLanguages = Locale::getSupportedLocales();
				if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
					foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $_accepted) {
						if (in_array($_accepted, $supportedLanguages)) {
							$locale = $_accepted;
							break;	
						}
					}
				}
			}
			list($common) = explode('-', $locale, 2);
			Locale::refreshLocaleResource($locale);
			if (file_exists($__locale['directory'] . '/' . $locale . '.php')) {
				include($__locale['directory'] . '/' . $locale . '.php');
				$__locale['locale'] = $locale;
				return true;
			} else if (($common != $locale) && file_exists($__locale['directory'] . '/' . $common . '.php')) {
				include($__locale['directory'] . '/' . $common . '.php');
				$__locale['locale'] = $common;
				return true;
			}
			return false;
		}

		function setSkinLocale($locale) {
			global $__locale, $__skinText;
			list($common) = explode('-', $locale, 2);
			Locale::refreshLocaleResource($locale);
			if (file_exists($__locale['directory'] . '/' . $locale . '.php')) {
				$__skinText = Locale::includeLocaleFile($__locale['directory'] . '/' . $locale . '.php');
				return true;
			} else if (($common != $locale) && file_exists($__locale['directory'] . '/' . $common . '.php')) {
				$__skinText = Locale::includeLocaleFile($__locale['directory'] . '/' . $common . '.php');
				return true;
			}
			return false;
		}
		
		function includeLocaleFile($languageFile) {
			global $service;
			include($languageFile);
			return $__text;
		}

		function refreshLocaleResource($locale) {
			global $__locale;
			// po파일과 php파일의 auto convert 지원을 위한 루틴.
			$lang_php = $__locale['directory'] . '/' . $locale . ".php";
			$lang_po = $__locale['directory'] . '/po/' . $locale . ".po";
			// 두 파일 중 최근에 갱신된 것을 찾는다.
			$time_po = filemtime( $lang_po );
			$time_php = filemtime( $lang_php );
			// po파일이 더 최근에 갱신되었으면 php파일을 갱신한다.
			if ($time_po && ($time_po > $time_php)) {
				requireComponent('Needlworks.Core.Locale');
				$langConvert = new Po2php;
				$langConvert->open($lang_po);
				$langConvert->save($lang_php);
			}
			return false;
		}

		function setDirectory($directory) {
			global $__locale;
			if (!is_dir($directory))
				return false;
			$__locale['directory'] = $directory;
			return true;
		}
		
		function setDomain($domain) {
			global $__locale;
			$__locale['domain'] = $domain;
			return true;
		}
		
		function match($locale) {
			global $__locale;
			if (strcasecmp($locale, $__locale['locale']) == 0)
				return 3;
			else if (strncasecmp($locale, $__locale['locale'], 2) == 0)
				return 2;
			else if (strncasecmp($locale, 'en', 2) == 0)
				return 1;
			return 0;
		}
		
		function getSupportedLocales() {
			global $__locale;
			$locales = array();
			if ($dir = dir($__locale['directory'])) {
				while (($entry = $dir->read()) !== false) {
					if (!is_file($__locale['directory'] . '/' . $entry))
						continue;
					$locale = substr($entry, 0, strpos($entry, '.'));
					if (empty($locale) || $locale == 'messages')
						continue;
					if ($fp = fopen($__locale['directory'] . '/' . $entry, 'r')) {
						$desc = fgets($fp);
						if (preg_match('/<\?(php)?\s*\/\/\s*(.+)/', $desc, $matches))
							$locales[$locale] = _t(trim($matches[2]));
						else
							$locales[$locale] = $locale;
						fclose($fp);
					}
				}
				$dir->close();
			}
			return $locales;
		}
	}

	$__locale = array(
		'locale' => null,
		'directory' => './languages/locale',
		'domain' => null,
		);

	function _t($t) {
		global $__locale, $__text;
		if (isset($__locale['domain']) && isset($__text[$__locale['domain']][$t]))
			return $__text[$__locale['domain']][$t];
		else if (isset($__text[$t]))
			return $__text[$t];
		return $t;
	}

	function _f($t) {
		$t = _t($t);
		if (($n = func_num_args()) <= 1)
			return $t;
		$arg = func_get_args();
		for ($i = 1; $i < $n; $i++) {
			$t = str_replace('%'.$i, $arg[$i], $t);
		}
		return $t;
	}

	// General utility functions for Chinese,Japanese,Korean Environment

	class CJK {
		function num2talk($num) {
			global $__locale;
			$myLocale = (isset($__locale['locale']) && !empty($__locale['locale'])) ? $__locale['locale'] : 'ko'; // default is ko
			if (!Validator::enum($myLocale, 'ko,ja,zn')) // only CJK
				return $num;

			$zero = array();
			$zero['ko'] = '영';
			$zero['ja'] = $zero['zh']= '零';

			if (!isset($num) || ($num <= 0)) 
				return $zero[$myLocale];
			$num = "$num";
			$len = $s = strlen($num);
			$result = array();

			switch ($myLocale) {
				case 'zh':
				case 'ja':
					$hfix = '二十';
					$units = array('','萬','億','兆','京','垓');
					$unitl = array('','十','百','千');

					$nc = array('','一','二','三','四','五','六','七','八','九');
					$nk = array('','一','二','三','四','五','六','七','八','九');
					$nh = array('','十','二十','三十','四十','五十','六十','七十','八十','九十');
					break;

				default:
				case 'ko':
					$hfix = '스무';
					$units = array('','만','억','조','경','해');
					$unitl = array('','십','백','천');

					$nc = array('','일','이','삼','사','오','육','칠','팔','구');
					$nk = array('','한','두','세','네','다섯','여섯','일곱','여덟','아홉');
					$nh = array('','열','스물','서른','마흔','쉰','예순','일흔','여든','아흔');
					break;
			}

			for ($i = 0; $i < $len; $i++) {
				$v = $num{$i};
				$r = $nc[$v];
				if ($i > $len-2) $r = $nk[$v];
				$c = ( --$s % 4 );
				$t = ( $v ) ? $unitl[$c] : '';

				switch ($c) {
					case 0:
						$cut = ($i < 4) ? $i : 3;
						$tmp = substr($num, $i-$cut, $cut+1);
						if (!intval($tmp)) $t = '';
						else $t = $units[floor($s/4)];
						break;

					case 1:
						if ($i > $len-3) {
							if (($v == 2) && ($num{$i+1} == '0')) $r = $hfix;
							else $r = $nh[$v];
							$t = '';
						} else if ($v == 1) 
							$r = '';
						break;

					case 2:
					case 3:
						if ($v == 1) $r = '';
						break;
				}
				array_push($result, $r.$t);
			}
			return @implode('', $result);
		}
	}

	class Korean {
		function doesHaveFinalConsonant($hanStr)  {
			$hanChar = substr($hanStr, strlen($hanStr)-3);
			$ha = (isset($hanChar[1])) ? ((ord($hanChar[1])&0x3F)<<6) : null;
			$hb = (isset($hanChar[2])) ? (ord($hanChar[2])&0x3F) : null;
			$jong = ((((((ord($hanChar[0])&0x0F)<<12) | $ha | $hb)) - 0xAC00) % (21*28)) % 28;
			return ($jong > 0) ? true : false;
		} 

		function attachAux($str, $aux1, $aux2) {
			return $str.(Korean::doesHaveFinalConsonant($str)?$aux1:$aux2);
		}
	}

	class Japanese {
		// preserved
	}

	class Chinese {
		// preserved
	}

	class msg { // special message generator
		function getRandomMsg($type) { // type = enum('welcome')
			global $__locale;
			static $__messages = array();
			if (!isset($type) || empty($type)) return false;
			$myLocale = (isset($__locale['locale']) && !empty($__locale['locale'])) ? $__locale['locale'] : 'ko'; // default is ko

			if (!isset($__messages['welcome'])) {
				$__messages['welcome'] = array();
				$__messages['welcome']['ko'] = '안녕하세요';
				$__messages['welcome']['ja'] = 'こんにちは';
				$__messages['welcome']['zh'] = '你好';
				$__messages['welcome']['en'] = 'Welcome';
			}

			return $__messages[$type][$myLocale];
		}

		function makeWelcomeMsg($msgPack = 'default') {
			$useClockBase = false;

			$xmls=new XMLStruct();
			if (!$xmls->openFile(ROOT . '/language/welcome/'.$msgPack.'.xml'))
				return msg::getRandomMsg('welcome');

			if ($useClockBase) {
				$hour = date("H");
				switch(true) {
					case (($hour >= 2) && ($hour <= 5)):
						$tick = 'daybreak';
						break;
					case (($hour >= 6) && ($hour <= 11)):
						$tick = 'morning';
						break;
					case (($hour >= 12) && ($hour <= 17)):
						$tick = 'afternoon';
					case (($hour >= 18) && ($hour <= 19)):
						$tick = 'evening';
					case (($hour >= 20) || ($hour <= 1)):
						$tick = 'night';
				}
			} else { // common
				$tick = 'common';
			}

			$max = $xmls->getNodeCount("/welcome/$tick/item");
			$bingo = mt_rand(1, $max);

			return $xmls->getValue("/welcome/$tick/item[$bingo]");
		}
	
	}
?>