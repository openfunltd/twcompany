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
        $q = urlencode('總公司統一編號:"' . $id . '"');
        $from = 10 * ($page - 1);
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?q=' . $q . '&from=' . $from);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }

    public function searchCompaniesByName($name, $page = 1, $alive_only = false)
    {
        $curl = curl_init();
        $name = Unit::changeRareWord($name);
        if ($alive_only) {
            $q = urlencode("(現況:核准設立 AND 商業名稱:\"{$name}\") OR (公司狀況:核准設立 AND 公司名稱:\"{$name}\") OR (名稱:\"{$name}\")");
        } else {
            $q = urlencode('商業名稱:"' . $name . '" OR 公司名稱:"' . $name. '" OR 名稱:"' . $name . '"');
        }
        $from = 10 * ($page - 1);
        curl_setopt($curl, CURLOPT_URL, getenv('SEARCH_URL') . '/company/_search?q=' . $q . '&from=' . $from);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $ret = curl_exec($curl);
        return json_decode($ret);
    }
}
