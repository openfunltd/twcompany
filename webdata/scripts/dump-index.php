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
foreach (UnitData::search(1)->searchIn('column_id', array(2, 33))->order('id')->volumemode(10000) as $unit_data) {
    $rows = array();
    # 統一編號
    $rows[] = str_pad($unit_data->id, 8, '0', STR_PAD_LEFT);
    # 公司 or 商業登記
    if (2 == $unit_data->column_id) {
        $rows[] = '公司';
    } elseif (33 == $unit_data->column_id) {
        $rows[] = '商業登記';
    }
    # 公司名稱
    $v = json_decode($unit_data->value);
    if (is_array($v)) {
        $rows[] = $v[0];
    } else {
        $rows[] = strval($v);
    }
    fputcsv($fp, $rows);
}
fclose($fp);
DropboxLib::putFile($tmpname, $file_name);
unlink($tmpname);
