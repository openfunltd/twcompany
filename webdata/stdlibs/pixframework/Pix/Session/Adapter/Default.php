<?php

/**
 * Pix_Session_Adapter_Default
 *
 * @package Session
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Session_Adapter_Default extends Pix_Session_Adapter
{
    public function __construct($config = array())
    {
        if (isset($config['save_path'])) {
            ini_set('session.save_path', $config['save_path']);
        }

        if (isset($config['save_handler'])) {
            ini_set('session.save_handler', $config['save_handler']);
        }

        if (!session_id()){
            session_start();
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return $_SESSION[$key];
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function clear()
    {
        session_destroy();
    }
}
