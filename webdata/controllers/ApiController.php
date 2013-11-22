<?php

class ApiController extends Pix_Controller
{
    public function init()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
    }

    public function showAction()
    {
        list(, /*api*/, /*show*/, $id) = explode('/', $this->getURI());

        $ret = new StdClass;
        if (!$unit = Unit::find(intval($id))) {
            $ret->error = true;
            $ret->message = "Company not found";
            return $this->jsonp($ret, strval($_GET['callback']));
        }

        $ret->data = $unit->getData();
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    public function searchAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $alive_only = $_GET['alive_only'] ? true : false;
        $search_ret = SearchLib::searchCompaniesByName($_GET['q'], $page, $alive_only);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $hit->_source->{'統一編號'} = $hit->_id;
            $data[] = $hit->_source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    public function fundAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByFund($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $hit->_source->{'統一編號'} = $hit->_id;
            $data[] = $hit->_source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total;
        return $this->jsonp($ret, strval($_GET['callback']));
    }
}
