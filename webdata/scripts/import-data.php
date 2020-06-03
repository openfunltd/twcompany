<?php

include(__DIR__ . '/../init.inc.php');
$db = Unit::getDb();
$table = Unit::getTable();

error_log("import index.csv.gz");
$fp = gzopen("http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/index.csv.gz", "r");
fgetcsv($fp);
$map = array('公司' => 1, '商業登記' => 2, '分公司' => 3, '教育部' => 4, '其他' => 99);
$terms = array();
$c = 0;
while ($row = fgetcsv($fp)) {
    list($id, $type) = $row;

    $terms[] = sprintf("(%d,%d,0)", $id, $map[$type]);
    $c ++;
    if (count($terms) >= 100000) {
        $db->query("INSERT INTO unit (id,type,updated_at) VALUES " . implode(',', $terms));
        $terms = array();
        error_log("inserted {$c} unit");
    }
}
fclose($fp);

if (count($terms)) {
    $db->query("INSERT INTO unit (id,type,updated_at) VALUES " . implode(',', $terms));
    error_log("inserted {$c} unit");
}


$terms = array();
$c = 0;
for ($i = 0; $i < 10; $i ++) {
    error_log("importing {$i}0000000.jsonl.gz");
    $fp = gzopen("http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/files/{$i}0000000.jsonl.gz", "r");
    while ($line = fgets($fp)) {
        $obj = json_decode($line);
        if (!$obj) continue;
        $id = $obj->id;
        unset($obj->id);
        foreach ($obj as $k => $v) {
            $column_id = ColumnGroup::getColumnId($k);
            $terms[] = sprintf("(%d,%d,%s)", $id, $column_id, $db->quoteWithColumn($table, json_encode($v), 'value'));
            $c ++;
            if (count($terms) >= 100000) {
                $db->query("INSERT INTO unit_data (id,column_id,value) VALUES " . implode(',', $terms));
                $terms = array();
                error_log("inserted {$c} unit data");
            }
        }
    }
    fclose($fp);
}

if (count($terms)) {
    $db->query("INSERT INTO unit_data (id,column_id,value) VALUES " . implode(',', $terms));
    $terms = array();
    error_log("inserted {$c} unit data");
}
