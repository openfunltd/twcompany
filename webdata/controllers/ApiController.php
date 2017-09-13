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
            $data = new StdClass;
            $data->{'財政部'} = new StdClass;
            foreach (FIAUnitData::search(array('id' => $this->id)) as $unitdata) {
                $data->{'財政部'}->{FIAColumnGroup::getColumnName($unitdata->column_id)} = json_decode($unitdata->value);
            }
            $ret->data = $unit->getData();
            return $this->jsonp($ret, strval($_GET['callback']));
        }

        $ret->data = $unit->getData($_GET['with_changelog']);
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    public function searchAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $alive_only = $_GET['alive_only'] ? true : false;
        if (preg_match('#^address:(.*)$#', $_GET['q'], $matches)) {
            $search_ret = (SearchLib::searchCompaniesByAddress($matches[1], $page));
        } else {
            $search_ret = (SearchLib::searchCompaniesByName($_GET['q'], $page));
        }
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
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
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    public function nameAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByPerson($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    public function branchAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByParent($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    public function bulksearchAction()
    {
        $ret = SearchLib::bulkSearchCompany(array(
            'name' => explode(',', $_REQUEST['names']),
        ));
        return $this->jsonp($ret, strval($_GET['callback']));
    }
}
