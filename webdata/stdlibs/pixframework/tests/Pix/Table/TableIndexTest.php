<?php

class Pix_Table_TestIndexTest_User extends Pix_Table
{
    public function init()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
	$this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

	$this->addIndex('name', array('name'), 'unique');
    }
}

class Pix_Table_TestIndexTest_Article extends Pix_Table
{
    public function init()
    {
	$this->_name = 'article';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['user_id'] = array('type' => 'int');
	$this->_columns['time'] = array('type' => 'int');
	$this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

	$this->_relations['user'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestIndexTest_User', 'foreign_key' => 'user_id', 'delete' => true);

	$this->addIndex('userid_time', array('user_id', 'time'));
    }
}


class Pix_Table_TableIndexTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
	$this->assertEquals('PRIMARY', Pix_Table_TestIndexTest_User::findUniqueKey(array('id')));
	$this->assertEquals('PRIMARY', Pix_Table_TestIndexTest_Article::findUniqueKey(array('id', 'user_id')));
	$this->assertEquals(null, Pix_Table_TestIndexTest_Article::findUniqueKey(array('user_id')));
	$this->assertEquals('name', Pix_Table_TestIndexTest_User::findUniqueKey(array('name')));
        $this->assertEquals('name', Pix_Table_TestIndexTest_User::findUniqueKey(array('name', 'password')));

        $this->assertEquals(Pix_Table_TestIndexTest_Article::getIndexColumns('userid_time'), array('user_id', 'time'));
        $this->assertEquals(Pix_Table_TestIndexTest_Article::getIndexColumns('PRIMARY'), array('id'));
    }
}
