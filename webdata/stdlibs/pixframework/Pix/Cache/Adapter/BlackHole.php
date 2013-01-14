<?php

/**
 * Pix_Cache_Adapter_BlackHole
 *
 * @uses Pix_Cache_Adapter
 * @package Cache
 * @version $id$
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Cache_Adapter_BlackHole extends Pix_Cache_Adapter
{
    public function __construct($config)
    {
    }

    public function add($key, $value, $options = array())
    {
    }

    public function set($key, $value, $options = array())
    {
    }

    public function delete($key)
    {
    }

    public function replace($key, $value, $options = array())
    {
    }

    public function inc($key, $inc = 1)
    {
    }

    public function dec($key, $inc = 1)
    {
    }

    public function append($key, $data, $options = array())
    {
    }

    public function prepend($key, $data, $options = array())
    {
    }

    public function get($key)
    {
	return false;
    }

    public function gets(array $keys)
    {
	return false;
    }
}
