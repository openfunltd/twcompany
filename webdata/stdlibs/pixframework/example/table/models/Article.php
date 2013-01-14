<?php

class Article extends Pix_Table
{
    public function init()
    {
        $this->_name = 'article';

        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['user_id'] = array('type' => 'int');
        $this->_columns['post_at'] = array('type' => 'int');
        $this->_columns['title'] = array('type' => 'text');
        $this->_columns['body'] = array('type' => 'text');

        $this->_indexes['userid_postat'] = array('type' => 'unique', 'columns' => array('user_id', 'post_at'));
    }
}
