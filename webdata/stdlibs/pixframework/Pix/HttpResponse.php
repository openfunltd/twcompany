<?php

/**
 * Pix_HttpResponse rewrite some PHP HTTP method for adding hooks
 * 
 * @package HttpResponse
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_HttpResponse
{
    protected static function runHook($action, $args)
    {
        if (function_exists('PixHttpResponseHook_' . $action)) {
            $hook = 'PixHttpResponseHook_' . $action;
            call_user_func_array($hook, $args);
        }
    }

    public static function redirect($url, $code = 302)
    {
        header("Location: $url", true, $code);
        self::runHook('redirect', func_get_args());
    }

    public static function setcookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false)
    {
        if (is_null($domain)) {
            $domain = $_SERVER['HTTP_HOST'];
        }
        setcookie($name, $value, $expire, $path, $domain, $secure);
        self::runHook('setcookie', func_get_args());
    }
}
