<?php
ini_set('memory_limit', '256m');

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
    $tmpname1 = tempnam('', '');
    $tmpname2 = tempnam('', '');
    $file_name1 = 'files/' . str_pad($i * $delta, 8, '0', STR_PAD_LEFT) . '.json.gz';
    $file_name2 = 'files/bussiness-' . str_pad($i * $delta, 8, '0', STR_PAD_LEFT) . '.json.gz';
    $fp[1] = gzopen($tmpname1, 'w');
    $fp[2] = gzopen($tmpname2, 'w');

    $unit_id = null;
    $unit = new StdClass;
    $unit_types = Unit::search("`id` >= $start AND `id` <= $end")->toArray('type');
    foreach (UnitData::search("`id` >= $start AND `id` <= $end")->order("`id`, `column_id`")->volumemode(10000) as $unit_data) {
        if (!is_null($unit_id) and $unit_data->id != $unit_id) {
            fwrite($fp[$unit_types[$unit_id]], str_pad($unit_id, 8, '0', STR_PAD_LEFT) . ',' . json_encode($unit, JSON_UNESCAPED_UNICODE) . "\n");
            $unit = new StdClass;
        }
        $unit_id = $unit_data->id;

        $unit->{$columns[$unit_data->column_id]} = json_decode($unit_data->value);
    }
    if (!is_null($unit_id)) {
        fwrite($fp[$unit_types[$unit_id]], str_pad($unit_id, 8, '0', STR_PAD_LEFT) . ',' . json_encode($unit, JSON_UNESCAPED_UNICODE) . "\n");
    }
    fclose($fp[1]);
    fclose($fp[2]);
    DropboxLib::putFile($tmpname1, $file_name1);
    DropboxLib::putFile($tmpname2, $file_name2);
    unlink($tmpname1);
    unlink($tmpname2);
}
