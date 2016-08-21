<?php

class SearchLib
{
    public function searchCompaniesByFund($name, $page = 1)
    {
        $curl = curl_init();
        $name = Unit::changeRareWord($name);
        $q = urlencode('董監事名單.所代表法人:"' . $name . '"');
        $from = 10 * ($page - 1);
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?q=' . $q . '&from=' . $from);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }

    public function searchCompaniesByPerson($name, $page = 1)
    {
        $curl = curl_init();
        $name = Unit::changeRareWord($name);
        $q = urlencode('代表人姓名:"' . $name . '" OR 經理人名單.姓名:"' . $name. '" OR 董監事名單.姓名:"' . $name . '"');
        $from = 10 * ($page - 1);
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?q=' . $q . '&from=' . $from);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }

    public function searchCompaniesByAddress($address, $page)
    {
        $curl = curl_init();
        $address = Unit::changeRareWord($address);
        $address = Unit::toNormalNumber($address);
        $q = urlencode('公司所在地:"' . $address. '"');
        $from = 10 * ($page - 1);
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?q=' . $q . '&from=' . $from);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }

    public function searchCompaniesByParent($id, $page)
    {
        $curl = curl_init();
        $id = sprintf("%08d", intval($id));
        $cmd = array(
            'query' => array(
                'match' => array('總(本)公司統一編號' => $id),
            ),
            'from' => 10 * ($page - 1),
        );
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search');
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_Setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cmd));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }

    public function searchCompaniesByName($name, $page = 1, $alive_only = false)
    {
        $curl = curl_init();
        if (!getenv('SKIP_RAREWORD')) {
            $name = Unit::changeRareWord($name);
        }
        $from = 10 * ($page - 1);
        if ($alive_only) {
            $q = urlencode("(現況:核准設立 AND 商業名稱:\"{$name}\") OR (公司狀況:核准設立 AND 公司名稱:\"{$name}\") OR (名稱:\"{$name}\")");
            curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?q=' . $q . '&from=' . $from);
        } else {
            $cmd = array(
                'query' => array(
                    'filtered' => array(
                        'query' => array('multi_match' => array(
                            'query' => $name,
                            'fields' => array('商業名稱', '公司名稱', '名稱'),
                            'type' => 'phrase',
                            'operator' => 'and'
                        )),
                        'filter' => array('missing' => array('field' => '分公司狀況')),
                    ),
                ),
                'from' => 10 * ($page - 1),
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cmd));
            curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?from=' . $from);
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }

    public function bulkSearchCompany($searchs)
    {
        $name_map = array();
        $names = array();
        foreach ($searchs['name'] as $id => $name) {
            $name = str_replace('　', '', $name);
            $name = Unit::changeRareWord($name);
            $name = Unit::toNormalNumber($name);
            $name_map[$id] = $name;
            if (array_key_exists($name, $names)) {
                continue;
            }
            $names[$name] = null;
        }

        foreach (array_chunk($names, 100, true) as $chunk_names) {
            $request = '';
            foreach ($chunk_names as $name => $nul) {
                $q = 'name:"' . ($name) . '"';
                $request .= "{}\n";
                $request .= json_encode(array(
                    'query' => array("query_string" => array('query' => $q )),
                )) . "\n";
            }
            $url = getenv('SEARCH_URL') . '/twcompany/name_map/_msearch';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            $ret = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            foreach (json_decode($ret)->responses as $res) {
                if (count($res->hits->hits) == 1) {
                    $hit = $res->hits->hits[0];
                    $names[$hit->_source->name] = $hit->_source->id;
                    continue;
                }

                if (count($res->hits->hits) == 0) {
                    continue;
                }

                $name = $res->hits->hits[0]->_source->name;
                $names[$name] = implode(';', array_map(function($hit) { return $hit->_source->id; }, $res->hits->hits));
            }
        }

        return array_map(function($id) use ($name_map, $names, $searchs) {
            return array(
                'query' => $searchs['name'][$id],
                'result' => $names[$name_map[$id]],
            );
        }, array_keys($name_map));
    }
}
