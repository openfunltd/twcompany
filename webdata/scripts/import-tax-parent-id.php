<?php

// http://data.gov.tw/node/9400
// http://www.fia.gov.tw/opendata/bgmopen1.csv
// 2016/8/11  財政部改格式，增加 總機構統一編號 欄位
include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

if ($_SERVER['argv'][1]) {
    $fp = fopen($_SERVER['argv'][1], 'r');
} else {
    $fp = tmpfile();
    $curl = curl_init('http://www.fia.gov.tw/opendata/bgmopen1.csv');
    curl_setopt($curl, CURLOPT_FILE, $fp);
    curl_exec($curl);
    curl_close($curl);

    fseek($fp, 0);
}
Pix_Table::enableLog(Pix_Table::LOG_QUERY);

while ($rows = fgetcsv($fp)) {
    if (strpos($rows[0], '備註:') === 0) {
        continue;
    }
    if (strpos($rows[0], '檔案產生日期：') === 0) {
        continue;
    }
    $columns = array_map('trim', $rows);

    if (implode(',', $columns) != '營業地址,統一編號,總機構統一編號,營業人名稱,負責人姓氏,資本額,設立日期,使用統一發票,行業代號,名稱,行業代號,名稱,行業代號,名稱,行業代號,名稱') {
        throw new Exception('欄位不正確');
    }
    break;
}

$inserting = array();

while ($rows = fgetcsv($fp, 0, ';')) {
    $rows = array_map('trim', $rows);
    if (count($rows) < 7) {
        print_r($rows);
        throw new Exception('wrong');
    }
    $values = array_combine(array_slice($columns, 0, 7), array_slice($rows, 0, 7));
    $rows = array_slice($rows, 7);
    $records = array();
    while ($no = array_shift($rows)) {
        $records[] = array($no, array_shift($rows));
    }
    $values['行業'] = $records;
    $no = $values['統一編號'];
    $values['設立日期'] = array(
        'year' => intval(1911 + substr($values['設立日期'], 0, 3)),
        'month' => intval(substr($values['設立日期'], 3, 2)),
        'day' => intval(substr($values['設立日期'], 5, 2)),
    );
    unset($values['統一編號']);
    $inserting[] = array($no, FIAColumnGroup::getColumnId('總機構統一編號'), json_encode($values['總機構統一編號']));
    if (count($inserting) > 10000) {
        FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $inserting, array('replace' => true));
        $inserting = array();
    }
}
if (count($inserting)) {
    FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $inserting, array('replace' => true));
}
