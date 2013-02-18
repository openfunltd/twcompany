<?php

class ErrorController extends Pix_Controller
{
    public function errorAction()
    {
        if ($this->view->exception instanceof Pix_Controller_Dispatcher_Exception) {
            echo '404';
            return $this->noview();
        } else {
            trigger_error(strval($this->view->exception), E_USER_WARNING);
            return $this->noview();
        }
    }

    public function notfoundAction()
    {
    }
}
