<?php

class FIAUnitChangeLogRow extends Pix_Table_Row
{
    public function preSave()
    {
        // 如果用到 Unicode 4.0 的字，在 MySQL 6.0 以前無法使用，因此在 json_encode 時就不要做 UNESCAPE
        if (preg_match('/([\x{0fffe}-\x{10ffff}]+)/u', $this->old_value)) {
            $this->old_value = json_encode(json_decode($this->old_value));
        }
        if (preg_match('/([\x{0fffe}-\x{10ffff}]+)/u', $this->new_value)) {
            $this->new_value = json_encode(json_decode($this->new_value));
        }
    }
}

class FIAUnitChangeLog extends Pix_Table
{
    public function init()
    {
        $this->_name = 'fia_unitchangelog';
        $this->_primary = array('id', 'updated_at', 'column_id');
        $this->_rowClass = 'FIAUnitChangeLogRow';

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['updated_at'] = array('type' => 'int');
        $this->_columns['column_id'] = array('type' => 'tinyint');
        $this->_columns['old_value'] = array('type' => 'text');
        $this->_columns['new_value'] = array('type' => 'text');
    }
}
