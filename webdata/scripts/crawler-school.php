<?php
include(__DIR__ . '/../init.inc.php');
for ($i = 0; ; $i ++) {
    $url = 'http://140.111.34.54/GENERAL/school_list.aspx?pages=' . $i . '&site_content_sn=16678';
    error_log('fetching ' . $url);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    $content = curl_exec($curl);
    //$content = iconv('big5', 'utf-8', $content);

    $doc = new DOMDocument();
    @$doc->loadHTML($content);
    $id_table = null;
    foreach ($doc->getElementsByTagName('table') as $table_dom) {
        if ('訊息列表' == $table_dom->getAttribute('summary')) {
            $id_table = $table_dom;
            break;
        }
    }

    if (!$id_table) {
        die('找不到統一編號');
    }

    $is_empty = true;
    foreach ($id_table->getElementsByTagName('tr') as $tr_dom) {
        if (!in_array($tr_dom->getAttribute('class'), array('td_style01', 'td_style02'))) {
            continue;
        }
        $is_empty = false;
        $td_doms = $tr_dom->getElementsByTagName('td');
        $obj = new StdClass;
        $obj->{'類別'} = $td_doms->item(1)->nodeValue;
        $id = $td_doms->item(2)->nodeValue;
        $obj->{'名稱'} = $td_doms->item(3)->nodeValue;
        $obj->{'來源'} = 'http://140.111.34.54/GENERAL/index.aspx';
        if ($unit = Unit::find($id)) {
            if ($unit->type != 99 and $unit->type != 4) {
                var_dump($unit->toArray());
                die();
            }
            $unit->update(array('type' => 4));
        } else {
            $unit = Unit::insert(array(
                'id' => $id,
                'type' => 4,
                'updated_at' => time(),
            ));
        }
        $unit->updateData($obj);
    }

    if ($is_empty) {
        break;
    }
}
