<?php

include(__DIR__ . '/../init.inc.php');

$fp = fopen(__DIR__ . '/../maps/rare-word.csv', 'r');
$words = array();
while ($rows = fgetcsv($fp)) {
    list($old, $new) = $rows;
    $words[$old] = $new;
    for ($i = 0; true; $i ++) {
        $ret = SearchLib::searchCompaniesByName($old, $i + 1);
        foreach ($ret->hits->hits as $hit) {
            Unit::find($hit->_id)->updateSearch();
        }
        $total = $ret->hits->total;
        if ($i * 10 >= $total) {
            break;
        }
    }
}
fclose($fp);

