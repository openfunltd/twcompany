<?php

class UnitDataRow extends Pix_Table_Row
{
    public function preSave()
    {
        // 如果用到 Unicode 4.0 的字，在 MySQL 6.0 以前無法使用，因此在 json_encode 時就不要做 UNESCAPE
        if (preg_match('/([\x{0fffe}-\x{10ffff}]+)/u', $this->value)) {
            $this->value = json_encode(json_decode($this->value));
        }
    }
}

class UnitData extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unit_data';
        $this->_primary = array('id', 'column_id');
        $this->_rowClass = 'UnitDataRow';

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['column_id'] = array('type' => 'tinyint');
        $this->_columns['value'] = array('type' => 'text');
    }
}
