<?php

include(__DIR__ . '/webdata/init.inc.php');

class MyDispatcher extends Pix_Controller_Dispatcher
{
    public function dispatch($path)
    {
        if (preg_match('#^/id/#', $path)) {
            return array('index', 'show');
        } elseif (preg_match('#^/name/#', $path)) {
            return array('index', 'name');
        }
        return null;
    }
}

Pix_Controller::addDispatcher(new MyDispatcher);
Pix_Controller::addCommonHelpers();
Pix_Controller::dispatch(__DIR__ . '/webdata/');
