<?php

// http://data.gov.tw/node/9400
// http://www.fia.gov.tw/opendata/bgmopen1.csv
include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

if ($_SERVER['argv'][1]) {
    $fp = fopen($_SERVER['argv'][1], 'r');
} else {
    system("wget -O bgmopen1.zip http://www.fia.gov.tw/opendata/bgmopen1.zip");
    if (file_exists('BGMOPEN1.csv')) {
        $old_md5 = md5_file('BGMOPEN1.csv');
    } else {
        $old_md5 = null;
    }
    system("unzip -o -P1234 bgmopen1.zip");
    if (!is_null($old_md5) and $old_md5 == md5_file('BGMOPEN1.csv')) {
        echo "檔案未變\n";
        exit;
    }
    $fp = fopen("BGMOPEN1.csv", "r");
}

$inserting = array();
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
$updating = array();

$changed_unit = array();
$checking = array();
$names = array();
while ($rows = fgetcsv($fp, 0, ';')) {
    $rows = array_map('trim', $rows);
    if (count($rows) < 6) {
        print_r($rows);
        throw new Exception('wrong');
    }
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

    $names[intval($no)] = $values['營業人名稱'];

    foreach ($values as $k => $v) {
        $column_id = FIAColumnGroup::getColumnId($k);
        $checking[intval($no) . '-' . $column_id] = json_encode($v);
    }
    if (count($checking) > 100000) {
        error_log(sprintf("No: %s insert=%d update=%d", array_keys($checking)[0], count($inserting), count($updating)));

        $unit_ids = array_unique(array_map(function($a) { return explode('-', $a)[0]; }, array_keys($checking)));
        $unit_datas = FIAUnitData::search(1)->searchIn('id', $unit_ids);
        foreach ($unit_datas->toArray() as $unit_data) {
            $id = $unit_data['id'] . '-' . $unit_data['column_id'];
            if ($checking[$id] !== $unit_data['value']) {
                $updating[$id] = array($unit_data['value'], $checking[$id]);
                $changed_unit[$unit_data['id']] = $names[$unit_data['id']];
            }
            unset($checking[$id]);
        }
        foreach ($checking as $no_column => $v) {
            list($id, $column_id) = explode('-', $no_column);
            $changed_unit[$id] = $names[$id];
        }
        $inserting = array_merge($inserting, $checking);
        $checking = array();
        $names = array();
    }
}
file_put_contents('change.log', json_encode($changed_unit));
foreach ($changed_unit as $id => $name) {
    if (strpos($name, '分公司')) {
        Updater::updateBranch($id);
    } elseif (strpos($name, '公司')) {
        Updater::update($id);
    } else {
        Updater::updateBussiness($id);
    }
}

$now = time();
$changelog_records = array_map(function($no_column) use ($updating, $now){
    list($id, $column_id) = explode('-', $no_column);
    return array($id, $now, $column_id, $updating[$no_column][0], $updating[$no_column][1]);
}, array_keys($updating));
FIAUnitChangeLog::bulkInsert(array('id', 'updated_at', 'column_id', 'old_value', 'new_value'), $changelog_records, array('replace' => true));

$insert_records = array_map(function($no_column) use ($inserting) {
    list($id, $column_id) = explode('-', $no_column);
    return array($id, $column_id, $inserting[$no_column]);
}, array_keys($inserting));
FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $insert_records, array('replace' => true));

$insert_records = array_map(function($no_column) use ($updating) {
    list($id, $column_id) = explode('-', $no_column);
    return array($id, $column_id, $updating[$no_column][1]);
}, array_keys($updating));
FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $insert_records, array('replace' => true));
