<?php

class ApiController extends Pix_Controller
{
    public function showAction()
    {
        list(, /*id*/, $id) = explode('/', $this->getURI());

        $ret = new StdClass;
        if (!$unit = Unit::find(intval($id))) {
            $ret->error = true;
            $ret->message = "Company not found";
            return $this->json($ret);
        }

        $columns = array();
        foreach (ColumnGroup::search(1) as $columngroup) {
            $columns[$columngroup->id] = $columngroup->name;
        }

        $data = new StdClass;
        foreach (UnitData::search(array('id' => $unit->id)) as $unitdata) {
            $data->{$columns[$unitdata->column_id]} = json_decode($unitdata->value);
        }

        $ret->data = $data;
        return $this->json($ret);
    }

    public function searchAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByName($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $data[] = $hit->_source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total;
        return $this->json($ret);
    }
}
