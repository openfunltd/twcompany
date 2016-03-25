<?php

// http://data.gov.tw/node/9400
// http://www.fia.gov.tw/opendata/bgmopen1.csv
include(__DIR__ . '/../init.inc.php');

$fp = fopen($_SERVER['argv'][1], 'r');
$inserting = array();
while ($rows = fgetcsv($fp)) {
    if (strpos($rows[0], '備註:') === 0) {
        continue;
    }
    $columns = array_map('trim', $rows);

    if (implode(',', $columns) != '縣市,鄉鎮市區,統一編號,營業人名稱,資本額,設立日期,使用統一發票,行業代號,名稱,行業代號,名稱,行業代號,名稱,行業代號,名稱') {
        throw new Exception('欄位不正確');
    }
    break;
}

$insert_values = array();
while ($rows = fgetcsv($fp)) {
    $rows = array_map('trim', $rows);
    $values = array_combine(array_slice($columns, 0, 7), array_slice($rows, 0, 7));
    $rows = array_slice($rows, 7);
    $records = array();
    while ($no = array_shift($rows)) {
        $records[] = array($no, array_shift($rows));
    }
    $values['行業'] = $records;
    $no = $values['統一編號'];
    unset($values['統一編號']);

    foreach ($values as $k => $v) {
        $column_id = FIAColumnGroup::getColumnId($k);
        $inserting[] = array($no, $column_id, json_encode($v));
    }
    if (count($inserting) > 10000) {
        error_log('No: ' . $inserting[0][0]);
        FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $inserting);
        $inserting= array();
    }
}
if (count($inserting)) {
    error_log('final');
    FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $inserting);
}
