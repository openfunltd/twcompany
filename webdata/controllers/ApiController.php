<?php

/**
 * @OA\Info(title="台灣公司資料API", version="0.0.1")
 * 
 */
class ApiController extends Pix_Controller
{
    public function init()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
    }

    public function bulkqueryAction()
    {
        $ids = array_slice(explode(';', $_REQUEST['ids']), 0, 10000);
        $ret = new StdClass;
        foreach ($ids as $id) {
            if (!$id = intval($id)) {
                continue;
            }
            if (!$unit = Unit::find(intval($id))) {
                $data = new StdClass;
                $data->{'財政部'} = new StdClass;
                foreach (FIAUnitData::search(array('id' => $id)) as $unitdata) {
                    $data->{'財政部'}->{FIAColumnGroup::getColumnName($unitdata->column_id)} = json_decode($unitdata->value);
                }
                $ret->{$id} = $data;
            } else {
                $ret->{$id} = $unit->getData();
            }
        }
        return $this->json($ret);
    }

    public function showAction()
    {
        list(, /*api*/, /*show*/, $id) = explode('/', $this->getURI());

        $ret = new StdClass;
        if (!$unit = Unit::find(intval($id))) {
            $data = new StdClass;
            $data->{'財政部'} = new StdClass;
            foreach (FIAUnitData::search(array('id' => $id)) as $unitdata) {
                $data->{'財政部'}->{FIAColumnGroup::getColumnName($unitdata->column_id)} = json_decode($unitdata->value);
            }
            $ret->data = $data;
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
            $search_ret = (SearchLib::searchCompaniesByAddress($matches[1], $page, $alive_only));
        } else {
            $search_ret = (SearchLib::searchCompaniesByName($_GET['q'], $page, $alive_only));
        }
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total->value;
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
        $ret->found = $search_ret->hits->total->value;
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
        $ret->found = $search_ret->hits->total->value;
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
        $ret->found = $search_ret->hits->total->value;
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
