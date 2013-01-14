<?php

/**
 * Pix_Cache_Adapter_HandlerSocket
 *
 * @uses Pix_Cache_Adapter
 * @package Cache
 * @version $id$
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Cache_Adapter_HandlerSocket extends Pix_Cache_Adapter
{
    protected $_hs = array();
    protected $_config = null;

    public function __construct($config)
    {
	$this->_config = $config;
    }

    public function getSocket($type = 'read')
    {
	$type = in_array($type, array('read', 'write')) ? $type : 'read';

	if (is_null($this->_hs[$type])) {
	    $config = $this->_config;
	    if (!is_array($config)) {
		throw new Pix_Exception('config error');
	    }

	    $hs = new HandlerSocket($config[$type]['host'], $config[$type]['port']);
	    if ('write' == $type) {
		if (!$hs->openIndex(1, $config[$type]['dbname'], $config[$type]['table'], '', 'value')) {
		    throw new Exception($hs->getError());
		}
	    } else {
		if (!$hs->openIndex(1, $config[$type]['dbname'], $config[$type]['table'], HandlerSocket::PRIMARY, 'key,value')) {
		    throw new Exception($hs->getError());
		}
	    }
	    $this->_hs[$type] = $hs;
	}
	return $this->_hs[$type];
    }

    protected function _getOptions($options)
    {
	$ret = array();
	$ret['expire'] = is_int($options) ? $options : (isset($options['expire']) ? $options['expire'] : 3600);
	$ret['compress'] = isset($options['compress']) ? $options['compress'] : false;
	return $ret;
    }

    public function add($key, $value, $options = array())
    {
	$this->set($key, $value);
    }

    public function set($key, $value, $options = array())
    {
	$hs = $this->getSocket('write');
	if (!$hs->executeInsert(1, array($key, $value))) {
	    $hs->executeUpdate(1, '=', array($key), array($value), 1, 0);
	}
    }

    public function delete($key)
    {
	$hs = $this->getSocket('write');
	if (!$hs->executeDelete(1, '=', array($key))) {
	    return 0;
	}
	return 1;
    }

    public function replace($key, $value, $options = array())
    {
	$this->set($key, $value);
    }

    public function inc($key, $inc = 1)
    {
	throw new Exception('not implemented');
    }

    public function dec($key, $inc = 1)
    {
	throw new Exception('not implemented');
    }

    public function get($key)
    {
	$hs = $this->getSocket();
	$retval = $hs->executeSingle(1, '=', array($key), 1, 0);

	return ($retval[0][1]);
    }

    /*public function gets(array $keys)
    {
	$memcache = $this->getMemcache();
	return $memcache->get($keys);
    } */
}
