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
    public static function getFuncs()
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
        if ($encoding = $this->checkCanGzip()) {
            ob_start();
            ob_implicit_flush(0);
            echo @json_encode($obj);
            $contents = ob_get_contents();
            ob_end_clean();
            header("Content-Encoding: " . $encoding);
            print gzencode($contents);
            return $controller->noview();
        }

        echo @json_encode($obj);
        return $controller->noview();
    }

    public function jsonp($controller, $obj, $callback)
    {
        header('Content-Type: application/javascript');
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', strval($callback))) {
            return $controller->json($obj);
        }
        echo $callback . '(' . @json_encode($obj) . ')';
        return $controller->noview();
    }

    public function checkCanGzip() {

        if (headers_sent()) return 0;
        if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) return
            "x-gzip";
        if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false) return
            "gzip";
        return 0;
    }

}
