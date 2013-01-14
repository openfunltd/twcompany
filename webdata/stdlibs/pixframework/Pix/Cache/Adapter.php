<?php

/**
 * Pix_Cache_Adapter
 *
 * @package Cache
 * @version $id$
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
abstract class Pix_Cache_Adapter
{
    abstract public function __construct($config);
    abstract public function add($key, $value, $expire = null);
    abstract public function set($key, $value, $expire = null);
    abstract public function delete($key);
    abstract public function replace($key, $value, $expire = null);
    abstract public function inc($key);
    abstract public function dec($key);
    abstract public function get($key);
    public function load($key)
    {
        return $this->get($key);
    }
    public function save($key, $value, $options = array())
    {
        return $this->set($key, $value, $options);
    }
    public function remove($key)
    {
        return $this->delete($key);
    }

    /**
     * gets 一次 get 多筆功能
     *
     * @param array $keys 要抓的 key 的 array
     * @access public
     * @return array
     */
    public function gets(array $keys)
    {
        $ret = array();
        foreach ($keys as $key) {
            $data = $this->get($key);
            if (false === $data) {
                continue;
            }
            $ret[$key] = $data;
        }
        return $ret;
    }

    /**
     * sets 一次指定多筆功能
     *
     * @param array $keys_values key, value 的 associate array
     * @access public
     * @return void
     */
    public function sets($keys_values)
    {
        foreach ($keys_values as $key => $value) {
            $this->set($key, $value);
        }
    }
}
