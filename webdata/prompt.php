<?php

include(__DIR__ . '/init.inc.php');
if ($_SERVER['argv'][1] == '-d') {
    Pix_Table::enableLog(Pix_Table::LOG_QUERY);
}
Pix_Prompt::init();
