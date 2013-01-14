<?php

/**
 * Pix_Cache 
 * 
 * @package Cache
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Cache
{
    protected $_id = 0;

    public function __construct($id = 0)
    {
	$this->_id = $id;
    }

    public function __call($func, $args)
    {
	if (!self::$_servers[$this->_id]) {
	    trigger_error('你應該要先做 Pix_Cache::addServer()', E_USER_WARNING);
	    return null;
	}
	$ret = call_user_func_array(array(self::$_servers[$this->_id], $func), $args);
	return $ret;
    }
    

    protected static $_servers = array();

    /**
     * addServer 增加 Cache Server 設定。
     * 
     * @param mixed $adapter Pix_Cache 使用的 Adapter 的 class name (Ex: Pix_Cache_Adapter_Memcache)
     * @param array $conf  
     * @param int $id 
     * @static
     * @access public
     * @return void
     */
    public static function addServer($adapter, $conf = array(), $id = 0)
    {
	if (!class_exists($adapter)) {
	    throw new Pix_Exception('Class not found');
	}

	$server = new $adapter($conf);
	if (!is_a($server, 'Pix_Cache_Core') and !is_a($server, 'Pix_Cache_Adapter')) {
	    throw new Pix_Exception("$adapter is not a Pix_Cache_Adapter");
	}
	self::$_servers[$id] = $server;
    }

    public static function reset()
    {
	self::$_servers = array();
    }
}
