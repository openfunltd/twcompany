<?php

class SearchLib
{
    public static function searchCompaniesByFund($name, $page = 1)
    {
        $name = Unit::changeRareWord($name);
        $q = urlencode('董監事名單.所代表法人:"' . $name . '"');
        $from = 10 * ($page - 1);

        $prefix = getenv('ELASTIC_PREFIX');
        $ret = Elastic::dbQuery("/{$prefix}company/_search?q={$q}&from={$from}");
        return $ret;
    }

    public static function searchCompaniesByPerson($name, $page = 1)
    {
        $name = Unit::changeRareWord($name);
        $q = urlencode('代表人姓名:"' . $name . '" OR 經理人名單.姓名:"' . $name. '" OR 董監事名單.姓名:"' . $name . '" OR 負責人姓名:"' . $name . '"');
        $from = 10 * ($page - 1);
        $prefix = getenv('ELASTIC_PREFIX');
        $ret = Elastic::dbQuery("/{$prefix}company/_search?q={$q}&from={$from}");
        return $ret;
    }

    public static function searchCompaniesByAddress($address, $page)
    {
        $address = Unit::changeRareWord($address);
        $address = Unit::toNormalNumber($address);
        $q = urlencode('公司所在地:"' . $address. '" OR 地址:"' . $address . '"');
        $from = 10 * ($page - 1);
        $prefix = getenv('ELASTIC_PREFIX');
        $ret = Elastic::dbQuery("/{$prefix}company/_search?q={$q}&from={$from}");
        return $ret;
    }

    public static function searchCompaniesByParent($id, $page)
    {
        $id = sprintf("%08d", intval($id));
        $cmd = array(
            'query' => array(
                'match' => array('總(本)公司統一編號' => $id),
            ),
            'from' => 10 * ($page - 1),
        );
        $prefix = getenv('ELASTIC_PREFIX');
        $ret = Elastic::dbQuery("/{$prefix}company/_search", "GET", json_encode($cmd));
        return $ret;
    }

    public static function searchCompaniesByName($name, $page = 1, $alive_only = false)
    {
        if (!getenv('SKIP_RAREWORD')) {
            $name = Unit::changeRareWord($name);
        }
        $from = 10 * ($page - 1);
        $prefix = getenv('ELASTIC_PREFIX');
        if ($alive_only) {
            $q = urlencode("(現況:核准設立 AND 商業名稱:\"{$name}\") OR (公司狀況:核准設立 AND 公司名稱:\"{$name}\") OR (名稱:\"{$name}\")");
            $ret = Elastic::dbQuery("/{$prefix}company/_search?q={$q}&from={$from}");
            return $ret;
        } else {
            $cmd = array(
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'multi_match' => [
                                    'query' => $name,
                                    'fields' => ['商業名稱', '公司名稱', '名稱'],
                                    'type' => 'phrase',
                                    'operator' => 'and'
                                ],
                            ],
                        ],
                        'filter' => [
                            [
                                'bool' => [
                                    'must_not' => [
                                        ['exists' => ['field' => '分公司狀況']],
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
                'from' => 10 * ($page - 1),
            );
            $ret = Elastic::dbQuery("/{$prefix}company/_search", "GET", json_encode($cmd));
            return $ret;
        }
    }

    public static function bulkSearchCompany($searchs)
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

        $prefix = getenv('ELASTIC_PREFIX');
        foreach (array_chunk($names, 100, true) as $chunk_names) {
            $request = '';
            foreach ($chunk_names as $name => $nul) {
                $request .= "{}\n";
                $request .= json_encode(array(
                    'query' => array("term" => array('company-name' => $name )),
                )) . "\n";
            }
            $ret = Elastic::dbQuery("/{$prefix}name_map/_msearch", "GET", $request);
            foreach ($ret->responses as $res) {
                if (count($res->hits->hits) == 1) {
                    $hit = $res->hits->hits[0];
                    $names[$hit->_source->{"company-name"}] = $hit->_source->{"company-id"};
                    continue;
                }

                if (count($res->hits->hits) == 0) {
                    continue;
                }

                $name = $res->hits->hits[0]->_source->{"company-name"};
                $names[$name] = implode(';', array_map(function($hit) { return $hit->_source->{"company-id"}; }, $res->hits->hits));
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
