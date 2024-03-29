<?php

// http://data.gov.tw/node/9400
// http://www.fia.gov.tw/opendata/bgmopen1.csv
include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

if ($_SERVER['argv'][1]) {
    $fp = fopen($_SERVER['argv'][1], 'r');
} else {
    system("wget -4 -O bgmopen1.zip https://eip.fia.gov.tw/data/BGMOPEN1.zip");
    system("unzip -o bgmopen1.zip");
    $unzip_filename = 'BGMOPEN1.csv';
    $old_filename = 'bgmopen1.csv';
    if (file_exists($old_filename)) {
        $old_md5 = md5_file($old_filename);
    } else {
        $old_md5 = null;
    }
    copy($unzip_filename, $old_filename);
    unlink('bgmopen1.zip');
    unlink($unzip_filename);
    if (!is_null($old_md5) and $old_md5 == md5_file($old_filename)) {
        echo "檔案未變\n";
        exit;
    }
    $fp = fopen($old_filename, "r");
}

$inserting = array();
$split = ',';
while ($rows = fgetcsv($fp, 0, ',')) {
    if (strpos($rows[0], '備註:') === 0) {
        continue;
    }
    if (strpos($rows[0], '檔案產生日期：') === 0) {
        continue;
    }
    $columns = array_map(function($s) {
        return str_replace('　', '', trim($s)); // 移除全形空白
    }, $rows);

    if (implode(',', $columns) != '營業地址,統一編號,總機構統一編號,營業人名稱,資本額,設立日期,組織別名稱,使用統一發票,行業代號,名稱,行業代號1,名稱1,行業代號2,名稱2,行業代號3,名稱3') {
        print_r($columns);
        throw new Exception('欄位不正確');
    }
    break;
}

$inserting = array();
$updating = array();
$update_column = new StdClass;
$insert_column = new StdClass;

$changed_unit = array();
$checking = array();
$names = array();
$split_column = array_search('行業代號', $columns);
if (!$split_column) {
    throw new Exception("找不到行業代號開始欄位");
}
while ($rows = fgetcsv($fp, 0, ',')) {
    if (strlen($rows[1]) != 8) {
        continue;
    }
    $rows = array_map(function($s) {
        return str_replace('　', '', trim($s)); // 移除全形空白
    }, $rows);
    if (count($rows) < $split_column) {
        print_r($rows);
        throw new Exception('wrong');
    }
    $values = array_combine(array_slice($columns, 0, $split_column), array_slice($rows, 0, $split_column));
    $rows = array_slice($rows, $split_column);
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
                if ($unit_data['column_id'] == 10 and !$checking[$id]) { // 負責人姓氏被拿掉了
                    unset($checking[$id]);
                    continue;
                }
                $updating[$id] = array($unit_data['value'], $checking[$id]);
                if (!property_exists($update_column, $unit_data['id'])) {
                    $update_column->{$unit_data['id']} = array();
                }
                $update_column->{$unit_data['id']}[] = $unit_data['column_id'];

                if (!in_array(FIAColumnGroup::getColumnName($unit_data['column_id']), array(
                    '使用統一發票',
                    //'行業', // 2023-01-31 大量更新
                ))) {  // 如果以上欄位變更，不需要去經濟部更新
                $changed_unit[$unit_data['id']] = $names[$unit_data['id']];
                }
            }
            unset($checking[$id]);
        }
        foreach ($checking as $no_column => $v) {
            list($id, $column_id) = explode('-', $no_column);
            if (!property_exists($insert_column, $id)) {
                $insert_column->{$id} = array();
            }
            $insert_column->{$id}[] = $column_id;
            $changed_unit[$id] = $names[$id];
        }
        $inserting = array_merge($inserting, $checking);
        $checking = array();
        $names = array();
    }
}
if (count($checking)) {
        error_log(sprintf("No: %s insert=%d update=%d", array_keys($checking)[0], count($inserting), count($updating)));

        $unit_ids = array_unique(array_map(function($a) { return explode('-', $a)[0]; }, array_keys($checking)));
        $unit_datas = FIAUnitData::search(1)->searchIn('id', $unit_ids);
        foreach ($unit_datas->toArray() as $unit_data) {
            $id = $unit_data['id'] . '-' . $unit_data['column_id'];
            if ($checking[$id] !== $unit_data['value']) {
                if ($unit_data['column_id'] == 10 and !$checking[$id]) { // 負責人姓氏被拿掉了
                    unset($checking[$id]);
                    continue;
                }
                $updating[$id] = array($unit_data['value'], $checking[$id]);
                if (!property_exists($update_column, $unit_data['id'])) {
                    $update_column->{$unit_data['id']} = array();
                }
                $update_column->{$unit_data['id']}[] = $unit_data['column_id'];

                if (!in_array(FIAColumnGroup::getColumnName($unit_data['column_id']), array(
                    '使用統一發票',
                ))) {  // 如果以上欄位變更，不需要去經濟部更新
                $changed_unit[$unit_data['id']] = $names[$unit_data['id']];
                }
            }
            unset($checking[$id]);
        }
        foreach ($checking as $no_column => $v) {
            list($id, $column_id) = explode('-', $no_column);
            if (!property_exists($insert_column, $id)) {
                $insert_column->{$id} = array();
            }
            $insert_column->{$id}[] = $column_id;
            $changed_unit[$id] = $names[$id];
        }
        $inserting = array_merge($inserting, $checking);
        $checking = array();
        $names = array();
    }
file_put_contents('change.log', json_encode($changed_unit));
$no = 0;
$total = count($changed_unit);
foreach ($changed_unit as $id => $name) {
    $no ++;
    error_log($no . '/' . $total);
    fwrite(STDERR, chr(27) . "k{$no}/{$total}" . chr(27) . "\\");
    $id = sprintf("%08d", $id);
    if (strpos($name, '分公司')) {
        $u = Updater2::updateBranch($id);
    } elseif (strpos($name, '公司')) {
        $u = Updater2::update($id);
    } else {
        $u = Updater2::updateBussiness($id);
    }
    if ($u) {
        $u->updateSearch();
    }
    $id = intval($id);
    if ($update_column->{$id}) {
        $change_records = array();
        $records = array();
        foreach ($update_column->{$id} as $column_id) {
            $no_column = $id . '-' . $column_id;
            $change_records[] = array($id, $now, $column_id, $updating[$no_column][0], $updating[$no_column][1]);
            $records[] = array($id, $column_id, $updating[$no_column][1]);
            unset($updating[$no_column]);
        }
        FIAUnitChangeLog::bulkInsert(array('id', 'updated_at', 'column_id', 'old_value', 'new_value'), $change_records, array('replace' => true));
        FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $records, array('replace' => true));
    }

    if ($insert_column->{$id}) {
        $records = array();
        foreach ($insert_column->{$id} as $column_id) {
            $no_column = $id . '-' . $column_id;
            $records[] = array($id, $column_id, $inserting[$no_column]);
            unset($inserting[$no_column]);
        }
        FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $records, array('replace' => true));
    }
}

$now = time();
foreach (array_chunk($updating, 1000, true) as $chunk_updating) {
    $changelog_records = array_map(function($no_column) use ($chunk_updating, $now){
        list($id, $column_id) = explode('-', $no_column);
        return array($id, $now, $column_id, $chunk_updating[$no_column][0], $chunk_updating[$no_column][1]);
    }, array_keys($chunk_updating));
    FIAUnitChangeLog::bulkInsert(array('id', 'updated_at', 'column_id', 'old_value', 'new_value'), $changelog_records, array('replace' => true));
}

$insert_records = array_map(function($no_column) use ($inserting) {
    list($id, $column_id) = explode('-', $no_column);
    return array($id, $column_id, $inserting[$no_column]);
}, array_keys($inserting));
FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $insert_records, array('replace' => true));

foreach (array_chunk($updating, 10000, true) as $chunk_updating) {
    $insert_records = array_map(function($no_column) use ($chunk_updating) {
        list($id, $column_id) = explode('-', $no_column);
        return array($id, $column_id, $chunk_updating[$no_column][1]);
    }, array_keys($chunk_updating));
    FIAUnitData::bulkInsert(array('id', 'column_id', 'value'), $insert_records, array('replace' => true));
}

Unit::refreshUpdatedStatus();
