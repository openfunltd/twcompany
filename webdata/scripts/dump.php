<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$columns = array();
foreach (ColumnGroup::search(1) as $columngroup) {
    $columns[$columngroup->id] = $columngroup->name;
}

$delta = 10000000;
for ($i = 0; $i * $delta < 99999999; $i ++) {
    $start = $i * $delta;
    $end = $start + $delta - 1;
    $tmpname = tempnam('', '');
    $file_name = 'files/' . str_pad($i * $delta, 8, '0', STR_PAD_LEFT) . '.json.gz';
    $fp = gzopen($tmpname, 'w');
    $unit_id = null;
    $unit = new StdClass;
    foreach (UnitData::search("`id` >= $start AND `id` <= $end")->order("`id`, `column_id`")->volumemode(10000) as $unit_data) {
        if (!is_null($unit_id) and $unit_data->id != $unit_id) {
            fwrite($fp, str_pad($unit_id, 8, '0', STR_PAD_LEFT) . ',' . json_encode($unit, JSON_UNESCAPED_UNICODE) . "\n");
            $unit = new StdClass;
        }
        $unit_id = $unit_data->id;

        $unit->{$columns[$unit_data->column_id]} = json_decode($unit_data->value);
    }
    if (!is_null($unit_id)) {
        fwrite($fp, str_pad($unit_id, 8, '0', STR_PAD_LEFT) . ',' . json_encode($unit, JSON_UNESCAPED_UNICODE) . "\n");
    }
    fclose($fp);
    DropboxLib::putFile($tmpname, $file_name);
    unlink($tmpname);
}
