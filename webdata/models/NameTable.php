<?php

class NameTable extends Pix_Table
{
    public function init()
    {
        $this->_name = 'name_table';
        $this->_primary = array('name_id', 'unit_id');

        $this->_columns['name_id'] = array('type' => 'int');
        $this->_columns['unit_id'] = array('type' => 'int');
    }
}
