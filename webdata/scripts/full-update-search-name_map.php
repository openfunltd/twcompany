<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;
//Pix_Table::enableLog(Pix_Table::LOG_QUERY);
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');

$command = '';
$count = 0;

$last_show = microtime(true);
foreach (Unit::search(1)->volumemode(100000) as $unit) {
    if (microtime(true) - $last_show > 3) {
        $title = $unit->id();
        fwrite(STDERR, chr(27) . "k{$title} {$count}" . chr(27) . "\\");
        $last_show = microtime(true);
    }
    // 處理名稱搜尋
    $columns = array(
        2 => '公司名稱',
        33 => '商業名稱',
        48 => '分公司名稱',
        50 => '總(本)公司統一編號',
    );
    $fia_columns = array(
        3 => '營業人名稱',
    );

    $uni_name = function($str) {
        if (is_scalar($str)) {
            $name = $str;
        } elseif (is_array($str)) {
            if (is_scalar($str[0])) {
                $name = $str[0];
            }
            if ($str[0][1] == '(在臺灣地區公司名稱)') {
                $name = $str[0][0];
            }
        } else {
            return null;
        }
        $name = str_replace('　', '', $name);
        $name = Unit::changeRareWord($name);
        $name = Unit::toNormalNumber($name);
        return trim($name);
    };

    $values = array();
    foreach (UnitData::search(array('id' => $unit->id))->searchIn('column_id', array_keys($columns)) as $ud) {
        if (!array_key_exists($ud->column_id, $values)) {
            $values[$ud->column_id] = array();
        }
        $values[$ud->column_id][] = json_decode($ud->value);
    }
    foreach (UnitChangeLog::search(array('id' => $unit->id))->searchIn('column_id', array_keys($columns)) as $ud) {
        if (!array_key_exists($ud->column_id, $values)) {
            $values[$ud->column_id] = array();
        }
        $values[$ud->column_id][] = json_decode($ud->old_value);
        $values[$ud->column_id][] = json_decode($ud->new_value);
    }
    foreach (FIAUnitData::search(array('id' => $unit->id))->searchIn('column_id', array_keys($fia_columns)) as $ud) {
        if (!array_key_exists($ud->column_id, $values)) {
            $values[$ud->column_id] = array();
        }
        $values[$ud->column_id][] = json_decode($ud->value);
    }
    foreach (FIAUnitChangeLog::search(array('id' => $unit->id))->searchIn('column_id', array_keys($fia_columns)) as $ud) {
        if (!array_key_exists($ud->column_id, $values)) {
            $values[$ud->column_id] = array();
        }
        $values[$ud->column_id][] = json_decode($ud->old_value);
        $values[$ud->column_id][] = json_decode($ud->new_value);
    }

    $names = array();
    foreach (array(2, 3, 33) as $c) { // 公司名稱, 商業名稱, 營業人名稱
        if (!array_key_Exists($c, $values)) {
            continue;
        }
        foreach ($values[$c] as $n) {
            $n = $uni_name($n);
            if ($n) {
                $names[$n] = true;
            }
        }
    }

    if (array_key_exists(50, $values)) {
        $parents_names = array();
        foreach ($values[50] as $n) {
            $id = $uni_name($n);
            if (!$id) {
                continue;
            }
            foreach (UnitData::search(array('id' => $id, 'column_id' => 2)) as $ud) {
                $parents_names[] = json_decode($ud->value);
            }
            foreach (UnitChangeLog::search(array('id' => $id, 'column_id' => 2)) as $ud) {
                $parents_names[] = json_decode($ud->old_value);
                $parents_names[] = json_decode($ud->new_value);
            }
        }

        foreach ($values[48] as $n) {
            $branch_name = $uni_name($n);
            if (!$branch_name) {
                continue;
            }
            foreach ($parents_names as $parent_name) {
                $parent_name = $uni_name($parent_name);
                if ($parent_name) {
                    $names[$parent_name . $branch_name] = true;
                }
            }
        }
    }

    // 新增新的資料
    $id = $unit->id();
    foreach ($names as $name => $true) {
        $command .= json_encode(array(
            'update' => array(
                '_id' => $id . '-' . $name,
            ),
        ), JSON_UNESCAPED_UNICODE) . "\n";
        $command .= json_encode(array(
            'doc' => array(
                'company-name' => $name,
                'company-id' => $id,
            ),
            'doc_as_upsert' => true,
        ), JSON_UNESCAPED_UNICODE) . "\n";
        $count ++;
    }

    if ($count >= 10000) {
        $url = getenv('SEARCH_URL') . '/name_map/_bulk';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PROXY, '');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
        $ret = json_decode(curl_exec($curl));
        $info = curl_getinfo($curl);
        if (!in_array($info['http_code'], array(200, 201))) {
            throw new Exception($info['http_code'] . ' ' . $ret);
        }
        error_log("insert {$count} records");
        $count = 0;
        $command = '';
        if ($ret->errors) {
            print_r($ret);
            exit;
        }
    }

}

if ($count) {
        $url = getenv('SEARCH_URL') . '/name_map/_bulk';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PROXY, '');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
        $ret = json_decode(curl_exec($curl));
        $info = curl_getinfo($curl);
        if (!in_array($info['http_code'], array(200, 201))) {
            throw new Exception($info['http_code'] . ' ' . $ret);
        }
        $count = 0;
        $command = '';
        if ($ret->errors) {
            print_r($ret);
            exit;
        }
}
