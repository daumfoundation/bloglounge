<?php

	// Copyright of each part belongs its author.
	// LZ.XMLStruct class based on XMLStruct class in Eolin.PHP.Core component of TextCube 1.5.
	// Tatter Network Foundation / NeedleWorks has all legal rights of Textcube 1.5/ license under GPL.

	class XMLStruct {
		var $struct, $error;

		function XMLStruct() {
			$this->ns = array();
			$this->nsenabled = true;
		}

		function getValueByLocale($param) {
			if (!is_array($param)) return $param;
			for ($i = 0; $i < count($param); $i++) {
				$lang = (isset($param[$i]['.attributes']['xml:lang'])) ? $param[$i]['.attributes']['xml:lang'] : '';
				switch (Locale::match($lang)) {
					case 3:
						$matched = $param[$i];
						unset($secondBest);
						unset($thirdBest);
						$i = count($param); // for exit loop
						break;
					case 2:
						$secondBest = $param[$i];
						break;
					case 1:
						$thirdBest = $param[$i];
						break;
					case 0:
						if (!isset($thirdBest))
							$thirdBest = $param[$i];
						break;
				}
			}
			if (isset($secondBest)) {
				$matched = $secondBest;
			} else if (isset($thirdBest)) {
				$matched = $thirdBest;
			}
			
			if (!isset($matched))
				return null;
			
			if (isset($matched['.value']))
				return $matched['.value'];
			return null;
		}
		
		function setNameSpacePrefix( $prefix, $url ) {
			$this->ns[$prefix] = $url;
		}

		function expandNS($item) {
			if (!$this->nsenabled) 
				return $item;

			foreach ($this->ns as $prefix => $url ) {
				if (substr( $item, 0, strlen($prefix) + 1) == "$prefix:" ) {
					return "$url:" . substr( $item, strlen($prefix) + 1 );
				}
			}
			return $item;
		}
		
		function open($xml, $encoding = null, $nsenabled = false) {
			if (!empty($encoding) && (strtolower($encoding) != 'utf-8') && !UTF8::validate($xml)) {
				if (preg_match('/^<\?xml[^<]*\s+encoding=["\']?([\w-]+)["\']?/', $xml, $matches)) {
					$encoding = $matches[1];
					$xml = preg_replace('/^(<\?xml[^<]*\s+encoding=)["\']?[\w-]+["\']?/', '$1"utf-8"', $xml, 1);
				}
				if (strcasecmp($encoding, 'utf-8')) {
					$xml = UTF8::bring($xml, $encoding);
					if ($xml === null) {
						$this->error = XML_ERROR_UNKNOWN_ENCODING;
						return false;
					}
				}
			} else {
				if (substr($xml, 0, 3) == "\xEF\xBB\xBF")
					$xml = substr($xml, 3);
			}
			$this->nsenabled = $nsenabled;
			$p = ($nsenabled) ? xml_parser_create_ns() : xml_parser_create();
			xml_set_object($p, $this);
			xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($p, 'o', 'c');
			xml_set_character_data_handler($p, 'd');
			xml_set_default_handler($p, 'x');
			$this->struct = array();
			$this->_cursor = &$this->struct;
			$this->_path = array('');
			$this->_cdata = false;
			if (!xml_parse($p, $xml))
				return $this->_error($p);
			unset($this->_cursor);
			unset($this->_cdata);
			if (xml_get_error_code($p) != XML_ERROR_NONE)
				return $this->_error($p);
			xml_parser_free($p);
			return true;
		}
		
		function openFile($filename, $correct = false) {
			if (!$fp = fopen($filename, 'r'))
				return false;
			$p = xml_parser_create();
			xml_set_object($p, $this);
			xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($p, 'o', 'c');
			xml_set_character_data_handler($p, 'd');
			xml_set_default_handler($p, 'x');
			$this->struct = array();
			$this->_cursor = &$this->struct;
			$this->_path = array('');
			$this->_cdata = false;
			if ($correct) {
				$remains = '';
				while (!feof($fp)) {
					$chunk = $remains . fread($fp, 10240);
					$remains = '';
					if (strlen($chunk) >= 10240) {
						for ($c = 1; $c <= 4; $c++) {
							switch ($chunk{strlen($chunk) - $c} & "\xC0") {
								case "\x00":
								case "\x40":
									if ($c > 1) {
										$remains = substr($chunk, strlen($chunk) - $c + 1);
										$chunk = substr($chunk, 0, strlen($chunk) - $c + 1);
									}
									$c = 5;
									break;
								case "\xC0":
									$remains = substr($chunk, strlen($chunk) - $c);
									$chunk = substr($chunk, 0, strlen($chunk) - $c);
									$c = 5;
									break;
							}
						}
					}
					if (!xml_parse($p, UTF8::correct($chunk, '?'), false)) {
						fclose($fp);
						return $this->_error($p);
					}
				}
			} else {
				while (!feof($fp)) {
					if (!xml_parse($p, fread($fp, 10240), false)) {
						fclose($fp);
						return $this->_error($p);
					}
				}
			}
			fclose($fp);
			if (!xml_parse($p, '', true))
				return $this->_error($p);
			unset($this->_cursor);
			unset($this->_cdata);
			if (xml_get_error_code($p) != XML_ERROR_NONE)
				return $this->_error($p);
			xml_parser_free($p);
			return true;
		}
		
		function close() {
		}
		
		function setStream($path) {
			$this->_streams[$path] = true;
		}
		
		function setConsumer($consumer) {
			$this->_consumer = $consumer;
		}
		
		function & selectNode($path, $lang = null) {
			$path = explode('/', $path);
			if (array_shift($path) != '') {
				$null = null;
				return $null;
			}
			$cursor = &$this->struct;
			while (is_array($cursor) && ($step = array_shift($path))) {
				$step = $this->expandNS($step);
				if (!preg_match('/^([^[]+)(\[(\d+|lang\(\))\])?$/', $step, $matches)) {
					$null = null;
					return $null;
				}
				$name = $matches[1];
				if (!isset($cursor[$name][0])) {
					$null = null;
					return $null;
				}
				
				if (count($matches) != 4) { // Node name only.
					if (isset($cursor[$name][0])) {
						$cursor = &$cursor[$name][0];
					} else {
						$null = null;
						return $null;
					}
				} else if ($matches[3] != 'lang()') { // Position.
					$index = $matches[3];
					$index--;

					if (isset($cursor[$name][$index])) {
						$cursor = &$cursor[$name][$index];
					} else {
						$null = null;
						return $null;
					}
				} else { // lang() expression.
					for ($i = 0; $i < count($cursor[$name]); $i++) {
						$lang = (isset($cursor[$name][$i]['.attributes']['xml:lang'])) ? $cursor[$name][$i]['.attributes']['xml:lang'] : '';
						switch (Locale::match($lang)) {
							case 3:
								$cursor = &$cursor[$name][$i];
								return $cursor;
							case 2:
								$secondBest = &$cursor[$name][$i];
								break;
							case 1:
								$thirdBest = &$cursor[$name][$i];
								break;
							case 0:
								if (!isset($thirdBest))
									$thirdBest = &$cursor[$name][$i];
								break;
						}
					}

					if (isset($secondBest)) {
						$cursor = &$secondBest;
					} else if (isset($thirdBest)) {
						$cursor = &$thirdBest;
					} else {
						$null = null;
						return $null;
					}
				}
			}
			return $cursor;
		}
		
		function & selectNodes($path) {
			$p = explode('/', $path);
			if (array_shift($p) != '') {
				$null = null;
				return $null;
			}
			$c = &$this->struct;
			
			while ($d = array_shift($p)) {
				$o = 0;
				if ($d{strlen($d) - 1} == ']') {
					@list($d, $o) = split('\[', $d, 2);
					if ($o === null) {
						$null = null;
						return $null;
					}
					$o = substr($o, 0, strlen($o) - 1);
					if (!is_numeric($o)) {
						$null = null;
						return $null;
					}

					$o--;
				}
				$d = $this->expandNS($d);
				if (empty($p)) {
					if (isset($c[$d])) {
						return $c[$d];
					} else {
						$null = null;
						return $null;
					}
				}
				if (isset($c[$d][$o]))
					$c = &$c[$d][$o];
				else
					break;
			}
			$null = null;
			return $null;
		}
		
		function doesExist($path) {
			return ($this->selectNode($path) !== null);
		}
		
		function getAttribute($path, $name, $default = null) {
			$n = &$this->selectNode($path);
			if (($n !== null) && isset($n['.attributes'][$name]))
				return $n['.attributes'][$name];
			else
				return $default;
		}

		function getValue($path) {
			$n = &$this->selectNode($path);
			return (isset($n['.value']) ? $n['.value'] : null);
		}
		
		function getNodeCount($path) {
			return count($this->selectNodes($path));
		}

		function o($p, $n, $a) {
			if (isset($a['http://www.w3.org/XML/1998/namespace:lang']))
				$a['xml:lang'] = $a['http://www.w3.org/XML/1998/namespace:lang'];
			if (!isset($this->_cursor[$n]))
				$this->_cursor[$n] = array();
			if (empty($a))
				$this->_cursor = &$this->_cursor[$n][array_push($this->_cursor[$n], array('.value' => '', '_' => &$this->_cursor)) - 1];
			else
				$this->_cursor = &$this->_cursor[$n][array_push($this->_cursor[$n], array('.attributes' => $a, '.value' => '', '_' => &$this->_cursor)) - 1];
			$this->_cdata = null;
			array_push($this->_path, $n);
			if (isset($this->_streams[implode('/', $this->_path)]))
				$this->_cursor['.stream'] = tmpfile();
		}

		function c($p, $n) {
			if (count($this->_cursor) != (2 + isset($this->_cursor['.attributes'])))
				unset($this->_cursor['.value']);
			else
				$this->_cursor['.value'] = rtrim($this->_cursor['.value']);
			$c = &$this->_cursor;
			$this->_cursor = &$this->_cursor['_'];
			unset($c['_']);
			if (isset($this->_consumer)) {
				if (call_user_func($this->_consumer, implode('/', $this->_path), $c, xml_get_current_line_number($p))) {
					if (count($this->_cursor[$n]) == 1)
						unset($this->_cursor[$n]);
					else
						array_pop($this->_cursor[$n]);
				}
			}
			array_pop($this->_path);
		}
		
		function d($p, $d) {
			if (count($this->_cursor) != (1 + isset($this->_cursor['.value']) + isset($this->_cursor['.attributes']) + isset($this->_cursor['.stream'])))
				return;
			if (!$this->_cdata) {
				if (isset($this->_cdata))
					$this->_cursor['.value'] = rtrim($this->_cursor['.value']);
				$this->_cdata = true;
				$d = ltrim($d);
			}
			if (strlen($d) == 0)
				return;
			if (empty($this->_cursor['.stream']))
				$this->_cursor['.value'] .= $d;
			else
				fwrite($this->_cursor['.stream'], $d);
		}
		
		function x($p, $d) {
			if ($d == '<![CDATA[')
				$this->_cdata = true;
			else if (($d == ']]>') && $this->_cdata)
				$this->_cdata = false;
		}
		
		function _error($p) {
			$this->error = array(
				'code' => xml_get_error_code($p),
				'offset' => xml_get_current_byte_index($p),
				'line' => xml_get_current_line_number($p),
				'column' => xml_get_current_column_number($p)
			);
			xml_parser_free($p);
			return false;
		}
	}
?>
