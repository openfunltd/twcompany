<?php

/**
 * Pix_Setting 這邊放一些 Pix Framework 可能可以大量用到的設定，Ex: proxy ...
 * 
 * @package Setting
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Setting
{
    static protected $_settings = array();

    /**
     * set 設定一個給 Pix Framework 用的 global 變數
     *
     * @param string $key 
     * @param string $value 
     * @static
     * @access public
     * @return void
     */
    static public function set($key, $value)
    {
	self::$_settings[$key] = $value;
    }

    /**
     * get 取得一個給 Pix Framework 的 global 變數
     *
     * @param string $key 
     * @static
     * @access public
     * @return string|null
     */
    static public function get($key)
    {
        if (array_key_exists($key, self::$_settings)) {
            return self::$_settings[$key];
        }
        return null;
    }
}
