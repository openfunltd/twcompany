<?php

class UnitData extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unit_data';
        $this->_primary = array('id', 'column_id');

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['column_id'] = array('type' => 'tinyint');
        $this->_columns['value'] = array('type' => 'text');
    }
}
