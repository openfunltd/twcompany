<?php

include(__DIR__ . '/../init.inc.php');

try {
    Elastic::dropIndex('');
} catch (Exception $e) {
}

Elastic::createIndex('company', [
    "date_detection" => false,
    'properties' => [
        '公司名稱' => ['type' => 'text', 'analyzer' => 'cjk'],
        '代表人姓名' => ['type' => 'text', 'analyzer' => 'cjk'],
        '公司所在地' => ['type' => 'text', 'analyzer' => 'cjk'],
        '分公司名稱' => ['type' => 'text', 'analyzer' => 'cjk'],
        '分公司所在地' => ['type' => 'text', 'analyzer' => 'cjk'],
        '分公司經理姓名' => ['type' => 'text', 'analyzer' => 'cjk'],
        '合夥人姓名' => ['type' => 'text', 'analyzer' => 'cjk'],
        '商業名稱' => ['type' => 'text', 'analyzer' => 'cjk'],
        '名稱' => ['type' => 'text', 'analyzer' => 'cjk'],
        '地址' => ['type' => 'text', 'analyzer' => 'cjk'],
        '負責人姓名' => ['type' => 'text', 'analyzer' => 'cjk'],
        '辦事處所在地' => ['type' => 'text', 'analyzer' => 'cjk'],
        '總(本)公司統一編號' => ['type' => 'text', 'analyzer' => 'cjk'],
        '經理人名單' => ['type' => 'text', 'analyzer' => 'cjk'],
        '董監事名單' => ['type' => 'text', 'analyzer' => 'cjk'],
    ],
]);
Elastic::createIndex('name_map', [
    "date_detection" => false,
    'properties' => [
        'company-name' => ['type' => 'keyword'],
        'company-id' => ['type' => 'keyword'],
    ],
]);
