<?php

class UnitChangeLog extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unitchangelog';
        $this->_primary = array('id', 'updated_at', 'column_id');

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['updated_at'] = array('type' => 'int');
        $this->_columns['column_id'] = array('type' => 'tinyint');
        $this->_columns['old_value'] = array('type' => 'text');
        $this->_columns['new_value'] = array('type' => 'text');
    }
}
