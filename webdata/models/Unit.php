<?php

class UnitRow extends Pix_Table_Row
{
    protected static $_columns = null;

    public function getData()
    {
        if (is_null(self::$_columns)) {
            self::$_columns = array();
            foreach (ColumnGroup::search(1) as $columngroup) {
                self::$_columns[$columngroup->id] = $columngroup->name;
            }
        }

        $data = new StdClass;
        foreach (UnitData::search(array('id' => $this->id)) as $unitdata) {
            $data->{self::$_columns[$unitdata->column_id]} = json_decode($unitdata->value);
        }
        return $data;
    }

    public function updateSearch()
    {
        $curl = curl_init();
        $url = getenv('SEARCH_URL') . '/company/company/' . $this->id();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($this->getData()));
        $ret = curl_exec($curl);
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

    public function get($column)
    {
        return UnitData::search(array('id' => $this->id, 'column_id' => ColumnGroup::getColumnId($column)))->first();
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
