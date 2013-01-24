<?php

	// Convert numeric character(string) reference to UTF-8
	// from php.net, utf8_decode() manual page. inspired by tobias at code-x dot de

	if (!function_exists('mb_decode_numericentity')) {
		function mb_decode_numericentity($str, $dumb = null, $dumber = null) {
			if (!function_exists('_mb_decode_numericentity_callback') ) {
				function _mb_decode_numericentity_callback($t) {
					$decode = $t[1];
					if ($decode < 128) {
						$str = chr($decode);
					} else if ($decode < 2048) {
						$str = chr(192 + (($decode - ($decode % 64)) / 64));
						$str .= chr(128 + ($decode % 64));
					} else {
						$str = chr(224 + (($decode - ($decode % 4096)) / 4096));
						$str .= chr(128 + ((($decode % 4096) - ($decode % 64)) / 64));
						$str .= chr(128 + ($decode % 64));
					}
					return $str;
				}
			}
			return preg_replace_callback('/&#([0-9]{1,});/', '_mb_decode_numericentity_callback', $str);
		}
	}

	// base UTF8 class inherits from Textcube/Tattertools

	class UTF8 {
		function validate($str, $truncated = false) {
			$length = strlen($str);
			if ($length == 0)
				return true;
			for ($i = 0; $i < $length; $i++) {
				$high = ord($str{$i});
				if ($high < 0x80) {
					continue;
				} else if ($high <= 0xC1) {
					return false;
				} else if ($high < 0xE0) {
					if (++$i >= $length)
						return $truncated;
					else if (($str{$i} & "\xC0") == "\x80")
						continue;
				} else if ($high < 0xF0) {
					if (++$i >= $length) {
						return $truncated;
					} else if (($str{$i} & "\xC0") == "\x80") {
						if (++$i >= $length)
							return $truncated;
						else if (($str{$i} & "\xC0") == "\x80")
							continue;
					}
				} else if ($high < 0xF5) {
					if (++$i >= $length) {
						return $truncated;
					} else if (($str{$i} & "\xC0") == "\x80") {
						if (++$i >= $length) {
							return $truncated;
						} else if (($str{$i} & "\xC0") == "\x80")  {
							if (++$i >= $length)
								return $truncated;
							else if (($str{$i} & "\xC0") == "\x80")
								continue;
						}
					}
				} // F5~FF is invalid by RFC 3629
				return false;
			}
			return true;
		}
		
		function correct($str, $broken = '') {
			$corrected = '';
			$strlen = strlen($str);
			for ($i = 0; $i < $strlen; $i++) {
				switch ($str{$i}) {
					case "\x09":
					case "\x0A":
					case "\x0D":
						$corrected .= $str{$i};
						break;
					case "\x7F":
						$corrected .= $broken;
						break;
					default:
						$high = ord($str{$i});
						if ($high < 0x20) { // Special Characters.
							$corrected .= $broken;
						} else if ($high < 0x80) { // 1byte.
							$corrected .= $str{$i};
						} else if ($high <= 0xC1) {
							$corrected .= $broken;
						} else if ($high < 0xE0) { // 2byte.
							if (($i + 1 >= $strlen) || (($str{$i + 1} & "\xC0") != "\x80"))
								$corrected .= $broken;
							else
								$corrected .= $str{$i} . $str{$i + 1};
							$i += 1;
						} else if ($high < 0xF0) { // 3byte.
							if (($i + 2 >= $strlen) || (($str{$i + 1} & "\xC0") != "\x80") || (($str{$i + 2} & "\xC0") != "\x80"))
								$corrected .= $broken;
							else
								$corrected .= $str{$i} . $str{$i + 1} . $str{$i + 2};
							$i += 2;
						} else if ($high < 0xF5) { // 4byte.
							if (($i + 3 >= $strlen) || (($str{$i + 1} & "\xC0") != "\x80") || (($str{$i + 2} & "\xC0") != "\x80") || (($str{$i + 3} & "\xC0") != "\x80"))
								$corrected .= $broken;
							else
								$corrected .= $str{$i} . $str{$i + 1} . $str{$i + 2} . $str{$i + 3};
							$i += 3;
						} else { // F5~FF is invalid by RFC3629.
							$corrected .= $broken;
						}
						break;
				}
			}
			
			// 네이버등 xml_parser 에서 읽지 못하는 특수문자가 본문에 포함되어 있을때.. &#8.. 이런식으로 &#8200; 과 같이 제대로 끝마치지 못한 특수문자.. 에러..
			$corrected = preg_replace('/&#([0-9]{1,})[^0-9^;]/','', $corrected); // 제대로 마쳐지지 않은 특수문자 제거

			if (preg_match('/&#([0-9]{1,});/', $corrected)) {
				$corrected = mb_decode_numericentity($corrected, array(0x0, 0x10000, 0, 0xfffff), 'UTF-8');
			}
			return $corrected;
		}

		function bring($str, $encoding = null) { // alias of convert()
			return UTF8::convert($str, $encoding);
		}
		
		function convert($str, $encoding = null) {
			if (!isset($encoding)) $encoding = 'EUC-KR';
			if (UTF8::validate($str)) return $str;
			return (function_exists('mb_convert_encoding')) ? mb_convert_encoding($str, 'UTF-8', $encoding) : iconv($encoding, 'UTF-8', $str);
		}
		
		function length($str) {
			$len = strlen($str);
			for ($i = $length = 0; $i < $len; $length++) {
				$high = ord($str{$i});
				if ($high < 0x80)
					$i += 1;
				else if ($high < 0xE0)
					$i += 2;
				else if ($high < 0xF0)
					$i += 3;
				else
					$i += 4;
			}
			return $length;
		}
		
		function lengthAsEm($str) {
			$len = strlen($str);
			for ($i = $length = 0; $i < $len; ) {
				$high = ord($str{$i});
				if ($high < 0x80) {
					$i += 1;
					$length += 1;
				} else {
					if ($high < 0xE0)
						$i += 2;
					else if ($high < 0xF0)
						$i += 3;
					else
						$i += 4;
					$length += 2;
				}
			}
			return $length;
		}
		
		function lessen($str, $chars, $tail = '..') {
			if (UTF8::length($str) <= $chars)
				$tail = '';
			else
				$chars -= UTF8::length($tail);
			$len = strlen($str);
			for ($i = $adapted = 0; $i < $len; $adapted = $i) {
				$high = ord($str{$i});
				if ($high < 0x80)
					$i += 1;
				else if ($high < 0xE0)
					$i += 2;
				else if ($high < 0xF0)
					$i += 3;
				else
					$i += 4;
				if (--$chars < 0)
					break;
			}
			return substr($str, 0, $adapted) . $tail;
		}
		
		function lessenAsByte($str, $bytes, $tail = '..') {
			if (strlen($str) <= $bytes)
				$tail = '';
			else
				$bytes -= strlen($tail);
			$len = strlen($str);
			for ($i = $adapted = 0; $i < $len; $adapted = $i) {
				$high = ord($str{$i});
				if ($high < 0x80)
					$i += 1;
				else if ($high < 0xE0)
					$i += 2;
				else if ($high < 0xF0)
					$i += 3;
				else
					$i += 4;
				if ($i > $bytes)
					break;
			}
			return substr($str, 0, $adapted) . $tail;
		}
		
		function lessenAsEm($str, $ems, $tail = '..') {
			if (UTF8::lengthAsEm($str) <= $ems)
				$tail = '';
			else
				$ems -= strlen($tail);
			$len = strlen($str);
			for ($i = $adapted = 0; $i < $len; $adapted = $i) {
				$high = ord($str{$i});
				if ($high < 0x80) {
					$i += 1;
					$ems -= 1;
				} else {
					if ($high < 0xE0)
						$i += 2;
					else if ($high < 0xF0)
						$i += 3;
					else
						$i += 4;
					$ems -= 2;
				}
				if ($ems < 0)
					break;
			}
			return substr($str, 0, $adapted) . $tail;
		}

		function clear($string) {
			$string = str_replace('&apos;', '&#39;', $string); // http://www.w3.org/TR/xhtml1/guidelines.html#C_16
			$string = UTF8::correct($string);
			$string = stripslashes($string);
			$string = html_entity_decode($string);
			$string = str_replace('&amp;amp;', '&', htmlspecialchars($string));
			return $string;
		}
	}

	class Validator {
		function language($value) {
			return preg_match('/^[[:alpha:]]{2}(\-[[:alpha:]]{2})?$/', $value);
		}

		function getBool($value) {
			if (!isset($value)) {
				return false;
			}

			if (is_numeric($value)) {
				if (intval($value) == 0) {
					return false;
				} else {
					return true;
				}
			}

			switch(strtolower($value)) {
				case 'y':
				case 'yes':
				case 'on':
					return true;
					break;
				case 'n':
				case 'no':
				case 'off':
					return false;
					break;
			}

			return (!empty($value) && (!is_string($value) || (strcasecmp('false', $value) && strcasecmp('off', $value) && strcasecmp('no', $value))));
		}

		function enum($needle, $haystack, $haystackSeperator = ',') {
			if (!is_array($haystack)) {
				$haystack = explode($haystackSeperator, $haystack);
			} 

			foreach($haystack as $key => $value) {
				$haystack[$key] = trim(strtolower($value));
			}
			
			return in_array(trim(strtolower($needle)), $haystack);
		}

		function is_email($email) {
		   return preg_match("/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$/i", $email);
		}

		function is_ip($value) {
			return preg_match('/^\\d{1,3}(\\.\\d{1,3}){3}$/', $value);
		}
		
		function is_domain($value) {
			return ((strlen($value) <= 64) && preg_match('/^([[:alnum:]]+(-[[:alnum:]]+)*\\.)+[[:alnum:]]+(-[[:alnum:]]+)*$/', $value));
		}

		function is_digit($value) {
			return preg_match("/^[0-9]+$/", $value);
		}

		function is_alnum($value) {
			return preg_match("/^[0-9a-zA-Z]+$/", $value);
		}

		function is_empty($str) {
			if (isset($str)) {
				$str = preg_replace("/([\r]|[\n]|[\s])+/", '', $str);
				$str = str_replace('%20', '', $str);
				$str = str_replace('　', '', $str);
			}
			return empty($str);
		}
	}

	class Timestamp {
		function format($format = '%c', $time = null) {
			if (isset($time))
				return strftime(_t($format), $time);
			else
				return strftime(_t($format));
		}
		
		function formatGMT($format = '%c', $time = null) {
			if (isset($time))
				return gmstrftime(_t($format), $time);
			else
				return gmstrftime(_t($format));
		}
		
		function format2($time) {
			if (date('Ymd', $time) == date('Ymd'))
				return strftime(_t('%H:%M'), $time);
			else if (date('Y', $time) == date('Y', time()))
				return strftime(_t('%m/%d'), $time);
			else
				return strftime(_t('%Y'), $time);
		}
	
		function format3($time) {
			if (date('Ymd', $time) == date('Ymd'))
				return strftime(_t('%H:%M:%S'), $time);
			else
				return strftime(_t('%Y/%m/%d'), $time);
		}
		
		function format5($time = null) {
			return (isset($time) ? strftime(_t('%Y/%m/%d %H:%M'), $time) : strftime(_t('%Y/%m/%d %H:%M')));
		}
		
		function formatDate($time = null) {
			return (isset($time) ? strftime(_t('%Y/%m/%d'), $time) : strftime(_t('%Y/%m/%d')));
		}
		
		function formatDate2($time = null) {
			return (isset($time) ? strftime(_t('%Y/%m'), $time) : strftime(_t('%Y/%m')));
		}
		
		function formatTime($time = null) {
			return (isset($time) ? strftime(_t('%H:%M:%S'), $time) : strftime(_t('%H:%M:%S')));
		}
		
		function get($format = 'YmdHis', $time = null) {
			return (isset($time) ? date($format, $time) : date($format));
		}
		
		function getGMT($format = 'YmdHis', $time = null) {
			return (isset($time) ? gmdate($format, $time) : gmdate($format));
		}
		
		function getDate($time = null) {
			return (isset($time) ? date('Ymd', $time) : date('Ymd'));
		}
		
		function getYearMonth($time = null) {
			return (isset($time) ? date('Ym', $time) : date('Ym'));
		}
	
		function getYear($time = null) {
			return (isset($time) ? date('Y', $time) : date('Y'));
		}
	
		function getTime($time = null) {
			return (isset($time) ? date('His', $time) : date('His'));
		}
		
		function getRFC1123($time = null) {
			return (isset($time) ? date('r', $time) : date('r'));
		}
		
		function getRFC1123GMT($time = null) {
			return (isset($time) ? gmdate('D, d M Y H:i:s \G\M\T', $time) : gmdate('D, d M Y H:i:s \G\M\T'));
		}
	}

	class TimePeriod {
		function checkPeriod($period) {
			if (is_numeric($period)) {
				$year = 0;
				$month = 1;
				$day = 1;
				switch (strlen($period)) {
					case 8:
						$day = substr($period, 6, 2);
					case 6:
						$month = substr($period, 4, 2);
					case 4:
						$year = substr($period, 0, 4);
						return checkdate($month, $day, $year);
				}
			}
			return false;
		}

		function getTimeFromPeriod($period) {
			if (is_numeric($period)) {
				$year = 0;
				$month = 1;
				$day = 1;
				switch (strlen($period)) {
					case 8:
						$day = substr($period, 6, 2);
					case 6:
						$month = substr($period, 4, 2);
					case 4:
						$year = substr($period, 0, 4);
						if (checkdate($month, $day, $year))
							return mktime(0, 0, 0, $month, $day, $year);
				}
			}
			return false;
		}

		function addPeriod($period, $inc = 1) {
			if (TimePeriod::checkPeriod($period) !== false) {
				switch (strlen($period)) {
					case 4:
						return strftime('%Y', mktime(0, 0, 0, 1, 1, $period + $inc));
					case 6:
						return strftime('%Y%m', mktime(0, 0, 0, substr($period, 4) + $inc, 1, substr($period, 0, 4)));
					case 8:
						return strftime('%Y%m%d', mktime(0, 0, 0, substr($period, 4, 2), substr($period, 6, 2) + $inc, substr($period, 0, 4)));
				}
			}
			return false;
		}

		function getPeriodLabel($period) {
			$name = strval($period);
			switch (strlen($name)) {
				case 4:
					return $name;
				case 6:
					return substr($name, 0, 4) . '/' . substr($name, 4);
				case 8:
					return substr($name, 0, 4) . '/' . substr($name, 4, 2) . '/' . substr($name, 6) . '';
			}
		}
	}

	class Encrypt {
		function hmac($key, $data){ // HMAC-MD5
			$byteLength = 64; // byte length for md5
			if (strlen($key) > $byteLength)
				$key = pack("H*", md5($key));

			$key  = str_pad($key, $byteLength, chr(0x00));
			$ipad = str_pad('', $byteLength, chr(0x36));
			$opad = str_pad('', $byteLength, chr(0x5c));
			$k_ipad = $key ^ $ipad ;
			$k_opad = $key ^ $opad;

			return md5($k_opad.pack("H*", md5($k_ipad . $data)));
		}

		function hmacsha1($key, $data){ // HMAC-SHA1
			$byteLength = 64; // byte length for sha1
			if (strlen($key) > $byteLength)
				$key = pack("H*", sha1($key));

			$key  = str_pad($key, $byteLength, chr(0x00));
			$ipad = str_pad('', $byteLength, chr(0x36));
			$opad = str_pad('', $byteLength, chr(0x5c));
			$k_ipad = $key ^ $ipad ;
			$k_opad = $key ^ $opad;

			return sha1($k_opad.pack("H*", sha1($k_ipad . $data)));
		}

		function hex2bin($hex){
			$bin='';
			for ($i=0, $length = strlen($hex); $i< $length; $i++)
				$bin .= str_pad(decbin(hexdec($hex{$i})), 4, '0', STR_PAD_LEFT);
			return $bin;
		}
	}
?>
