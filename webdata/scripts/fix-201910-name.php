<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

while (true) {
    $ret = SearchLib::searchCompaniesByName('前名稱');
    foreach ($ret->hits->hits as $h) {
        $u = Unit::find($h->_id);
        $u->updateSearch();
    }
}
exit;

foreach (UnitChangeLog::search("column_id IN (2,10) AND updated_at > " . strtotime("2019/10/1")) as $change_log) {
    $id = sprintf("%08d", $change_log->id);
    if ($change_log->column_id == 2) { // 公司名稱
        $v = json_decode($change_log->new_value);
        if (is_array($v) and strpos($v[1], '前名稱')) {
            error_log("change 公司名稱 {$id}");
            UnitData::search(array('id' => $change_log->id, 'column_id' => 2))->update(array('value' => $change_log->old_value));
            $change_log->delete();
            Updater2::update($id);
        }
    } else { // 所營事業資料
        $v = json_decode($change_log->new_value);
        if (!$v) {
            error_log("change 所營事業資料 {$id}");
            UnitData::search(array('id' => $change_log->id, 'column_id' => 10))->update(array('value' => $change_log->old_value));
            $change_log->delete();
            Updater2::update($id);
        }
    }
    
}
