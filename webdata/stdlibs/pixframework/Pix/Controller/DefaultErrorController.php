<?php

/**
 * Pix_Controller_DefaultErrorController
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller_DefaultErrorController extends Pix_Controller
{
    public function errorAction()
    {
        trigger_error("Pix_Controller_DefaultErrorController catch a exception {$this->view->exception->getMessage()}.", E_USER_WARNING);
    }
}
