<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
//Pix_Table::enableLog(Pix_Table::LOG_QUERY);
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$last_show = microtime(true);
foreach (Unit::search(1)->volumemode(100000) as $unit) {
    if (microtime(true) - $last_show > 3) {
        $title = $unit->id();
        fwrite(STDERR, chr(27) . "k{$title}" . chr(27) . "\\");
        $last_show = microtime(true);
    }
    Elastic::dbBulkInsert('company', $unit->id(), $unit->getSearchData());
}

Elastic::dbBulkCommit();
