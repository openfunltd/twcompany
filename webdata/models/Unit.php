<?php

class UnitRow extends Pix_Table_Row
{
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
            UnitChangeLog::insert(array(
                'id' => $this->id,
                'updated_at' => $now,
                'column_id' => $column_id,
                'old_value' => $old_data[$column_id],
                'new_value' => $value,
            ));
        }

        foreach ($delete_data as $column_id) {
            UnitChangeLog::insert(array(
                'id' => $this->id,
                'updated_at' => $now,
                'column_id' => $column_id,
                'old_value' => $old_data[$column_id],
                'new_value' => '',
            ));
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
        // 1 - 公司, 2 - 商業登記, 3 - 工廠登記
        $this->_columns['type'] = array('type' => 'tinyint');
        $this->_columns['updated_at'] = array('type' => 'int');
    }
}
