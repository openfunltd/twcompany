<?php

/**
 * Pix_Partial_Helper_JQueryTmpl
 *
 * @package Partial
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Partial_Helper_JQueryTmpl extends Pix_Helper
{
    public static function getFuncs()
    {
        return array('jquerytmpl', 'addjquerytmpl');
    }

    protected function _getTokens($tmpl)
    {
	preg_match_all('#({{.*?}}|\${.*?})#', $tmpl, $matches, PREG_OFFSET_CAPTURE);
	$tokens = array();
	$pointer = 0;
	$last_statement = 'text';
	$stack = array();
	$depth = 0;

	foreach ($matches[0] as $match) {
	    $tag = $match[0];
	    $offset = $match[1];

	    // 純文字部分
	    $token = array();
	    $token['text'] = substr($tmpl, $pointer, $offset - $pointer);
	    $token['type'] = 'text';
	    $token['depth'] = count($stack);
	    $tokens[] = $token;

	    // jQuery Tmpl tag 部分
	    $token = array();
	    $token['text'] = $tag;
	    if (preg_match('#^\${(.*)}$#', $tag, $m)) {
		$token['type'] = 'variable';
		$token['variable'] = $m[1];
	    } elseif (preg_match('#^{{(.*)}}$#', $tag, $m)) {
		list($command, $options) = explode(' ', trim($m[1]), 2);
		$token['type'] = $command;

		if ('if' == $command) {
		    array_push($stack, count($tokens));
		    $token['variable'] = trim($options);
		} elseif ('else' == $command) {
		    $top = array_pop($stack);
		    array_push($stack, $top);

		    if ($tokens[$top]['type'] != 'if') {
			throw new Exception("Parse error in offset {$offset}");
		    }
		    $tokens[$top]['else'] = count($tokens);
		} elseif ('/if' == $command) {
		    $top = array_pop($stack);
		    array_push($stack, $top);

		    if ($tokens[$top]['type'] != 'if') {
			throw new Exception("Parse error in offset {$offset}");
		    }
		    $tokens[$top]['endif'] = count($tokens);
		    array_pop($stack);
		} elseif ('each' == $command) {
		    array_push($stack, count($tokens));
		    if (preg_match('#^\((.*),(.*)\) (.*)$#', trim($options), $matches)) {
			$token['variable'] = trim($matches[3]);
			$token['each_index'] = trim($matches[1]);
			$token['each_value'] = trim($matches[2]);
		    } else {
			$token['variable'] = trim($options);
			$token['each_index'] = 'index';
			$token['each_value'] = 'value';
		    }
		} elseif ('/each' == $command) { 
		    $top = array_pop($stack);
		    array_push($stack, $top);

		    if ($tokens[$top]['type'] != 'each') {
			throw new Exception("Parse error in offset {$offset}");
		    }

		    $tokens[$top]['match'] = count($tokens);
		    array_pop($stack);
		} elseif ('html' == $command) {
		    // TODO
		} elseif ('tmpl' == $command) {
		    // TODO
		} elseif ('wrap' == $command) {
		    // TODO
		} elseif ('/wrap' == $command) {
		    // TODO
		}
	    } else {
		throw new Exception("Parse tmpl failed on offset {$offset}");
	    }

	    $token['depth'] = count($stack);
	    $pointer = $offset + strlen($tag);
	    $tokens[] = $token;
	}

	if ($stack) {
	    throw new Exception("Parse tmpl failed on offset {$offset}");
	}
	// 剩下的部分
	$token = array();
	$token['text'] = substr($tmpl, $pointer);
	$token['type'] = 'text';
	$token['depth'] = count($stack);
	$tokens[] = $token;

	return $tokens;
    }

    protected function _printVariable($data, $var)
    {
	$t = $data;
	foreach (explode('.', $var) as $term) {
	    if (is_array($t)) {
		$t = $t[$term];
	    } else {
		$t = $t->{$term};
	    }
	}
        return htmlspecialchars($t);
    }

    protected function _walkToken($tokens, $start, $end, $data)
    {
	for ($i = $start; $i < $end; $i ++) {
	    $token = $tokens[$i];
	    if ($token['type'] == 'text') { 
		echo $token['text'];
		continue;
	    }

	    if ($token['type'] == 'variable') {
		echo $this->_printVariable($data, $token['variable']);
		continue;
	    }

	    if ($token['type'] == 'each') {
		foreach ($data->{$token['variable']} as $index => $value) { 
		    $new_data = clone $data;
		    $new_data->{$token['each_index']} = $index;
		    $new_data->{$token['each_value']} = $value;
		    $this->_walkToken($tokens, $i + 1, $token['match'], $new_data);
		    unset($new_data);
		}
		$i = $token['match'];
		continue;
	    }
	}
    }

    public function jQueryTmpl($me, $path, $data)
    {
        $content = file_get_contents($me->getPath() . '/' . $path);
	$tokens = $this->_getTokens($content);

	$this->_walkToken($tokens, 0, count($tokens), $data);
    }

    public function addJQueryTmpl($me, $path, $id)
    {
	if ($me->getPath()) {
            $path = $me->getPath() . '/' . ltrim($path, '/');
	} else {
	    $path = $path;
	}
	ob_start();
        echo '<script id="', htmlspecialchars($id), '" type="text/html">';
        echo file_get_contents($path);
	echo '</script>';
	return ob_get_clean();

    }
}
