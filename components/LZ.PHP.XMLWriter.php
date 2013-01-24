<?php
	/*
		usage : 
		$xml = new XMLFile('filename.xml');
		$xml->startGroup('rss', array('version'=>'2.0'));
		$xml->startGroup('channel');
		$xml->write('title', 'this is rss title');

		$xml->startGroup('item');
		$xml->write('title', 'this is item title');
		$xml->endGroup();

		$xml->endAllGroups();
		$xml->close();
	*/
	define('CRLF', "\r\n");

	class XMLFile {
			var $filename, $fp, $buffer, $recentGroup = array();
	
			function XMLFile($filename) {
				$this->clear();
				if (!isset($filename)) return false;
				if (!$this->open($filename)) return false;

				$this->filename = $filename;
				$this->makeResult('<?xml version="1.0" encoding="utf-8" ?>');
				return true;
			}

			function open($filename) {
				$this->filename = $filename;
				if (strtolower($filename) == 'stdout')  return true;
				if (!function_exists('fopen')) return false;

				if (!$this->fp = fopen($filename, 'w')) 
					return false;

				return true;
			}

			function clear() {
				$this->buffer = '';
				$this->recentGroup = array();
			}

			function makeResult($str) {
				$this->buffer .= UTF8::correct($str).CRLF;
			}

			function write($elementName, $detail, $isCDATA = false, $properties = null) {
				if (!isset($elementName)) 
					return false;

				$elementPA = '';
				if (is_array($properties)) {
					$elementPA = ' ';
					foreach($properties as $key=>$value) {
						$elementPA .= $key.'="'.htmlspecialchars($value).'" ';
					}
				}

				if (!Validator::is_empty($detail)) {
					if ($isCDATA) $detail = '<![CDATA[ '.$detail.' ]]>';
					$this->makeResult('<'.$elementName.$elementPA.'>'.$detail.'</'.$elementName.'>');
				} else {
					$this->makeResult('<'.$elementName.$elementPA.' />');
				}
			}

			function startGroup($groupName, $properties = null) {
				$groupPA = '';
				if (is_array($properties)) {
					foreach($properties as $key=>$value) {
						$groupPA .= ' '.$key.'="'.$value.'"';
					}
				}
				array_push($this->recentGroup, $groupName);
				$this->makeResult('<'.$groupName.$groupPA.'>');
			}

			function endGroup() {
				$groupName = (!func_num_args()) ? array_pop($this->recentGroup) : func_get_arg(0);
				$this->makeResult('</'.$groupName.'>');
			}

			function endGroups($groups = null) {
				if (!isset($groups)) return;
				if (!is_array($groups)) 
					$groups = explode(',', $groups);
				foreach ($groups as $groupName) { 
					$this->makeResult('</'.$groupName.'>');
				}
			}

			function endAllGroups() {
				$n = count($this->recentGroup);
				for ($i=0; $i < $n; $i++)
					$this->endGroup();
			}

			function flush() {
				if (strtolower($this->filename) == 'stdout') {
					echo $this->buffer;
				} else {
					fwrite($this->fp, $this->buffer);
				}
				$this->buffer = '';
			}

			function close() {
				$this->endAllGroups();
				$this->flush();

				if (strtolower($this->filename) == 'stdout') {
					return true;
				} else {
					$closed = fclose($this->fp);
					@chmod($this->filename, 0664);
					return $closed;
				}
			}
	}
?>