<?php
/**
 *  從現有的資料中，找出所有公司、商號、分公司名稱，並生出統編號名稱的列表
 */

include(__DIR__ . '/../init.inc.php');
Pix_Table::addStaticResultSetHelper('Pix_Array_Volume');
Pix_Table::$_save_memory = true;
Pix_Table::enableLog(Pix_Table::LOG_QUERY);


class UnitColumnGetter implements Pix_Array_Volumable
{
    protected $_after = null;
    protected $_limit = 100;
    protected $_columns = array();
    protected $_fia_columns = array();

    public function __construct($columns, $fia_columns = array())
    {
        $this->_columns = $columns;
        $this->_fia_columns = $fia_columns;
    }

    public function after($after)
    {
        $this->_after = $after;
        return $this;
    }
    public function limit($limit = 10)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function rewind()
    {
        $result = array(
            'ud' => array(),
            'ucl' => array(),
            'fd' => array(),
            'fcl' => array(),
        );

        if (is_null($this->_after)) {
            $search = "id >= 1"; 
        } else {
            $search = "id > " . intval($this->_after['id']);
        }

        foreach (UnitData::search($search)->searchIn('column_id', $this->_columns)->order('id ASC, column_id ASC')->limit($this->_limit) as $ud) {
            $result['ud'][] = array(
                'id' => $ud->id,
                'column_id' => $ud->column_id,
                'is_changelog' => 0,
                'is_fia' => false,
                'changelog_update_at' => 0,
                'value' => array($ud->value),
            );
        }
        foreach (UnitChangeLog::search($search)->searchIn('column_id', $this->_columns)->order('id ASC')->limit($this->_limit) as $ud) {
            $result['ucl'][] = array(
                'id' => $ud->id,
                'column_id' => $ud->column_id,
                'is_changelog' => 1,
                'is_fia' => false,
                'changelog_update_at' => $ud->updated_at,
                'value' => array($ud->old_value, $ud->new_value),
            );
        }
        if ($this->_fia_columns) {
            foreach (FIAUnitData::search($search)->searchIn('column_id', $this->_fia_columns)->order('id ASC, column_id ASC')->limit($this->_limit) as $ud) {
                $result['fd'][] = array(
                    'id' => $ud->id,
                    'column_id' => $ud->column_id,
                    'is_changelog' => 0,
                    'is_fia' => true,
                    'changelog_update_at' => 0,
                    'value' => array($ud->value),
                );
            }
            foreach (FIAUnitChangeLog::search($search)->searchIn('column_id', $this->_fia_columns)->order('id ASC')->limit($this->_limit) as $ud) {
                $result['fcl'][] = array(
                    'id' => $ud->id,
                    'column_id' => $ud->column_id,
                    'is_changelog' => 1,
                    'is_fia' => true,
                    'changelog_update_at' => $ud->updated_at,
                    'value' => array($ud->old_value, $ud->new_value),
                );
            }
        }

        foreach (array('ud', 'ucl') as $table) {
            // 如果抓不到 1000 表示已經是完整的了
            if (count($result[$table]) != $this->_limit) {
                continue;
            }

            $last_id = array_pop($result[$table])['id'];
            while ($result[$table][count($result[$table]) - 1]['id'] == $last_id) {
                array_pop($result[$table]);
            }
        }
        $min_final_id = min(array_map(function($records) { return $records[count($records) - 1]['id']; }, $result));
        foreach (array_keys($result) as $table) {
            while ($result[$table][count($result[$table]) - 1]['id'] > $min_final_id) {
                array_pop($result[$table]);
            }
        }

        $result = array_merge($result['ud'], $result['ucl'], $result['fd'], $result['fcl']);

        usort($result, function($a, $b) {
            if ($a['id'] != $b['id']) {
                return $a['id'] - $b['id'];
            }
            if ($a['column_id'] != $b['column_id']) {
                return $a['column_id'] - $b['column_id'];
            }
            if ($a['is_changelog'] != $b['is_changelog']) {
                return $a['is_changelog'] - $b['is_changelog'];
            }
            return $a['changelog_update_at'] - $b['changelog_update_at'];
        });
        return $result;
    }

    public function getVolumePos($row)
    {
        if (is_null($row)) {
            return null;
        }
        return array(
            'id' => $row['id'],
        );
    }

    public function getVolumeID()
    {
        return null;
    }
}

function getName($str)
{
    if (is_scalar($str)) {
        return $str;
    } elseif (is_array($str)) {
        if (is_scalar($str[0])) {
            return $str[0];
        }
        if ($str[0][1] == '(在臺灣地區公司名稱)') {
            return $str[0][0];
        }
    }
    throw new Exception("找不到名字" . json_encode($str));
}

// 先取得有哪些公司曾經有分公司過(這邊不用管排序)
if (file_exists('has_child.json')) {
    $has_child = json_decode(file_get_contents('has_child.json'), true);
} else {
    $has_child = array();
    foreach (new Pix_Array_Volume_ResultSet(new UnitColumnGetter(array(50)), array('chunk' => 10000, 'simple_mode' => true)) as $row) {
        foreach ($row['value'] as $v) {
            if ($v) {
                $n = intval(getName(json_decode($v)));
                $has_child[$n][] = $row['id'];
            }
        }
    }
    $has_child = array_map(function($r) { return array_values(array_unique($r)); }, $has_child);
    file_put_contents('has_child.json', json_encode($has_child, JSON_UNESCAPED_UNICODE));
}

unset($has_child[0]);

// 取得總公司名稱及身為總公司的狀態
if (file_exists('parent_names.json')) {
    $parent_names = json_decode(file_get_contents('parent_names.json'), true);
} else {
    $columns = array(
        2 => '公司名稱',
    );
    //$has_child = array_slice($has_child, 0, 10000, true);
    $cid = array_keys($columns);
    $parent_names = array();
    foreach (UnitChangeLog::search(array('column_id' => 2))->searchIn('id',  array_keys($has_child)) as $ud) {
        if (!$ud->id) {
            continue;
        }

        foreach (array('old_value', 'new_value') as $k) {
            $v = $ud->{$k};
            if (!json_decode($v)) {
                continue;
            }
            $n = strval(getName(json_decode($v)));
            if (!$n) {
                continue;
            }
            if (!array_key_exists($ud->id, $parent_names)) {
                $parent_names[$ud->id] = array();
            }
            $parent_names[$ud->id][$n] = $n;
        }
    }

    foreach (UnitData::search(array('column_id' => 2))->searchIn('id',  array_keys($has_child)) as $ud) {
        if (!$ud->id) {
            continue;
        }
        $v = $ud->value;
        $n = strval(getName(json_decode($v)));
        if (!$n) {
            continue;
        }
        if (!array_key_exists($ud->id, $parent_names)) {
            $parent_names[$ud->id] = array();
        }

        $parent_names[$ud->id][$n] = $n;
    }
    $parent_names = array_map('array_values', $parent_names);

    file_put_contents('parent_names.json', json_encode($parent_names, JSON_UNESCAPED_UNICODE));
}

$uni_name = function($name) {
    $name = str_replace('　', '', $name);
    $name = Unit::changeRareWord($name);
    $name = Unit::toNormalNumber($name);
    return $name;
};

$walk_unit_history = function($id, $history) use ($parent_names, $uni_name){
    $current = $history['current'];
    unset($history['current']);
    krsort($history);
    $values = array();
    foreach ($history as $t => $changelogs) {
        $values[$t] = json_decode(json_encode($current)); // copy current
        $values[$t]->updated_at = $t;

        foreach ($changelogs as $k => $v) {
            $current->{$k} = json_decode($v->old_value);
        }
    }
    $values[0] = $current;
    $values[0]->updated_at = $t;
    ksort($values);
    $values = array_values($values);

    $names = array();
    foreach ($values as $value) {
        $is_empty = true;
        foreach ($value as $k => $v) {
            if ('updated_at' == $k) {
                continue;
            }
            if (is_null($v)) {
                unset($value->{$k});
            } else {
                $is_empty = false;
            }
        }
        if ($is_empty) {
            continue;
        }
        if (property_exists($value, '營業人名稱')) {
            $name = getName($value->{'營業人名稱'});
            $name = $uni_name($name);
            $names[$name] = $name;
        }

        if (property_exists($value, '商業名稱')) {
            $name = getName($value->{'商業名稱'});
            $name = $uni_name($name);
            $names[$name] = $name;
        } elseif (property_exists($value, '分公司名稱')) {
            $parent_id = intval($value->{'總(本)公司統一編號'});
            if ($parent_id == 0) {
                continue;
            }
            if (!array_key_exists($parent_id, $parent_names)) {
                print_r($value);
                throw new Exception("找不到 {$id} 的 {$parent_id} 的母公司名稱");
            }
            foreach ($parent_names[$parent_id] as $n) {
                $name = $n . getName($value->{'分公司名稱'});
                $name = $uni_name($name);
                $names[$name] = $name;

                if ($parent_id == $id) {
                    $name = $n;
                    $name = $uni_name($name);
                    $names[$name] = $name;
                }
            }
        } elseif (property_exists($value, '公司名稱')) {
            $name = getName($value->{'公司名稱'});
            $name = $uni_name($name);
            $names[$name] = $name;
        } else {
            continue;
        }

    }

    foreach ($names as $name) {

        echo sprintf("%08d %s\n", $id, $name);
    }
};
$unit_history = array();
$columns = array(
    2 => '公司名稱',
    33 => '商業名稱',
    48 => '分公司名稱',
    50 => '總(本)公司統一編號',
);
$fia_columns = array(
    3 => '營業人名稱',
);
$unit_id = null;

foreach (new Pix_Array_Volume_ResultSet(new UnitColumnGetter(array_keys($columns), array_keys($fia_columns)), array('chunk' => 10000, 'simple_mode' => true)) as $row) {
    if (!$row['id']) {
        continue;
    }
    if (!is_null($unit_id) and $unit_id != $row['id']) {
        $walk_unit_history($unit_id, $unit_history);
        $unit_history = array();
    }
    $unit_id = $row['id'];

    if ($row['is_changelog']) {
        if (!array_key_exists($row['changelog_update_at'], $unit_history)) {
            $unit_history[$row['changelog_update_at']] = new StdClass;
        }
        $record = array(
            'old_value' => $row['value'][0],
            'new_value' => $row['value'][1],
            'column_id' => $row['column_id'],
            'updated_at' => $row['changelog_update_at'],
        );
        if ($row['is_fia']) {
            $c = $fia_columns[$record['column_id']];
        } else {
            $c = $columns[$record['column_id']];
        }
        $unit_history[$row['changelog_update_at']]->{$c} = new StdClass;
        foreach (array('updated_at', 'old_value', 'new_value') as $c2) {
            $unit_history[$row['changelog_update_at']]->{$c}->{$c2} = $record[$c2];
        }
    } else {
        if (!array_key_exists('current', $unit_history)) {
            $unit_history['current'] = new StdClass;
        }

        $record = array(
            'value' => $row['value'][0],
            'column_id' => $row['column_id'],
        );
        if ($row['is_fia']) {
            $c = $fia_columns[$record['column_id']];
        } else {
            $c = $columns[$record['column_id']];
        }
        $unit_history['current']->{$c} = json_decode($record['value']);
    }
}

if ($unit_history) {
    $walk_unit_history($unit_id, $unit_history);
    $unit_history = array();
}
