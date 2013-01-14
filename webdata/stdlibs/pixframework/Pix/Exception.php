<?php

/**
 * Pix_Exception 
 * 
 * @uses Exception
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Exception extends Exception
{
    public $errorcode;
    public $options;

    public function __construct($message = null, $errorcode = null, $options = null)
    {
	parent::__construct($message);
	$this->errorcode = $errorcode;
	$this->options = $options;
    }
}
