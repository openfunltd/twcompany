<?php

class ColumnGroup extends Pix_Table
{
    public function init()
    {
        $this->_name = 'columngroup';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 32);

        $this->addIndex('name', array('name'), 'unique');
    }
}
