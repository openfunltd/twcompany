<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$tmpname = tempnam('', '');
$file_name = 'index.csv.gz';
$fp = gzopen($tmpname, 'w');
fwrite($fp, '#id,name' . PHP_EOL);
foreach (UnitData::search(array('column_id' => 2))->order('id')->volumemode(10000) as $unit_data) {
    fwrite($fp, str_pad($unit_data->id, 8, '0', STR_PAD_LEFT) . ',' . json_decode($unit_data->value) . "\n");
}
fclose($fp);
DropboxLib::putFile($tmpname, $file_name);
unlink($tmpname);
