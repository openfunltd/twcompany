<?php

/**
 * Pix_Controller_Dispatcher_Default default dispatcher, dispatch /foo/bar ro FooController barAction
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller_Dispatcher_Default extends Pix_Controller_Dispatcher
{
    public function dispatch($url)
    {
        list(, $controllerName, $actionName) = explode(DIRECTORY_SEPARATOR, $url);
        list($actionName, $ext) = explode('.', $actionName);
        $args = array();
        if ($ext) {
            $args['ext'] = $ext;
        }

        $actionName = $actionName ? $actionName : 'index';
        $controllerName = $controllerName ? $controllerName : 'index';

        if (!preg_match('/^([A-Za-z]{1,})$/' , $controllerName)) {
            return null;
        }
        if (!preg_match('/^([A-Za-z][A-Za-z0-9]*)$/' , $actionName)) {
            return array($controllerName, null);
        }
        return array($controllerName, $actionName, $args);
    }
}
