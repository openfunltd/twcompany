<?php

/**
 * Pix_Partial 為了讓 PHP 讓頁面更簡潔，參數使用比更靈活
 *
 * @package Partial
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Partial
{
    public $_data = array();
    public $_path = '.';
    public $_escape = 'htmlspecialchars';
    public $_cache_prefix = '';
    public $alert_messages = array();

    /**
     * __construct 建出一個 Pix_Partial ，並且將路徑設定成 $path
     *
     * @param string|null $path
     * @param array $options cache_prefix cache_id 要加入的字串
     * @access public
     * @return void
     */
    public function __construct($path = null, $options = array())
    {
	if ($path) {
	    $this->setPath($path);
        } else {
            $this->setPath($this->_path);
        }

        if ($options['cache_prefix']) {
            $this->_cache_prefix = $options['cache_prefix'];
        }
    }

    public function __get($k)
    {
	return $this->_data[$k];
    }

    public function __set($k, $v)
    {
	$this->_data[$k] = $v;
    }

    public function __isset($k)
    {
	return isset($this->_data[$k]);
    }

    protected static $_trim_mode = false;
    protected static $_comment_mode = false;
    protected static $_nocache = false;
    protected static $_write_only_mode = false;

    public static function setCacheWriteOnlyMode($write_only)
    {
        self::$_write_only_mode = $write_only;
    }

    /**
     * setTrimMode 是否要啟動 Trim mode ，預設把每一行的前後空白拿掉
     *
     * @param boolean $trim_mode
     * @static
     * @access public
     * @return void
     */
    public static function setTrimMode($trim_mode = false)
    {
	self::$_trim_mode = $trim_mode;
    }

    /**
     * getTrimMode 取得 Trim Mode 是否開啟
     *
     * @static
     * @access public
     * @return boolean
     */
    public static function getTrimMode()
    {
	return self::$_trim_mode;
    }

    /**
     * setNoCache 是否不啟動 cache (預設啟動)
     *
     * @param boolean $nocache
     * @static
     * @access public
     * @return void
     */
    public static function setNoCache($nocache = false)
    {
	self::$_nocache = $nocache;
    }

    /**
     * getNoCache 取得 nocache 是否開啟
     *
     * @static
     * @access public
     * @return boolean
     */
    public static function getNoCache()
    {
	return self::$_nocache;
    }

    /**
     * setCommentMode 註解模式，會在 partial 前面顯示他是哪個 partial
     *
     * @param mixed $comment_mode
     * @static
     * @access public
     * @return void
     */
    public static function setCommentMode($comment_mode = false)
    {
	self::$_comment_mode = $comment_mode;
    }

    public static function getCommentMode()
    {
	return self::$_comment_mode;
    }

    /**
     * partial 印出 $file 的內容，並且將 $data 的參數轉成 $this
     *
     * @param string $file
     * @param array|Pix_Partial|null $data 傳進去的參數
     * @param array|string $options 設定 或 cache id
     * @access public
     * @return string
     */
    public function partial($file, $data = null, $options = null)
    {
	if ($data instanceof Pix_Partial) {
            $data = $data->_data;
	} else {
            $data = (array) $data;
	}

	if (array_key_exists('_data', $data)) {
	    $data = array_shift($data);
	}

        if ($this->_path) {
            $path = $this->_path . ltrim($file, '/');
        } else {
            $path = $file;
        }

        if (!is_null($options) and is_scalar($options)) {
            // $options 是 scalar 的話, 就是 cache id
            $cache_id = $options;
        } elseif (is_array($options)) {
            // 是 array 的話, 就從 array 取出設定
            $cache_id = $options['cache_id'];
            $cache = $options['cache'];
        }

        if (!($cache instanceof Pix_Cache)) {
            $cache = new Pix_Cache();
        }

        $cache_key = sprintf('Pix_Partial:%s:%s:%s:%d',
                $this->_cache_prefix,
                sha1(file_get_contents($path)),
                $cache_id,
                self::$_trim_mode ? 1 : 0);
        if (!self::$_nocache and !self::$_write_only_mode and strlen($cache_id) > 0 and $html = $cache->load($cache_key)) {
            return $html;
        }

	// TODO: 這邊要改漂亮一點的寫法
	$old_data = $this->_data;
	$this->_data = $data;

	ob_start();

	try {
	    if (!file_exists($path)) {
		throw new Exception("找不到 {$path} 這個路徑的 partial");
	    }
	    if (preg_match('#.tmpl$#', $path)) {
		$this->jQueryTmpl($path, $this);
	    } else {
		require($path);
	    }
	    $str = ob_get_clean();
	} catch (Pix_Partial_NoRender $e) {
	    ob_get_clean();
	    $str = '';
	} catch (Pix_Partial_BreakRender $e) {
	    $str = ob_get_clean();
	} catch (Exception $e) {
	    ob_get_clean();
	    throw $e;
	}

	if (self::getCommentMode() and $str) {
	    $str = "<!-- Pix_Partial START {$file} -->\n{$str}\n<!-- Pix_Partial END {$file} -->\n";
	}

	$this->_data = $old_data;
	if (self::$_trim_mode) {
	    $newstr = '';
	    foreach (explode("\n", $str) as $line) {
		$newstr .= trim($line) . "\n";
	    }
	    $str = trim($newstr);
	}

        if (!self::$_nocache and strlen($cache_id) > 0) {
            $cache->save($cache_key, $str);
        }

	return $str;
    }

    /**
     * noRender 在 partial 內呼叫 function ，呼叫此 function 時，整個 partial 前面已經生成的部分會被捨棄。
     *
     * @access public
     * @return void
     */
    public function noRender()
    {
	throw new Pix_Partial_NoRender();
    }

    /**
     * breakRender 在 partial 內呼叫 function ，呼叫此 function 時，整個 partial 前面已經生成的部分會照常被印出。
     *
     * @access public
     * @return void
     */
    public function breakRender()
    {
	throw new Pix_Partial_BreakRender();
    }

    /**
     * setPath 將 Partial 自動讀取路徑設定為 $path
     *
     * @param string $path
     * @access public
     * @return void
     */
    public function setPath($path)
    {
	$this->_path = rtrim($path, '/') . '/';
    }

    /**
     * getPath 取出 Partial 的 path
     *
     * @access public
     * @return void
     */
    public function getPath()
    {
	return $this->_path;
    }

    public function escape($var)
    {
	return call_user_func($this->_escape, $var);
    }

    /**
     * addCommonHelpers add common helpers
     *
     * @static
     * @access public
     * @return void
     */
    public static function addCommonHelpers()
    {
	self::addHelper('Pix_Partial_Helper_Html');
        self::addHelper('Pix_Partial_Helper_Capture');
	self::addHelper('Pix_Partial_Helper_JQueryTmpl');
    }

    /**
     *  Pix_Helper_Manager
     */
    protected static $_helper_manager = null;

    /**
     * getHelperManager get Helper Manager
     *
     * @static
     * @access public
     * @return Pix_Helper_Manager
     */
    public static function getHelperManager()
    {
        if (is_null(self::$_helper_manager)) {
            self::$_helper_manager = new Pix_Helper_Manager();
        }
        return self::$_helper_manager;
    }

    /**
     * addHelper add static helper in Pix_Controller
     *
     * @param string $helper Helper name
     * @param array $methods
     * @param array $options
     * @static
     * @access public
     * @return void
     */
    public static function addHelper($helper, $methods = null, $options = array())
    {
        $manager = self::getHelperManager();
        $manager->addHelper($helper, $methods, $options);
    }

    public function __call($func, $args)
    {
        array_unshift($args, $this);
        return self::getHelperManager()->callHelper($func, $args);
    }
}
