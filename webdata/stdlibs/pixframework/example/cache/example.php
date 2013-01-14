<?php

// init: 要自動載入 Pix Framework
include(__DIR__ . '/../../Pix/Loader.php');
set_include_path(
    __DIR__ . '/../../'
    . PATH_SEPARATOR . __DIR__ . '/models'
);
Pix_Loader::registerAutoload();

// 設定 Pix_Cache server 位置
Pix_Cache::addServer('Pix_Cache_Adapter_Memcache', array(
    'servers' => array(
        array('host' => 'memcache-server-name', 'port' => 11211),
    ),
));

$cache = new Pix_Cache();
$cache->save('test-key', 'value');
echo $cache->load('test-key');
