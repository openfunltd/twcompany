<?php

/**
 * Pix_Session
 *
 * @package Session
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Session
{
    protected static $_obj = null;
    protected static $_adapter = 'default';
    protected static $_adapter_options = array();

    public static function setAdapter($adapter, $options = array())
    {
        self::$_adapter = $adapter;
        self::$_adapter_options = $options;
    }

    public function __construct()
    {
	self::getObject();
    }

    protected static function getObject()
    {
	if (!is_null(self::$_obj)) {
	    return self::$_obj;
	}
	return self::$_obj = Pix_Session_Adapter::loadAdapter(self::$_adapter, self::$_adapter_options);
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

    public static function __callStatic($name, $args)
    {
	return self::__call($name, $args);
    }

    public function __call($name, $args)
    {
        array_unshift($args, $this);
        return self::getHelperManager()->callHelper($name, $args);
    }

    public static function get($key)
    {
	$obj = self::getObject();
	return $obj->get($key);
    }

    public static function set($key, $value)
    {
	$obj = self::getObject();
	return $obj->set($key, $value);
    }

    public static function delete($key)
    {
	$obj = self::getObject();
	return $obj->delete($key);
    }

    public static function clear()
    {
	$obj = self::getObject();
	return $obj->clear();
    }

    public static function setOption($key, $value)
    {
	$obj = self::getObject();
	return $obj->setOption($key, $value);
    }

    public static function getOption($key)
    {
	$obj = self::getObject();
	return $obj->getOption($key);
    }

    public function __get($key)
    {
	return self::get($key);
    }

    public function __set($key, $value)
    {
	return self::set($key, $value);
    }

    public function __unset($key)
    {
	return self::delete($key);
    }
}
