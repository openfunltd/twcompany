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

        $page = intval($_GET['page']) ?: 1;
        $ret = (SearchLib::searchCompaniesByPerson($name, $page));

        $this->view->page = $page;
        $this->view->max_page = ceil($ret->hits->total / 10);
        $this->view->search_word = $name;
        $this->view->search_result = $ret;
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
        if (preg_match('#\d{8}#', $_GET['q'])) {
            return $this->redirect('/id/' . $_GET['q']);
        }

        $page = intval($_GET['page']) ?: 1;
        if (preg_match('#^address:(.*)$#', $_GET['q'], $matches)) {
            $ret = (SearchLib::searchCompaniesByAddress($matches[1], $page));
        } else {
            $ret = (SearchLib::searchCompaniesByName($_GET['q'], $page));
        }
        if ($ret->hits->total == 1) {
            return $this->redirect('/id/' . urlencode($ret->hits->hits[0]->_id));
        }

        $this->view->page = $page;
        $this->view->max_page = ceil($ret->hits->total / 10);
        $this->view->search_word = $_GET['q'];
        $this->view->search_result = $ret;
    }
}
