<?php

// 把財政部資料歷史記錄備份起來
$start = strtotime('-1 year');
//$start = strtotime('2013/1/1');

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

// 依月份處理
for ($time = $start; $time < mktime(0, 0, 0, date('m'), 1) - 86400; $time = strtotime('next month', $time)) {
    error_log(date('Ym', $time));
    $tmpname = tempnam('', '');
    $fp = gzopen($tmpname, 'w');
    fputs($fp, json_encode(array('id', 'time', 'column', 'old', 'new')) . "\n");
    foreach (UnitChangeLog::search(sprintf("updated_at >= %d AND updated_at < %d", $time, strtotime('next month', $time)))->volumemode(10000) as $unit) {
        fputs($fp, json_encode(array(
            $unit->id,
            intval($unit->updated_at),
            ColumnGroup::getColumnName($unit->column_id),
            json_decode($unit->old_value),
            json_decode($unit->new_value),
        ), JSON_UNESCAPED_UNICODE) . "\n");
    }
    fclose($fp);
    $file_name = date('Ym', $time) . '.jsonl.gz';
    S3Lib::putFile($tmpname, 's3://ronnywang-twcompany/gcis-history/' . $file_name);
    S3Lib::buildIndex('s3://ronnywang-twcompany/');
    unlink($tmpname);
}
