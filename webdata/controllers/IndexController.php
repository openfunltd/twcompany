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

    public function nameAction()
    {
        list(, /*name*/, $name) = explode('/', $this->getURI());

        $name = urldecode($name);
        if (!$namemap = NameMap::search(array('name' => $name))->first()) {
            $unit_ids = array();
        } else {
            $unit_ids = NameTable::search(array('name_id' => $namemap->id))->toArray('unit_id');
        }
        $this->view->unit_ids = $unit_ids;
        $this->view->name = $name;
    }

    public function redirectAction()
    {
        if (!$unit = Unit::find(intval($_GET['id']))) {
            return $this->redirect('/');
        }
        $this->view->unit = $unit;
    }

    public function searchAction()
    {
        if ($id = intval($_GET['q'])) {
            return $this->redirect('/id/' . str_pad($id, '0', 8, STR_PAD_LEFT));
        }

        return $this->redirect('/');
    }
}
