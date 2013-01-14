<?php

/**
 * Pix_Prompt 增加提示字元操作的功能
 *     用法: Pix_Prompt::init($loading_paths);
 * 
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Prompt
{
    static public $_often;
    static public $_walked_prefix = array();
    static public $_paths = array();
    static public $__last;
    static public $__l;
    static public $__history_path;
    static public $_vars;

    static protected function _supportedReadline()
    {
        return function_exists('readline');
    }

    static protected function _readline($prompt)
    {
        if (self::_supportedReadline()) {
            return readline($prompt);
        } else {
            $fp = fopen('php://stdin', 'r');
            echo $prompt;
            $line = fgets($fp);
            fclose($fp);
            return $line;
        }
    }

    static public function init($paths = null, $history_path = null)
    {
        self::$_often = get_defined_functions();
        self::$_often = array_merge(self::$_often['internal'], get_declared_classes());
        if (is_null($paths)) {
            $paths = explode(PATH_SEPARATOR, get_include_path());
        }
        self::$_paths = $paths;

        if (self::_supportedReadline()) {
            readline_completion_function(array(__CLASS__, 'autocomplete'));
            self::$__last = null;

            if (is_null($history_path)) {
                if ($home = getenv('HOME')) {
                    $history_path = $home . '/.pprompt_history';
                }
            }

            if (self::$__history_path = $history_path){
                readline_read_history(self::$__history_path);
            }
        }

        unset($paths);
        unset($history_path);
        unset($home);

        while(self::$__l = self::_readline(">> ")) {
            if (self::_supportedReadline()) {
                if (is_null(self::$__last) or self::$__l != self::$__last) {
                    readline_add_history(self::$__l);
                }
                if (self::$__history_path) {
                    readline_write_history(self::$__history_path);
                }
            }
            try {
                eval(self::$__l . ";");
                echo "\n";
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
                echo $e->getTraceAsString() . "\n";
            }
            self::$_vars = get_defined_vars();
            self::$__last = self::$__l;
        }
    }

    static public function findMatch($m)
    {
        $terms = explode('_', $m);
        array_pop($terms);
        $prefix = implode('_', $terms);
        self::addOften($prefix);

        $ret = array();
        foreach (self::$_often as $name) {
            if ('' === trim($m) or strpos($name, $m) === 0) {
                $ret[] = $name;
            }
        }
        return $ret;
    }

    static public function addOften($prefix = '')
    {
        if (self::$_walked_prefix[$prefix]) {
            return;
        }
        self::$_walked_prefix[$prefix] = true;

        foreach (self::$_paths as $path) {
            $dir = rtrim($path, '/') . '/' . str_replace('_', '/', $prefix) . '/';
            if (!file_exists($dir) or !is_dir($dir)) {
                continue;
            }

            $d = opendir($dir);
            while ($f = readdir($d)) {
                if (strpos($f, '.') === 0) {
                    continue;
                }

                if (!is_file($dir . $f)) {
                    continue;
                }
                if (preg_match('#^(.*)\.php$#', $f, $matches)) {
                    self::$_often[] = ltrim($prefix . '_' . $matches[1], '_') . '::';
                }
            }
        }
    }

    public function autocomplete($name, $args2, $args3)
    {
        $res = array();
        if (preg_match('#^(.*)::(.*)$#', $name, $matches)) {
            $methods = get_class_methods($matches[1]);

            foreach ($methods as $m) {
                if ($matches[2] === '' or strpos($m, $matches[2]) === 0) {
                    $res[] = $matches[1] . '::' . $m . '()';
                }
            }
        } elseif (preg_match('#\$([^$]*)->([^>]*)$#', readline_info('line_buffer'), $matches)) {
            $obj = self::$_vars[$matches[1]];
            if (!is_object($obj) or ($class = get_class($obj)) === false) {
                return null;
            }

            if (is_a($obj, 'Pix_Table_Row')) {
                // columns
                foreach (array_keys($obj->getTable()->_columns) as $m) {
                    if (($matches[2] === '') or (strpos($m, $matches[2]) === 0)) {
                        $res[] = $m;
                    }
                }

                // relations
                foreach (array_keys($obj->getTable()->_relations) as $m) {
                    if (($matches[2] === '') or (strpos($m, $matches[2]) === 0)) {
                        $res[] = $m;
                    }
                }

                // aliases
                if ($obj->getTable()->_aliases) {
                    foreach (array_keys($obj->getTable()->_aliases) as $m) {
                        if (($matches[2] === '') or (strpos($m, $matches[2]) === 0)) {
                            $res[] = $m;
                        }
                    }
                }

                // hook
                if ($obj->getTable()->_hooks) {
                    foreach (array_keys($obj->getTable()->_hooks) as $name) {
                        if (($matches[2] === '') or (stripos($name, $matches[2]) === 0)) {
                            $res[] = $name;
                        }
                    }
                }

                // Table Row Helper
                foreach (array_keys($obj->getTable()->getHelperManager('row')->getMethods()) as $name) {
                    if (($matches[2] === '') or (stripos($name, $matches[2]) === 0)) {
                        $res[] = $name . '()';
                    }
                }

                // Table Static Helper
                foreach (array_keys(Pix_Table::getStaticHelperManager('row')->getMethods()) as $name) {
                    if (($matches[2] === '') or (stripos($name, $matches[2]) === 0)) {
                        $res[] = $name . '()';
                    }
                }
            }

            $methods = get_class_methods($class);
            foreach ($methods as $m) {
                if (($matches[2] === '') or (strpos($m, $matches[2]) === 0)) {
                    $res[] = $m . '()';
                }
            }

        } else {
            foreach (self::findMatch($name) as $o) {
                $res[] = $o;
            }
        }

        if (count($res) == 0) {
            return null;
        }
        return $res;
    }
}
