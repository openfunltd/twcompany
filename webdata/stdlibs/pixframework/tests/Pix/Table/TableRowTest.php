<?php

class Pix_Table_TableRowTest_TableRow extends Pix_Table_Row
{
    public function get_hook_value_add_3()
    {
        return $this->value + 3;
    }

    public function set_hook_value_add_3($v)
    {
        $this->value = $v + 3;
    }

    public function preDelete()
    {
        if ($this->value == 'preDelete_stop') {
            return $this->stop();
        }
    }

    public function preSave()
    {
        if ($this->value == 'preSave_stop') {
            return $this->stop();
        }
    }

    public function preUpdate($changed_fields = null)
    {
        if ($this->value == 'preUpdate_stop') {
            return $this->stop();
        }
    }

    public function preInsert()
    {
        if ($this->value == 'preInsert_stop') {
            return $this->stop();
        }
    }
}

class Pix_Table_TableRowTest_Table extends Pix_Table
{
    public function init()
    {
        $this->_name = 'table';
        $this->_primary = 't1_id';
        $this->_rowClass = 'Pix_Table_TableRowTest_TableRow';

        $this->_columns['t1_id'] = array('type' => 'int', 'auto_increment' => true, 'unsigned' => true);
        $this->_columns['value'] = array('type' => 'text', 'default' => 'default');

        $this->_hooks['hook_value_add_3'] = array('get' => 'get_hook_value_add_3', 'set' => 'set_hook_value_add_3');
        $this->_hooks['hook_value_add_5'] = array('get' => function($row){ return $row->value + 5; }, 'set' => function($row, $value){ $row->value = $value + 5; });
        $this->_hooks['hook_no_get_and_set'] = array();
        $this->_hooks['hook_invalid_get_and_set'] = array('get' => new StdClass, 'set' => new StdClass);
    }
}

class Pix_Table_TableRowTest_Table2 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'table2';
        $this->_primary = 't2_id';

        $this->_columns['t2_id'] = array('type' => 'int');
        $this->_columns['value'] = array('type' => 'text', 'default' => 'default');
    }
}

class Pix_Table_TableRowTest_Table3 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'table3';
        $this->_primary = array('t3_id', 't3_id2');

        $this->_columns['t3_id'] = array('type' => 'int');
        $this->_columns['t3_id2'] = array('type' => 'int');
        $this->_columns['value'] = array('type' => 'enum', 'list' => array('on', 'off'));
    }
}

class Pix_Table_TableRowTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testEquals()
    {
        $row = new Pix_Table_Row(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1003, 'value' => 'abc')
        ));
        $row->t1_id = 1003;

        $this->assertTrue($row->equals(1003));
        $this->assertFalse($row->equals(998));

        $this->assertTrue($row->equals(array(1003)));
        $this->assertFalse($row->equals(array(998)));

        $row2 = new Pix_Table_Row(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1003, 'value' => 'abc')
        ));
        $row3 = new Pix_Table_Row(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 998, 'value' => 'abc')
        ));

        $this->assertTrue($row->equals($row2));
        $this->assertFalse($row->equals($row3));
    }

    /**
     * 測試 equals 是不是傳了不同的 Table
     * @expectedException           Pix_Table_Exception
     */
    public function testEqualsWrongTable()
    {
        $row = new Pix_Table_Row(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1004, 'value' => 'abc')
        ));
        $row2 = new Pix_Table_Row(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table2',
            'data' => array('t2_id' => 1004, 'value' => 'abc')
        ));
        $row->equals($row2);
    }

    public function testUpdate()
    {
        $db = $this->getMock('Pix_Table_Db_Adapter_Abstract', array('updateOne'));
        $table = Pix_Table::getTable('Pix_Table_TableRowTest_Table');

        $row = new Pix_Table_Row(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1005, 'value' => 'abc')
        ));

        // array
        $db->expects($this->once())
            ->method('updateOne')
            ->with($this->isInstanceOf(get_class($row)), array('value' => '9'))
            ->will($this->returnValue(null));

        Pix_Table_TableRowTest_Table::setDb($db);

        $row->update(array('value' => '9'));
        $this->assertEquals($row->value, '9');

        // string
        $db = $this->getMock('Pix_Table_Db_Adapter_Abstract', array('updateOne', 'fetchOne', 'support'));

        $db->expects($this->any())
            ->method('support')
            ->with($this->logicalOr('immediate_consistency', 'force_master'))
            ->will($this->returnValue(true));

        $db->expects($this->once())
            ->method('updateOne')
            ->with($this->isInstanceOf(get_class($row)), 'value = value + 1')
            ->will($this->returnValue(null));

        $db->expects($this->once())
            ->method('fetchOne')
            ->with($this->isInstanceOf('Pix_Table_TableRowTest_Table', array(1005)))
            ->will($this->returnValue(array('t1_id' => 1005, 'value' => '10')));

        Pix_Table_TableRowTest_Table::setDb($db);

        $row->update("value = value + 1");
        $this->assertEquals($row->value, 10);
    }

    public function testUpdateStop()
    {
        $db = $this->getMock('Pix_Table_Db_Adapter_Abstract', array('updateOne', 'fetchOne'));

        $row = new Pix_Table_TableRowTest_TableRow(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1002, 'value' => 'oldvalue')
        ));

        $db->expects($this->never())
            ->method('updateOne');
        $db->expects($this->never())
            ->method('fetchOne');
        Pix_Table_TableRowTest_Table::setDb($db);

        // 未修改的情況下，Save 無效 
        $row->save();
        // preSave return $this->stop(), save 無效
        $row->value = 'preSave_stop';
        $row->update("value = value + 1");
        // preSave return $this->stop(), update 無效
        $row->update(array('value' => 'preSave_stop'));
        // preUpdate return $this->stop(), update 無效
        $row->value = 'preUpdate_stop';
        $row->save();
    }

    public function testInsertStop()
    {
        $db = $this->getMock('Pix_Table_Db_Adapter_Abstract', array('insertOne'));
        $db->expects($this->never())
            ->method('insertOne');

        Pix_Table_TableRowTest_Table::setDb($db);
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->value = 'preInsert_stop';
        $row->save();

    }

    public function testDelete()
    {
        $db = $this->getMock('Pix_Table_Db_Adapter_Abstract', array('deleteOne'));

        $row = new Pix_Table_TableRowTest_TableRow(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1000, 'value' => 'delete_me')
        ));

        $db->expects($this->once())
            ->method('deleteOne')
            ;

        Pix_Table_TableRowTest_Table::setDb($db);

        $row->delete();
    }

    public function testDeleteStop()
    {
        $db = $this->getMock('Pix_Table_Db_Adapter_Abstract', array('deleteOne', 'fetchOne'));

        $row = new Pix_Table_TableRowTest_TableRow(array(
            'tableClass' => 'Pix_Table_TableRowTest_Table',
            'data' => array('t1_id' => 1001, 'value' => 'preDelete_stop')
        ));

        $db->expects($this->never())
            ->method('deleteOne');
        $db->expects($this->never())
            ->method('fetchOne');
        Pix_Table_TableRowTest_Table::setDb($db);

        $row->delete();
    }


    /**
     * 測試還沒有被存進 db 的資料刪除時會不會丟 Exception
     * @expectedException           Pix_Table_Exception
     */
    public function testDeleteNotInDatabase()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->delete();
    }

    public function testHook()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->value = 5;
        $this->assertEquals($row->hook_value_add_3, 8);

        $row->hook_value_add_3 = 10;
        $this->assertEquals($row->value, 13);

        $row->value = 8;
        $this->assertEquals($row->hook_value_add_5, 13);

        $row->hook_value_add_5 = 20;
        $this->assertEquals($row->value, 25);
    }

    /**
     * 測試 construct 沒指定 tableClass 時要噴 Exception
     * @expectedException           Pix_Table_Exception
     */
    public function testConstructNoTableClass()
    {
        new Pix_Table_TableRowTest_TableRow(array());
    }

    /**
     * 測試 construct 有給 data 但是 primary value 不足夠要噴 Exception
     * @expectedException           Pix_Table_Exception
     */
    public function testConstructNoAllPrimaryValues()
    {
        new Pix_Table_TableRowTest_TableRow(array('tableClass' => 'Pix_Table_TableRowTest_Table', 'data' => array('value' => 'foo')));
    }

    /**
     * 測試 construct 有給 data 但是 primary value 不足夠要噴 Exception
     * @expectedException           Pix_Table_Row_InvalidFormatException
     */
    public function testListColumnInvalidValue()
    {
        $row = Pix_Table_TableRowTest_Table3::createRow();
        $row->value = 'abc';
    }

    /**
     * 測試 int column 不能被指定為英文字
     * @expectedException           Pix_Table_Row_InvalidFormatException
     */
    public function testInvalidInteger()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->t1_id = 'abc';
    }

    /**
     * 測試 unsigned int column 不能被指定為負數
     * @expectedException           Pix_Table_Row_InvalidFormatException
     */
    public function testInvalidUnsignedInteger()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->t1_id = -3;
    }

    public function testValidValue()
    {
        // 沒指定 unsigned = true 就可以用負數
        $row = Pix_Table_TableRowTest_Table2::createRow();
        $row->t2_id = -3;

        // 指定了 enum 但是符合也可以
        $row = Pix_Table_TableRowTest_Table3::createRow();
        $row->value = 'on';
    }

    /**
     * 沒指定 getter 的 hook 要噴 exception
     * @expectedException           Pix_Table_Exception
     */
    public function testNoGetterHook()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $return = $row->hook_no_get_and_set;
    }

    /**
     * 沒指定 setter 的 hook 要噴 exception
     * @expectedException           Pix_Table_Exception
     */
    public function testNoSetterHook()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->hook_no_get_and_set = 3;
    }

    /**
     * 沒指定 getter 的 hook 要噴 exception
     * @expectedException           Pix_Table_Exception
     */
    public function testInvalidGetterHook()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $return = $row->hook_invalid_get_and_set;
    }

    /**
     * 沒指定 setter 的 hook 要噴 exception
     * @expectedException           Pix_Table_Exception
     */
    public function testInvalidSetterHook()
    {
        $row = Pix_Table_TableRowTest_Table::createRow();
        $row->hook_invalid_get_and_set = 3;
    }
}
