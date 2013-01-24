<?php

class IndexController extends Pix_Controller
{
    public function indexAction()
    {
    }

    public function showAction()
    {
        list(, /*id*/, $id) = explode('/', $this->getURI());

        if (!$unit = Unit::find(intval($id))) {
            return $this->redirect('/');
        }

        $this->view->unit = $unit;
    }
}
