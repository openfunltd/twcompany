#!/usr/bin/env php
<?php

for ($i = 1; isset($_SERVER['argv'][$i]); $i ++) {
    if (file_exists($_SERVER['argv'][$i])) {
        include($_SERVER['argv'][$i]);
    }
}

include(__DIR__ . '/../Pix/Prompt.php');
Pix_Prompt::init();

