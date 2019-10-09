<?php

class Updater2
{
    protected static $_last_fetch = null;

    public static function parseBussinessFile($content)
    {
        $doc = new DOMDocument;
        $content = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);

        $info = new StdClass;

        @$doc->loadHTML($content);
        if ($doc->getElementById('tabBusmContent')) {
            foreach ($doc->getElementById('tabBusmContent')->getElementsByTagName('tbody')->item(0)->childNodes as $tr_dom) {
                if ($tr_dom->nodeName != 'tr') {
                    continue;
                }
                $td_doms = $tr_dom->getElementsByTagName('td');
                if ($td_doms->length < 2) {
                    continue;
                }
                $key = trim($td_doms->item(0)->nodeValue);
                $value = trim($td_doms->item(1)->childNodes->item(0)->nodeValue);

                if (preg_match("#^(\d+)年(\d+)月(\d+)日$#", $value, $matches) or in_array($key, array(
                    '核准許可報備日期', '最後核准變更日期', '核准許可日期', '停業日期(起)', '停業日期(迄)',
                    '核准登記日期', '核准設立日期', '最後核准變更日期', '核准報備日期', '核准認許日期', '停業日期(起)', '停業日期(迄)',
                    '核准設立日期', '最後核准變更日期', '停業日期(起)', '停業日期(迄)', '延展開業日期(迄)',
                ))) {
                    $value = array(
                        'year' => intval($matches[1]) + 1911,
                        'month' => intval($matches[2]),
                        'day' => intval($matches[3]),
                    );
                } else if (in_array($key, array('負責人姓名', '合夥人姓名'))) {
                    foreach ($td_doms->item(1)->getElementsByTagName('tr') as $name_tr_dom) {
                        $name = $name_tr_dom->getElementsByTagName('td')->item(0)->nodeValue;
                        $amount = explode(':', $name_tr_dom->getElementsByTagName('td')->item(1)->nodeValue)[1];

                        if (!property_exists($info, '出資額(元)')) {
                            $info->{'出資額(元)'} = new StdClass;
                        }
                        $info->{'出資額(元)'}->{$name} = str_replace(',', '', $amount);

                        if (property_exists($info, $key)) {
                            if (is_scalar($info->{$key})) {
                                $info->{$key} = array($info->{$key});
                            }
                            $info->{$key}[] = $name;
                        } else {
                            $info->{$key} = $name;
                        }
                    }
                    continue;
                } elseif (in_array($key, array('營業項目'))) {
                    $value = trim($td_doms->item(1)->nodeValue);
                }

                $info->{$key} = $value;
            }
        }
        $info->{'商業統一編號'} = str_replace(html_entity_decode('&nbsp;'), '', $info->{'商業統一編號'});

        return $info;
    }

    public static function parseBranchFile($content)
    {
        $doc = new DOMDocument;
        $content = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);

        $info = new StdClass;

        @$doc->loadHTML($content);
        if ($doc->getElementById('tabCmpyContent')) {
            foreach ($doc->getElementById('tabCmpyContent')->getElementsByTagName('tbody')->item(0)->childNodes as $tr_dom) {
                if ($tr_dom->nodeName != 'tr') {
                    continue;
                }
                $td_doms = $tr_dom->getElementsByTagName('td');
                if ($td_doms->length < 2) {
                    continue;
                }
                $key = trim($td_doms->item(0)->nodeValue);
                $value = trim($td_doms->item(1)->childNodes->item(0)->nodeValue);

                if (preg_match("#^(\d+)年(\d+)月(\d+)日$#", $value, $matches) or in_array($key, array(
                    '核准許可報備日期', '最後核准變更日期', '核准許可日期', '停業日期(起)', '停業日期(迄)',
                    '核准登記日期', '核准設立日期', '最後核准變更日期', '核准報備日期', '核准認許日期', '停業日期(起)', '停業日期(迄)',
                    '核准設立日期', '最後核准變更日期', '停業日期(起)', '停業日期(迄)', '延展開業日期(迄)'))) {
                    $value = array(
                        'year' => intval($matches[1]) + 1911,
                        'month' => intval($matches[2]),
                        'day' => intval($matches[3]),
                    );
                } elseif ($key == '總(本)公司統一編號') {
                    $value = trim($td_doms->item(1)->getElementsByTagName('a')->item(0)->nodeValue);
                }

                $info->{$key} = $value;
            }
        }
        unset($info->{'總(本)公司名稱'});
        $info->{'分公司統一編號'} = str_replace(html_entity_decode('&nbsp;'), '', $info->{'分公司統一編號'});

        return $info;
    }

    public static function parseFile($content)
    {
        $doc = new DOMDocument;
        $content = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);


        $info = new StdClass;

        @$doc->loadHTML($content);
        if ($doc->getElementById('tabCmpyContent')) {
            foreach ($doc->getElementById('tabCmpyContent')->getElementsByTagName('tbody')->item(0)->childNodes as $tr_dom) {
                if ($tr_dom->nodeName != 'tr') {
                    continue;
                }
                $td_doms = $tr_dom->getElementsByTagName('td');
                if ($td_doms->length < 2) {
                    continue;
                }
                $key = trim($td_doms->item(0)->nodeValue);
                $value = trim($td_doms->item(1)->childNodes->item(0)->nodeValue);

                if (preg_match("#^(\d+)年(\d+)月(\d+)日$#", $value, $matches) or in_array($key, array(
                    '核准許可報備日期', '最後核准變更日期', '核准許可日期', '停業日期(起)', '停業日期(迄)',
                    '核准登記日期', '核准設立日期', '最後核准變更日期', '核准報備日期', '核准認許日期', '停業日期(起)', '停業日期(迄)',
                    '核准設立日期', '最後核准變更日期', '停業日期(起)', '停業日期(迄)', '延展開業日期(迄)'))) {
                    $value = array(
                        'year' => intval($matches[1]) + 1911,
                        'month' => intval($matches[2]),
                        'day' => intval($matches[3]),
                    );
                } elseif ($key == '所營事業資料') {
                    $list = array();
                    foreach ($td_doms->item(1)->childNodes as $node) {
                        if ($node->nodeValue == 'br') {
                            continue;
                        }
                        $lines = explode("\n", trim($node->wholeText));
                        if (!preg_match('#^([A-Z0-9]*)#', trim($lines[0]), $matches)) {
                            throw new Exception('事業代號不正確');
                        }
                        if ($matches[1]) {
                            $list[] = array($matches[1], trim($lines[1]));
                        }
                    }
                    $value = $list;
                } elseif (in_array($key, array('在中華民國境內負責人', '在中華民國境內代表人', '訴訟及非訴訟代理人姓名'))) {
                    $lines = explode("\n", trim($value));
                    if (count($lines) > 1) {
                        $value = array(
                            trim($lines[0]),
                            trim($lines[count($lines) - 1]),
                        );
                    }
                } elseif ($key == '公司名稱') {
                    $dom = $doc->getElementById('linkGoogleSearch');
                    while ($dom = $dom->nextSibling) {
                        if ($dom->nodeName == 'br') {
                            if (preg_match('#^(.*)\((.*)\)$#', trim($value), $matches1) and 
                                preg_match('#^(.*)\((.*)\)$#', trim($dom->nextSibling->nodeValue), $matches2)) {
                                $value = array(
                                    array(trim($matches1[1]), trim($matches1[2])),
                                    array(trim($matches2[1]), trim($matches2[2])),
                                );
                            } else {
                                $value = array($value, trim($dom->nextSibling->nodeValue));
                            }
                            break;
                        }
                    }
                }

                $info->{$key} = $value;
            }
        }

        if ($doc->getElementById('tabShareHolderContent')) {
            $list = array();
            foreach ($doc->getElementById('tabShareHolderContent')->getElementsByTagName('tbody')->item(0)->childNodes as $tr_dom) {
                if ($tr_dom->nodeName != 'tr') {
                    continue;
                }
                $td_doms = $tr_dom->getElementsByTagName('td');
                if ($td_doms->length != 5) {
                    continue;
                }
                $row = new StdClass;
                $row->{'序號'} = trim($td_doms->item(0)->nodeValue);
                $row->{'職稱'} = trim($td_doms->item(1)->nodeValue);
                $row->{'姓名'} = trim($td_doms->item(2)->nodeValue);
                if (trim($td_doms->item(3)->nodeValue) != '') {
                    $a_dom = $td_doms->item(3)->getElementsByTagName('a')->item(0);
                    if (!$a_dom) {
                        $row->{'所代表法人'} = array(0, trim($td_doms->item(3)->nodeValue));
                    } else {
                        $link = $a_dom->getAttribute('onclick');
                        if (!preg_match('#queryCmpy\(\'[^\']*\',\'([^\']*)#', $link, $matches)) {
                            throw new Exception('請處理法人');
                        }
                        $row->{'所代表法人'} = array($matches[1], trim($a_dom->nodeValue));
                    }
                } else {
                    $row->{'所代表法人'} = '';
                }
                $row->{'出資額'} = trim($td_doms->item(4)->nodeValue);
                $list[] = $row;
            }
            $info->{'董監事名單'} = $list;
        }

        if ($doc->getElementById('tabBrCmpyContent')) {
            if ($doc->getElementById('tabBrCmpyContent')->getElementsByTagName('a')->length) {
                $info->_has_branch = true;
            }
        }

        if ($doc->getElementById('tabMgrContent')) {
            $list = array();
            foreach ($doc->getElementById('tabMgrContent')->getElementsByTagName('tbody')->item(0)->childNodes as $tr_dom) {
                if ($tr_dom->nodeName != 'tr') {
                    continue;
                }
                $td_doms = $tr_dom->getElementsByTagName('td');
                if ($td_doms->length != 3) {
                    continue;
                }
                $row = new StdClass;
                $row->{'序號'} = trim($td_doms->item(0)->nodeValue);
                $row->{'姓名'} = trim($td_doms->item(1)->nodeValue);
                if (!preg_match('#(.*)年(.*)月(.*)日#', trim($td_doms->item(2)->nodeValue), $matches)) {
                    $row->{'到職日期'} = null;
                } else {
                    $value = new stdClass;
                    $value->year = 1911 + intval($matches[1]);
                    $value->month = intval($matches[2]);
                    $value->day = intval($matches[3]);
                    $row->{'到職日期'} = $value;
                }
                $list[] = $row;
            }
            $info->{'經理人名單'} = $list;
        }

        $info->{'統一編號'} = str_replace(html_entity_decode('&nbsp;'), '', $info->{'統一編號'});
        return $info;
    }

    public static function updateBussiness($id, $options = array())
    {
        $url = "https://findbiz.nat.gov.tw/fts/query/QueryList/queryList.do";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "qryCond={$id}&infoType=D&cmpyType=&brCmpyType=&qryType=busmType&busmType=true&factType=&lmtdType=&isAlive=all&busiItemMain=&busiItemSub=&sugCont=&sugEmail=&g-recaptcha-response=");
        curl_setopt($curl, CURLOPT_REFERER, $url); //'https://gcis.nat.gov.tw/pub/cmpy/cmpyInfoListAction.do');
        $content = curl_exec($curl);
        $content = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);
        $doc = new DOMDocument;
        @$doc->loadHTML($content);

        if (!$doc->getElementById('eslist-table')) {
            trigger_error("找不到商業登記: $id", E_USER_WARNING);
            return;
        }
        $hit_href = array();
        foreach ($doc->getElementById('eslist-table')->getElementsByTagName('a') as $a_dom) {
            $href = $a_dom->getAttribute('href');
            if (strpos($href, '/fts/query/QueryBusmDetail/queryBusmDetail.do') === false) {
                continue;
            }
            $hit_href[] = preg_replace('#\s*#', '', $href);
        }
        if (count($hit_href) != 1) {
            throw new Exception("搜尋 {$id} 商號結果不是 1 筆：" . count($hit_href));
        }

        $content = self::http("https://findbiz.nat.gov.tw" . $hit_href[0]);
        if (!$content) {
            trigger_error("找不到網頁內容: $url", E_USER_WARNING);
            return;
        }
        $info = self::parseBussinessFile($content);
        $info->url = $hit_href[0];

        if (!$parsed_id = $info->{'商業統一編號'}) {
            trigger_error("找不到統一編號: $id", E_USER_WARNING);
            return;

            throw new Exception('統一編號 not found?');
        }
        unset($info->{'商業統一編號'});

        if (!$unit = Unit::find($id)) {
            $unit = Unit::insert(array(
                'id' => $id,
                'type' => 2, // 商業登記
            ));
        }
        $unit->updateData($info);
        return $unit;
    }

    public static function updateBranch($id, $options = array())
    {
        $unit = Unit::find($id);
        if (!$unit) {
            // 找不到檔案就不用判斷了
        } else {
            $modified_at = $unit->updated_at;
            if (array_key_exists('month', $options)) {
                $query_time = strtotime('+1 month', mktime(0, 0, 0, $options['month'], 1, $options['year']));
                if ($query_time < $modified_at) {
                    return;
                }
            }
        }
        $url = "https://findbiz.nat.gov.tw/fts/query/QueryBrCmpyDetail/queryBrCmpyDetail.do?objectId=" .  urlencode(base64_encode('BC' . $id)) . '&brBanNo=' . urlencode($id);
        // 一秒只更新一個檔案
        while (!is_null(self::$_last_fetch) and (microtime(true) - self::$_last_fetch) < 0.5) {
            usleep(1000);
        }
        self::$_last_fetch = microtime(true);

        $content = self::http($url);
        if (!$content) {
            trigger_error("找不到網頁內容: $url", E_USER_WARNING);
            return;
        }

        $info = self::parseBranchFile($content);

        if (!$parsed_id = $info->{'分公司統一編號'}) {
            trigger_error("找不到統一編號: $id", E_USER_WARNING);
            return;

            throw new Exception('統一編號 not found?');
        }
        if ($info->{'總(本)公司統一編號'} == $info->{'分公司統一編號'}) {
            return self::update($id);
        }
        if (!$info->{'總(本)公司統一編號'}) {
            return;
        }
        unset($info->{'分公司統一編號'});

        if (!$unit = Unit::find($id)) {
            $unit = Unit::insert(array(
                'id' => $id,
                'type' => 3,
            ));
        } else {
            $unit->update(array('type' => 3));
        }
        $unit->updateData($info);
        return $unit;
    }

    public static function update($id, $options = array())
    {
        $unit = Unit::find($id);
        if (!$unit) {
            // 找不到檔案就不用判斷了
        } else {
            $modified_at = $unit->updated_at;
            if (array_key_exists('month', $options)) {
                $query_time = strtotime('+1 month', mktime(0, 0, 0, $options['month'], 1, $options['year']));
                if ($query_time < $modified_at) {
                    return;
                }
            }
        }

        $url = "https://findbiz.nat.gov.tw/fts/query/QueryCmpyDetail/queryCmpyDetail.do?objectId=" .  urlencode(base64_encode('HC' . $id)) . '&banNo=' . urlencode($id);
        // 一秒只更新一個檔案
        while (!is_null(self::$_last_fetch) and (microtime(true) - self::$_last_fetch) < 0.5) {
            usleep(1000);
        }
        self::$_last_fetch = microtime(true);

        $content = self::http($url);
        if (!$content) {
            trigger_error("找不到網頁內容: $url", E_USER_WARNING);
            return;
        }

        $info = self::parseFile($content);
        $has_branch = false;
        if (property_exists($info, '_has_branch')) {
            $has_branch = $info->_has_branch;
            unset($info->_has_branch);
        }

        if (!$parsed_id = $info->{'統一編號'}) {
            trigger_error("找不到統一編號: $id", E_USER_WARNING);
            return;

            throw new Exception('統一編號 not found?');
        }
        unset($info->{'統一編號'});

        if (!$unit = Unit::find($id)) {
            $unit = Unit::insert(array(
                'id' => $id,
                'type' => 1,
            ));
        } else {
            $unit->update(array('type' => 1));
        }

        if ($has_branch) {
            $branch_ids = self::searchBranch($unit->id());
        } else {
            $branch_ids = array();
        }

        if (!array_key_exists($unit->id(), $branch_ids)) {
            $unit->updateData($info);
        }
        foreach ($branch_ids as $id => $name) {
            // 跳過 branch 等同自己的
            if ($id == $unit->id()) {
                $info->{'分公司名稱'} = $name;
                $unit->updateData($info);
                continue;
            }
            self::updateBranch($id);
        }
        return $unit;
    }

    public static function http($url)
    {
        for ($i = 0; $i < 10; $i ++) {
            error_log('Fetching ' . $url . " time: {$i}");
            $curl = curl_init($url);
            if (getenv('PROXY_URL')) {
                curl_setopt($curl, CURLOPT_PROXY, getenv('PROXY_URL'));
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            curl_setopt($curl, CURLOPT_REFERER, $url); //'https://gcis.nat.gov.tw/pub/cmpy/cmpyInfoListAction.do');
            $content = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            if (200 == $info['http_code']) {
                return $content;
            }
            if ($i) {
                sleep($i);
            }
        }
        throw new Exception("fetch 3 times failed");
    }

    public static function searchBranch($id)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $ids = array();
        for ($page = 1; ; $page ++) {
            sleep(1);
            curl_setopt($curl, CURLOPT_URL, 'https://findbiz.nat.gov.tw/fts/query/QueryCmpyDetail/queryCmpyDetail.do');
            curl_setopt($curl, CURLOPT_REFERER, 'https://findbiz.nat.gov.tw/fts/query/QueryCmpyDetail/queryCmpyDetail.do');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, "banNo={$id}&brBanNo=&banKey=&estbId=&objectId=&CPage={$page}&brCmpyPage=Y&eng=false&CPageHistory=&historyPage=&chgAppDate=");
            $content = curl_exec($curl);
            $content = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);
            $doc = new DOMDocument;
            @$doc->loadHTML($content);
            if (!$dom = $doc->getElementById('tabBrCmpyContent')) {
                break;
            }
            $hit = false;
            foreach ($dom->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr') as $tr_dom) {
                $td_doms = $tr_dom->getElementsByTagName('td');
                if (!$td_doms->item(2)) {
                    continue;
                }
                if (!$a_dom = $td_doms->item(2)->getElementsByTagName('a')->item(0)) {
                    continue;
                }
                $hit = true;
                preg_match('#queryBranch\(\'([0-9]{8})#', $a_dom->getAttribute('onclick'), $matches);
                $ids[$matches[1]] = trim($a_dom->nodeValue);
            }
            if (!$hit) {
                break;
            }
        }

        return $ids;
    }
}
