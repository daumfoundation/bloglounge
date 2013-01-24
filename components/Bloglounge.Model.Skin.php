<?php

	Class Skin {
		var $output;
		var $url;

		function load($skinname) {
			global $service;

			$this->url = "{$service['path']}/skin/$skinname/";

			$this->output = file_get_contents(ROOT. "/skin/$skinname/skin.html");			
			$this->output = str_replace('./', $this->url , $this->output);

		}

		function cutSkinTag($tag, $contents = null) {
			if (!isset($contents))  $contents = $this->output;
			$tagSize = strlen($tag) + 4;
			$begin = strpos($contents, "<s_$tag>");
			if ($begin === false)
				return null;
			$end = strpos($contents, "</s_$tag>", $begin + 4);
			if ($end === false)
				return null;
			$inner = substr($contents, $begin + $tagSize, $end - $begin - $tagSize);
			return $inner;
		}

		function css($content) {
			$this->output = str_replace('</head>', $content . '</head>', $this->output);
		}

		function javascript($content) {
			$this->output = str_replace('</head>', $content . '</head>', $this->output);
		}

		function addJavascriptCode($code) {
			$this->output = str_replace('[##_bloglounge_autoinput_script_##]', $code . "\n[##_bloglounge_autoinput_script_##]", $this->output);
		}

		function replace($tag, $value) {
			$this->output = str_replace('[##_'.$tag.'_##]', $value, $this->output);
		}

		function dress($tag, $str, $content = null) {
				$this->output = str_replace('<s_'.$tag.'>'.$this->cutSkinTag($tag).'</s_'.$tag.'>', $str, $this->output);
		}

		function dressOut($tag, $str, $content) {			
			return str_replace('<s_'.$tag.'>'.$this->cutSkinTag($tag).'</s_'.$tag.'>', $str, $content);
		}

		function dressOn($tag, $key, $replacement, $haystack) {
			return str_replace('<s_'.$tag.'>'.$key.'</s_'.$tag.'>', $replacement, $haystack);
		}

		function parseTag($tag, $value, $haystack) {
			return str_replace('[##_'.$tag.'_##]', $value, $haystack);
		}

		function dressTag($tag, $value, $haystack) {
			return str_replace('<s_'.$tag.'>'.$this->cutSkinTag($tag).'</s_'.$tag.'>', $value, $haystack);
		}

		function parseTagWithCondition($tag, $condition, $baseReplacement, $haystack, $globalReplacement = null) {
			$str = $haystack;
			preg_match_all("/\[##_{$tag}(.[^_##]+)*_##\]/i", $haystack, $matches);
			for ($i=0, $n = count($matches[0]); $i < $n; $i++) {
				if (!isset($globalReplacement)) {
					if (!empty($matches[1][$i])) {
							$options = array();
							$options = array_slice(explode('|', $matches[1][$i]), 1);
							$m = $baseReplacement.(($condition)?$options[0]:$options[1]);
					} else {
							$m = $baseReplacement;
					}
				} else {
					$m = $globalReplacement;
				}
				$str = str_replace($matches[0][$i], $m, $str);
			}
			return $str;
		}

		function parseTagWithArgument($tag, $callback, $arguments, $haystack, $defaultOption = 'null') {
			$str = $haystack;
			preg_match_all("/\[##_{$tag}(.[^_##]+)*_##\]/i", $haystack, $matches);
			for ($i=0, $n = count($matches[0]); $i < $n; $i++) {
				if (!empty($matches[1][$i])) {
					$options = array();
					$options = array_slice(explode(':', $matches[1][$i]), 1);
					for ($j=0, $m = count($options); $j < $m; $j++) {
						$arguments = str_replace('#'.($j+1), $options[$j], $arguments);
					}
					$result = call_user_func_array($callback, explode(',', $arguments));
				} else { // No Arguments
					$result = call_user_func_array($callback, $defaultOption);
				}
				$str = str_replace($matches[0][$i], $result, $str);
			}
			return $str;
		}

		function flush() {	
			echo $this->output;
			$this->output = '';
		}

		function doesScopeExists($s_tag) {
			return (strpos($this->output, '<s_'.$s_tag.'>') !== false) ? true: false;
		}

		function removeScope($s_tag) {
			foreach (explode(',', $s_tag) as $tag) {
				$this->dress($tag, '');
			}
		}
		
		function removePageScopeExcept($s_tag, $removeScopeTag = true) { // _page_ scope only
			$this->_removeScopeException = explode(',', $s_tag);
			$this->output = preg_replace_callback("/<s_page_(.[^>]+)>.*<\/s_page_.[^>]+>/Us", array($this, '_removeScopeExcept_callback'), $this->output);	
	
			if ($removeScopeTag) {
				foreach ($this->_removeScopeException as $targetTag) {
					$this->output = str_replace('<s_page_'.$targetTag.'>', '', $this->output);
					$this->output = str_replace('</s_page_'.$targetTag.'>', '', $this->output);
				}
			}
		}

		function _removeScopeExcept_callback($matches) {	
			if (!isset($this->_removeScopeException) || empty($this->_removeScopeException))
				return $matches[0];
			return (in_array($matches[1], $this->_removeScopeException)) ? $matches[0] : '';
		}

		function clearScopes() { // remove all remain skin scopes
			$this->output = preg_replace('@<(s_[0-9a-zA-Z_]+)>.*?</\1>@s', '', $this->output);
		}

		function clearScopeTags() { // remove remain skin scope tags
			$this->output = preg_replace("/<s_(.[^>]+)>|<\/s_.[^>]+>/Us", '', $this->output);
		}

		function clearSkinTags() { // ncloud : remove all skin tags
			$this->output = preg_replace('/\[#M_[^|]*\|[^|]*\|/Us', '', str_replace('_M#]', '', preg_replace('/\[##_.+_##\]/Us', '', $this->output)));
		}
	}
?>