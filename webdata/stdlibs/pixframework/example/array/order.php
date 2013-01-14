<?php

// example/array/basic.php
include(__DIR__ . '/../init.inc.php');

$members = array(
    array(
        'name' => 'alice',
        'height' => 158,
        'weight' => 42,
        'money' => 200,
    ),
    array(
        'name' => 'bob',
        'height' => 182,
        'weight' => 72,
        'money' => 150,
    ),
    array(
        'name' => 'carol',
        'height' => 170,
        'weight' => 58,
        'money' => 200,
    ),
);

$members = Pix_Array::factory($members);

// 照身高排序 bob(182), carol(170), alice(158)
foreach ($members->order('height DESC') as $member) {
    echo $member['name'] . '(' . $member['height'] . ')' . PHP_EOL;
}
echo "===" . PHP_EOL;

// 照體重排序 alice(42), carol(58), bob(72)
foreach ($members->order('weight ASC') as $member) {
    echo $member['name'] . '(' . $member['weight'] . ')' . PHP_EOL;
}
echo "===" . PHP_EOL;

// 照錢以及身高排序 carol(200,170), alice(200,158), bob(150,182)
foreach ($members->order('money DESC, height DESC') as $member) {
    echo $member['name'] . '(' . $member['money'] . ',' . $member['height'] . ')' . PHP_EOL;
}

