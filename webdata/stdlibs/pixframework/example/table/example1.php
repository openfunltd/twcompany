<?php

// init: 要自動載入 Pix Framework
include(__DIR__ . '/../../Pix/Loader.php');
set_include_path(
    __DIR__ . '/../../'
    . PATH_SEPARATOR . __DIR__ . '/models'
);
Pix_Loader::registerAutoload();

// 預設所有 Db 都是 sqlite
Pix_Table::setDefaultDb(new Pix_Table_Db_Adapter_Sqlite(':memory:'));

// 顯示 SQL query
Pix_Table::enableLog(Pix_Table::LOG_QUERY);

echo 'create table' . PHP_EOL;
User::createTable();
Article::createTable();

echo '增加 user alice' . PHP_EOL;
$user_alice = User::insert(array(
    'name' => 'alice',
    'password' => crc32('foo'),
));

echo '新增一篇文章' . PHP_EOL;
$article = $user_alice->create_articles(array(
    'post_at' => time(),
    'title' => '我是標題',
    'body' => '我是內容',
));
