<?php
/**
 * 從之前的 sqlite 備份備到 mysql 的 script
 */
include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$file = $_SERVER['argv'][1];
if (!$file or !file_exists($file)) {
    die('need file');
}

// 先把所有 column 抓出來，之後就不需要再抓了
$columns = array();
foreach (ColumnGroup::search(1) as $columngroup) {
    $columns[$columngroup->name] = $columngroup->id;
}
// 匯入檔的格式
class ImportData extends Pix_Table
{
    public function init()
    {
        $this->_name = 'data';
        $this->_primary = 'no';

        $this->_columns['no'] = array('type' => 'varchar');
        $this->_columns['data'] = array('type' => 'text');
    }
}

$db = new Pix_Table_Db_Adapter_Sqlite($file);
ImportData::setDb($db);
$wdb = UnitData::getDb();
$table = UnitData::getTable();
$insert_data = array();
$insert_unit = array();

foreach (ImportData::search(1)->order('no')->volumemode(10000) as $importdata) {
    $data = json_decode($importdata->data);
    // 只處理商業登記
    if ('商業登記' !== $data->{'類型'}) {
        continue;
    }

    $insert_unit[] = "(" . intval($importdata->no) . ", 2, {$data->fetch_at})";

    foreach ($data as $name => $value) {
        // 類型和商業統一編號不需要了
        $name = trim($name);
        if (in_array($name, array('類型', '商業統一編號', 'fetch_at'))) {
            continue;
        }

        if (!$column_id = $columns[$name]) {
            $c = ColumnGroup::insert(array(
                'name' => $name,
            ));
            $column_id = $c->id;
            $columns[$name] = $column_id;
        }

        $insert_data[] = "(" . intval($importdata->no) . ", {$column_id}, " . $wdb->quoteWithColumn($table, json_encode($value, JSON_UNESCAPED_UNICODE), 'value') . ')';
    }
    if (count($insert_data) > 1000) {
        $wdb->query("INSERT IGNORE INTO `unit_data` (`id`, `column_id`, `value`) VALUES " . implode(', ', $insert_data));
        $wdb->query("INSERT IGNORE INTO `unit` (`id`, `type`, `updated_at`) VALUES " . implode(', ', $insert_unit));
        $insert_unit = array();
        $insert_data = array();
    }
    /*var_dump($data);
    exit;*/
}
if ($insert_data) {
    $wdb->query("INSERT IGNORE INTO `unit_data` (`id`, `column_id`, `value`) VALUES " . implode(', ', $insert_data));
    $wdb->query("INSERT IGNORE INTO `unit` (`id`, `type`, `updated_at`) VALUES " . implode(', ', $insert_unit));
}
