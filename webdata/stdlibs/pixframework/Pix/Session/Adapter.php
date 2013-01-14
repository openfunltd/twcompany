<?php

/**
 * Pix_Session_Adapter
 *
 * @abstract
 * @package Session
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
abstract class Pix_Session_Adapter
{
    public static function loadAdapter($adapter, $config)
    {
        if (class_exists('Pix_Session_Adapter_' . ucfirst($adapter))) {
            $adapter = 'Pix_Session_Adapter_' . $adapter;
        }

        if (!class_exists($adapter)) {
            throw new Pix_Exception("Adapter $adapter not found");
        }

        return new $adapter($config);
    }

    abstract public function set($key, $value);
    abstract public function get($key);
    abstract public function delete($key);
    abstract public function clear();

    protected $_options = array();

    public function __construct($options = array())
    {
        $this->_options = $options;
    }

    public function getOption($key, $options = array())
    {
        if (isset($options[$key])) {
            return $options[$key];
        }
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }
        return Pix_Session::getOption($key);
    }

    public function hasOption($key)
    {
        return isset($this->_options[$key]);
    }

    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }
}
