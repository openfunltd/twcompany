<?php

// http://data.gov.tw/node/9400
// http://www.fia.gov.tw/opendata/bgmopen1.csv
// 2016/4/2x  財政部改格式，把縣市鄉鎮拿掉，統一改成地址
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

// 先移除縣市, 鄉鎮市區欄位
$min_id = 0;
while (false) {
    $res = FIAUnitData::getDb()->query("SELECT id FROM fia_unit_data WHERE id >= {$min_id} AND column_id IN (1,2) ORDER BY id ASC LIMIT 1");
    if (!$row = $res->fetch_object()) {
        break;
    }
    $min_id = $row->id;
    FIAUnitData::getDb()->query("DELETE FROM `fia_unit_data` WHERE `id` >= {$min_id} AND `column_id` IN (1, 2) LIMIT 10000");
}

while ($rows = fgetcsv($fp)) {
    if (strpos($rows[0], '備註:') === 0) {
        continue;
    }
    $columns = array_map('trim', $rows);

    if (implode(',', $columns) != '營業地址,統一編號,營業人名稱,資本額,設立日期,使用統一發票,行業代號,名稱,行業代號,名稱,行業代號,名稱,行業代號,名稱') {
        throw new Exception('欄位不正確');
    }
    break;
}

$inserting = array();

while ($rows = fgetcsv($fp)) {
    $rows = array_map('trim', $rows);
    $values = array_combine(array_slice($columns, 0, 6), array_slice($rows, 0, 6));
    $rows = array_slice($rows, 6);
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
    $inserting[] = array($no, FIAColumnGroup::getColumnId('營業地址'), json_encode($values['營業地址']));
    if (count($inserting) > 10000) {
        FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $inserting, array('replace' => true));
        $inserting = array();
    }
}
if (count($inserting)) {
    FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $inserting, array('replace' => true));
}
