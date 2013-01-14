<?php

error_reporting(E_ALL);

// init: 要自動載入 Pix Framework
include(__DIR__ . '/../Pix/Loader.php');
set_include_path(
    get_include_path() .
    PATH_SEPARATOR .  __DIR__ . '/../' .
    PATH_SEPARATOR .  __DIR__ . '/'
);
Pix_Loader::registerAutoload();
