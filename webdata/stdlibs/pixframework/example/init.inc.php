<?php

// init: 要自動載入 Pix Framework
include(__DIR__ . '/../Pix/Loader.php');
set_include_path(
    __DIR__ . '/../'
);
Pix_Loader::registerAutoload();
