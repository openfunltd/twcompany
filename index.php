<?php

include(__DIR__ . '/webdata/init.inc.php');

class MyDispatcher extends Pix_Controller_Dispatcher
{
    public function dispatch($path)
    {
        if ($path == '/swagger.json') {
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            readfile(__DIR__ . '/webdata/swagger.json');
            exit;
        }
        if (preg_match('#^/id/\d+$#', $path)) {
            return array('index', 'show');
        } elseif (preg_match('#^/fund/#', $path)) {
            return array('index', 'fund');
        } elseif (preg_match('#^/branch/#', $path)) {
            return array('index', 'branch');
        } elseif (preg_match('#^/name/#', $path)) {
            return array('index', 'name');
        }
        return null;
    }
}

Pix_Controller::addDispatcher(new MyDispatcher);
Pix_Controller::addCommonHelpers();
Pix_Controller::dispatch(__DIR__ . '/webdata/');
