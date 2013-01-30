<?php
/**
 *   產生完整列表的 script ，列表格式為
 *   統一編號,類型,名稱
 *   ex: 12345678,商業登記,我是一家公司
 */

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$tmpname = tempnam('', '');
$file_name = 'index.csv.gz';
$fp = gzopen($tmpname, 'w');
fwrite($fp, '#id,type,name' . PHP_EOL);
$units = $unit_names = $types = array();
$type_names = array(1 => '公司', 2 => '商業登記', 4 => '教育部', 99 => '其他');
foreach (Unit::search(1)->order('id')->volumemode(10000) as $unit) {
    $units[$unit->type][] = $unit->id;
    $unit_names[$unit->id] = '';
    $types[$unit->id] = $unit->type;

    if (count($unit_names) > 10000) {
        // 1 - 公司
        foreach (UnitData::search(array('column_id' => 2))->searchIn('id', $units[1]) as $unitdata) {
            $unit_names[$unitdata->id] = $unitdata->value;
        }
        // 2 - 商業登記
        foreach (UnitData::search(array('column_id' => 33))->searchIn('id', $units[2]) as $unitdata) {
            $unit_names[$unitdata->id] = $unitdata->value;
        }
        // 4 - 教育部, 99 - 其他
        foreach (UnitData::search(array('column_id' => 43))->searchIn('id', array_merge($units[4], $units[99])) as $unitdata) {
            $unit_names[$unitdata->id] = $unitdata->value;
        }

        foreach ($unit_names as $id => $value) {
            $rows = array();
            $rows[] = str_pad($id, 8, '0', STR_PAD_LEFT);
            $rows[] = $type_names[$types[$id]];
            $v = json_decode($value);
            if (is_array($v)) {
                $rows[] = $v[0];
            } else {
                $rows[] = strval($v);
            }
            fputcsv($fp, $rows);
        }
        $units = $unit_names = $types = array();
    }
}
if (count($unit_names)) {
    // 1 - 公司
    foreach (UnitData::search(array('column_id' => 2))->searchIn('id', $units[1]) as $unitdata) {
        $unit_names[$unitdata->id] = $unitdata->value;
    }
    // 2 - 商業登記
    foreach (UnitData::search(array('column_id' => 33))->searchIn('id', $units[2]) as $unitdata) {
        $unit_names[$unitdata->id] = $unitdata->value;
    }
    // 4 - 教育部, 99 - 其他
    foreach (UnitData::search(array('column_id' => 43))->searchIn('id', array_merge($units[4], $units[99])) as $unitdata) {
        $unit_names[$unitdata->id] = $unitdata->value;
    }

    foreach ($unit_names as $id => $value) {
        $rows = array();
        $rows[] = str_pad($id, 8, '0', STR_PAD_LEFT);
        $rows[] = $type_names[$types[$id]];
        $v = json_decode($value);
        if (is_array($v)) {
            $rows[] = $v[0];
        } else {
            $rows[] = strval($v);
        }
        fputcsv($fp, $rows);
    }
    $units = $unit_names = $types = array();
}
fclose($fp);
DropboxLib::putFile($tmpname, $file_name);
unlink($tmpname);
