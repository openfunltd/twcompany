<?php

class Pix_Table_TableHelperTest_TableResultSet extends Pix_Table_ResultSet
{
    protected $_value;

    public function setData($value)
    {
        $this->_value = $value;
    }

    public function getData()
    {
        return $this->_value;
    }
}

class Pix_Table_TableHelperTest_Table extends Pix_Table
{
    public function init()
    {
        $this->_resultSetClass = 'Pix_Table_TableHelperTest_TableResultSet';

        $this->_columns['id'] = array('type' => 'varchar', 'size' => 32);
    }

    protected static $_value;

    public static function setData($value)
    {
        self::$_value = $value;
    }

    public static function getData()
    {
        return self::$_value;
    }
}

class Pix_Table_TableHelperTest_Helper extends Pix_Helper
{
    public function row_test($row, $value)
    {
        return 'RowHelper-' . $row->id . '-' . $value;
    }

    public function static_row_test($row, $value)
    {
        return 'StaticRowHelper-' . $row->id . '-' . $value;
    }

    public function resultset_test($resultset, $value)
    {
        return 'ResultSetHelper-' . $resultset->getData() . '-' . $value;
    }

    public function static_resultset_test($resultset, $value)
    {
        return 'StaticResultSetHelper-' . $resultset->getData() . '-' . $value;
    }

    public function table_test($table, $value)
    {
        return 'TableHelper-' . $table->getData() . '-' . $value;
    }

    public function static_table_test($table, $value)
    {
        return 'StaticTableHelper-' . $table->getData() . '-' . $value;
    }
}

class Pix_Table_TableHelperTest extends PHPUnit_Framework_TestCase
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

    /**
     * testRowHelper test add Row Helper in a Table
     */
    public function testRowHelper()
    {
        Pix_Table_TableHelperTest_Table::addRowHelper('Pix_Table_TableHelperTest_Helper', array('row_test'));

        $row = Pix_Table_TableHelperTest_Table::createRow();
        $row->id = uniqid();
        $uniqid = uniqid();
        $this->assertEquals($row->row_test($uniqid), 'RowHelper-' . $row->id . '-' . $uniqid);
    }

    /**
     * testStaticRowHelper test add Row Helper to all Pix_Table
     */
    public function testStaticRowHelper()
    {
        Pix_Table::addStaticRowHelper('Pix_Table_TableHelperTest_Helper', array('static_row_test'));

        $row = Pix_Table_TableHelperTest_Table::createRow();
        $row->id = uniqid();
        $uniqid = uniqid();
        $this->assertEquals($row->static_row_test($uniqid), 'StaticRowHelper-' . $row->id . '-' . $uniqid);
    }

    /**
     * testResultSetHelper test add ResultSet Helper in a Table
     */
    public function testResultSetHelper()
    {
        Pix_Table_TableHelperTest_Table::addResultSetHelper('Pix_Table_TableHelperTest_Helper', array('resultset_test'));

        $resultset = Pix_Table_TableHelperTest_Table::search(1);
        $uniqid = uniqid();
        $resultset->setData(uniqid());
        $this->assertEquals($resultset->resultset_test($uniqid), 'ResultSetHelper-' . $resultset->getData() . '-' . $uniqid);
    }

    /**
     * testStaticResultSetHelper test add ResultSet Helper to all Pix_Table
     */
    public function testStaticResultSetHelper()
    {
        Pix_Table::addStaticResultSetHelper('Pix_Table_TableHelperTest_Helper', array('static_resultset_test'));

        $resultset = Pix_Table_TableHelperTest_Table::search(1);
        $uniqid = uniqid();
        $resultset->setData(uniqid());
        $this->assertEquals($resultset->static_resultset_test($uniqid), 'StaticResultSetHelper-' . $resultset->getData() . '-' . $uniqid);
    }

    /**
     * testTableHelper test add Table Helper in a Table
     */
    public function testTableHelper()
    {
        Pix_Table_TableHelperTest_Table::addTableHelper('Pix_Table_TableHelperTest_Helper', array('table_test'));

        Pix_Table_TableHelperTest_Table::setData(uniqid());
        $uniqid = uniqid();

        $this->assertEquals(Pix_Table_TableHelperTest_Table::table_test($uniqid), 'TableHelper-' . Pix_Table_TableHelperTest_Table::getData() . '-' . $uniqid);
    }

    /**
     * testStaticTableHelper test add Table Helper to all Pix_Table
     */
    public function testStaticTableHelper()
    {
        Pix_Table::addStaticTableHelper('Pix_Table_TableHelperTest_Helper', array('static_table_test'));

        Pix_Table_TableHelperTest_Table::setData(uniqid());
        $uniqid = uniqid();

        $this->assertEquals(Pix_Table_TableHelperTest_Table::static_table_test($uniqid), 'StaticTableHelper-' . Pix_Table_TableHelperTest_Table::getData() . '-' . $uniqid);
    }
}
