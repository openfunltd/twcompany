<?php

class User extends Pix_Table
{
    public function init()
    {
        $this->_name = 'user';

        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'char', 'size' => 32);
        $this->_columns['password'] = array('type' => 'char', 'size' => 32);

        $this->_indexes['name'] = array('type' => 'unique', 'columns' => array('name'));

        $this->_relations['articles'] = array('rel' => 'has_many', 'type' => 'Article', 'foreign_key' => 'user_id');
    }
}
