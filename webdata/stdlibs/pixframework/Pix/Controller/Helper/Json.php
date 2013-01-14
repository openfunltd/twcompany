<?php

/**
 * Pix_Controller_Helper_Json
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller_Helper_Json extends Pix_Helper
{
    public function getFuncs()
    {
        return array('isJson', 'json', 'jsonp');
    }

    public function isJson($controller)
    {
        return preg_match('#application/json#', $_SERVER['HTTP_ACCEPT']);
    }

    public function json($controller, $obj)
    {
        header('Content-Type: application/json');
        echo @json_encode($obj);
        return $controller->noview();
    }

    public function jsonp($controller, $obj, $callback)
    {
        header('Content-Type: application/javascript');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', strval($callback))) {
            return $controller->json($obj);
        }
        echo $callback . '(' . @json_encode($obj) . ')';
        return $controller->noview();
    }
}
