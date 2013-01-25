<?php

class NameMap extends Pix_Table
{
    public function init()
    {
        $this->_name = 'name_map';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['unique'] = array('type' => 'tinyint', 'default' => 0);

        $this->addIndex('name_unique', array('name', 'unique'), 'unique');
    }
}
