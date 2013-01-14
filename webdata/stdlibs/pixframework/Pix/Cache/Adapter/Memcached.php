<?php

/**
 * Pix_Cache_Adapter_Memcached
 *
 * @uses Pix_Cache_Adapter
 * @package Cache
 * @version $id$
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Cache_Adapter_Memcached extends Pix_Cache_Adapter
{
    protected $_memcache = null;
    protected $_servers = null;
    protected $_default_expire = 3600;
    protected $_default_compress = false;

    public function __construct($config)
    {
        if (isset($config['servers'])) { // array('servers' => array( ... ), ...) 格式
            $this->_servers = $config['servers'];
        } elseif (is_array($config[0])) { // array(array('host' => ...), array('host' => ...) ... )
            $this->_servers = $config;
        } else { // array('host' => ...)
            $this->_servers = array($config);
        }

        if (isset($config['options'])) {
            if (isset($config['options']['expire'])) {
                $this->_default_expire = $config['options']['expire'];
            }
            if (isset($config['options']['compress'])) {
                $this->_default_compress = $config['options']['compress'];
            }
        }
    }

    public function getMemcache()
    {
	if (is_null($this->_memcache)) {
	    $servers = $this->_servers;
	    if (!is_array($servers)) {
		throw new Pix_Exception('config error');
	    }

	    $this->_memcache = new Memcached;
	    foreach ($servers as $server) {
		$this->_memcache->addServer(
		    $server['host'],
		    $server['port'],
		    isset($server['weight']) ? intval($server['weight']) : 1
		);
	    }
	}
	return $this->_memcache;
    }

    protected function _getOptions($options)
    {
	$ret = array();
        $ret['expire'] = is_int($options) ? $options : (isset($options['expire']) ? $options['expire'] : $this->_default_expire);
        $ret['compress'] = isset($options['compress']) ? $options['compress'] : $this->_default_compress;
	return $ret;
    }

    public function add($key, $value, $options = array())
    {
	$memcache = $this->getMemcache();
	$options = $this->_getOptions($options);
        $memcache->setOption(Memcached::OPT_COMPRESSION, $options['compress'] ? true : false);
	return $memcache->add($key, $value, $options['expire']);
    }

    public function set($key, $value, $options = array())
    {
	$memcache = $this->getMemcache();
	$options = $this->_getOptions($options);
        $memcache->setOption(Memcached::OPT_COMPRESSION, $options['compress'] ? true : false);
        return $memcache->set($key, $value, $options['expire']);
    }

    public function delete($key)
    {
	$memcache = $this->getMemcache();
	return $memcache->delete($key, 0);
    }

    public function replace($key, $value, $options = array())
    {
	$memcache = $this->getMemcache();
	$options = $this->_getOptions($options);
        $memcache->setOption(Memcached::OPT_COMPRESSION, $options['compress'] ? true : false);
	return $memcache->replace($key, $value, $options['expire']);
    }

    public function inc($key, $inc = 1)
    {
	$memcache = $this->getMemcache();
	return $memcache->increment($key, $inc);
    }

    public function dec($key, $inc = 1)
    {
	$memcache = $this->getMemcache();
	return $memcache->decrement($key, $inc);
    }

    public function append($key, $data, $options = array())
    {
	$memcache = $this->getMemcache();
	$options = $this->_getOptions($options);
        return $memcache->append($key, $data);
    }

    public function prepend($key, $data, $options = array())
    {
	$memcache = $this->getMemcache();
	$options = $this->_getOptions($options);
        return $memcache->prepend($key, $data);
    }

    public function get($key)
    {
	$memcache = $this->getMemcache();
	return $memcache->get($key);
    }

    public function gets(array $keys)
    {
	$memcache = $this->getMemcache();
	return $memcache->getMulti($keys);
    }
}
