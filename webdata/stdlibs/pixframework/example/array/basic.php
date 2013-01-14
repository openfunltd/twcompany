<?php

// example/array/basic.php
include(__DIR__ . '/../init.inc.php');

$array = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
$array = Pix_Array::factory($array);

// 得到 0 1 2
foreach ($array->limit(3) as $num) {
    echo $num . PHP_EOL;
}
echo "===" . PHP_EOL;

// 得到 5 6
foreach ($array->offset(5)->limit(2) as $num) {
    echo $num . PHP_EOL;
}
