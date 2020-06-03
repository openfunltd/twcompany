<?php

include(__DIR__ . '/../init.inc.php');
$tables = array(
    'ColumnGroup',
    'FIAColumnGroup',
    'FIAUnitChangeLog',
    'FIAUnitData',
    'KeyValue',
    'UnitChangeLog',
    'UnitData',
    'Unit',
);
foreach ($tables as $table) {
    $t = Pix_Table::getTable($table);
    $t->createTable();
}
