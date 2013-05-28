<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
$ids = (Updater::searchByKeyword($_SERVER['argv'][1]));
foreach ($ids as $id) {
    if (Unit::find($id)) {
        continue;
    }
    Updater::update($id);
    Unit::find($id)->updateSearch();
}

$ids = (Updater::searchBussinessByKeyword($_SERVER['argv'][1]));
foreach ($ids as $id) {
    if (Unit::find($id)) {
        continue;
    }
    if ($u = Updater::updateBussiness($id)) {
        $u->updateSearch();
    }
}
