<?php

class UnitRow extends Pix_Table_Row
{
    public function getNameColumns()
    {
        return array(
            5, // 代表人姓名
            16, // 訴訟及非訴訟代理人姓名
            11, // 董監事名單
            12, // 經理人名單
        );
    }

    public function id()
    {
        return str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    public function name($depth = 0)
    {
        $prefix = '';
        if (1 == $this->type) { // 公司
            $column_id = 2;
        } elseif (2 == $this->type) { // 商業登記
            $column_id = 33;
        } elseif (3 == $this->type) { // 分公司
            // 先取總公司
            $data = UnitData::search(array('id' => $this->id, 'column_id' => 50))->first();
            if (!$data) {
                return '';
            }
            $unit = Unit::find(json_decode($data->value));
            if (!$unit) {
                return '';
            }
            if ($depth) {
                return false;
            }
            $prefix = $unit->name($depth + 1);
            if (false === $prefix) {
                return '';
            }
            $column_id = 48;
        } else {
            $column_id = 43;
        }

        if ($data = UnitData::search(array('id' => $this->id, 'column_id' => $column_id))->first()) { // 公司名稱
            $v = json_decode($data->value);
            if (is_scalar($v)) {
                return $prefix . $v;
            } elseif (is_array($v)) {
                return $prefix . $v[0];
            }
        }
    }

    public function getNames()
    {
        $values = array();
        foreach (UnitData::search(array('id' => $this->id))->searchIn('column_id', $this->getNameColumns()) as $unit_data) { 
            $values[$unit_data->column_id] = json_decode($unit_data->value);
        }

        $names = array();
        // 代表人姓名, 訴訟及非訴訟代理人姓名
        foreach (array(5, 16) as $column_id) {
            if (!array_key_exists($column_id, $values)) {
                continue;
            }
            $value = $values[$column_id];

            if (is_scalar($value)) {
                $names[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $v) {
                    if (!is_scalar($v)) {
                        throw new Exception("unknown format, id={$this->id}, column_id={$column_id}");
                    }
                    $names[] = $v;
                }
            } else {
                throw new Exception("unknown format, id={$this->id}, column_id={$column_id}");
            }
        }

        // 經理人名單
        foreach (array(11, 12) as $column_id) {
            if (!array_key_exists($column_id, $values)) {
                continue;
            }
            $value = $values[$column_id];

            if (!is_array($value)) {
                throw new Exception("unknown format, id={$this->id}, column_id={$column_id}");
            }

            foreach ($value as $row) {
                if (!$row->{'姓名'}) {
                    throw new Exception("unknown format, id={$this->id}, column_id={$column_id}");
                }
                $names[] = $row->{'姓名'};
            }
        }

        return array_unique($names);
    }

    public function updateData($data)
    {
        $data = (array)$data;
        $old_data = array();
        foreach (UnitData::search(array('id' => $this->id)) as $unitdata) {
            $old_data[$unitdata->column_id] = $unitdata->value;
        }

        $add_data = $delete_data = $modify_data = array();
        foreach ($data as $column => $value) {
            $column_id = ColumnGroup::getColumnId($column);

            if (!array_key_exists($column_id, $old_data)) {
                $add_data[] = $column_id;
            } elseif (json_encode($value, JSON_UNESCAPED_UNICODE) != $old_data[$column_id]) {
                $modify_data[] = $column_id;
            }
        }

        foreach ($old_data as $column_id => $value) {
            if (!array_key_exists(ColumnGroup::getColumnName($column_id), $data)) {
                $delete_data[] = $column_id;
            }
        }

        if (count($add_data) + count($modify_data) + count($delete_data) == 0) {
            return;
        }
        $now = time();

        foreach ($add_data as $column_id) {
            $value = json_encode($data[ColumnGroup::getColumnName($column_id)], JSON_UNESCAPED_UNICODE);
            UnitData::insert(array(
                'id' => $this->id,
                'column_id' => $column_id,
                'value' => $value,
            ));
            UnitChangeLog::insert(array(
                'id' => $this->id,
                'updated_at' => $now,
                'column_id' => $column_id,
                'old_value' => '',
                'new_value' => $value,
            ));
        }

        foreach ($modify_data as $column_id) {
            $value = json_encode($data[ColumnGroup::getColumnName($column_id)], JSON_UNESCAPED_UNICODE);
            $unitdata = UnitData::find(array($this->id, $column_id));
            $unitdata->update(array(
                'value' => json_encode($data[ColumnGroup::getColumnName($column_id)], JSON_UNESCAPED_UNICODE),
            ));
            try {
                UnitChangeLog::insert(array(
                    'id' => $this->id,
                    'updated_at' => $now,
                    'column_id' => $column_id,
                    'old_value' => $old_data[$column_id],
                    'new_value' => $value,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

        foreach ($delete_data as $column_id) {
            try {
                UnitChangeLog::insert(array(
                    'id' => $this->id,
                    'updated_at' => $now,
                    'column_id' => $column_id,
                    'old_value' => $old_data[$column_id],
                    'new_value' => '',
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
            UnitData::find(array($this->id, $column_id))->delete();
        }
        $this->update(array('updated_at' => $now));
    }
}

class Unit extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unit';
        $this->_primary = 'id';
        $this->_rowClass = 'UnitRow';

        $this->_columns['id'] = array('type' => 'int', 'unsigned' => true);
        // 1 - 公司, 2 - 商業登記, 3 - 工廠登記, 4 - 教育部, 99 - 未知來源
        $this->_columns['type'] = array('type' => 'tinyint');
        $this->_columns['updated_at'] = array('type' => 'int');
    }
}
