<?php

/**
 * Pix_Controller_Helper_Alert
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller_Helper_Alert extends Pix_Helper
{
    public static function getFuncs()
    {
        return array('alert');
    }

    /**
     * alert popup javascript alert with $message and redirect to $url
     *
     * @param Pix_Controller $controller
     * @param string $message
     * @param string $url
     * @access public
     * @return void
     */
    public function alert($controller, $message, $url)
    {
        $view = new Pix_Partial(__DIR__ . '/Alert/');
        echo $view->partial('alert.phtml', array('message' => $message, 'url' => $url));
        return $controller->noview();
    }
}
