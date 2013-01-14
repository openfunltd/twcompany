<?php

class Pix_Table_TableCacheTest_User extends Pix_Table
{
    public function init()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
    }
}

class Pix_Table_TableCacheTest_Cache extends Pix_Cache
{
    protected $_data = array();

    public function load($key)
    {
        // Pix_Cache 如果沒有 cache 資料是會傳 false
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        return false;
    }

    public function save($key, $value)
    {
        $this->_data[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->_data[$key]);
    }
}

class Pix_Table_TableCacheTest extends PHPUnit_Framework_TestCase
{
    protected $_old_cache;
    protected $_old_save_memory;

    public function setUp()
    {
        $this->_old_cache = Pix_Table::getCache();
        $this->_old_save_memory = Pix_Table::$_save_memory;
    }

    public function tearDown()
    {
        Pix_Table::setCache($this->_old_cache);
        Pix_Table::$_save_memory = $this->_old_save_memory;
    }

    public function testDisableCache()
    {
        Pix_Table::$_save_memory = true;

        $cache = $this->getMock('Pix_Cache', array('load'));
        $cache->expects($this->never())
            ->method('load');
        Pix_Table::setCache($cache);

        $table = Pix_Table::getTable('Pix_Table_TableCacheTest_User');
        $table->disableTableCache();
        $this->assertEquals($table->getRowFromCache(array(1)), false);
    }

    public function testEnableCacheSaveMemory()
    {
        Pix_Table::$_save_memory = true;

        $cache = new Pix_Table_TableCacheTest_Cache;
        Pix_Table::setCache($cache);

        $table = Pix_Table::getTable('Pix_Table_TableCacheTest_User');
        $table->enableTableCache();

        $table->cacheRow(1, array('id' => 1, 'name' => 'abc', 'password' => 'def'));

        $row = $table->getRowFromCache(1);
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'abc');
        $this->assertEquals($row->password, 'def');
    }

    public function testNullOrFalseSaveMemory()
    {
        Pix_Table::$_save_memory = true;

        $cache = new Pix_Table_TableCacheTest_Cache;
        Pix_Table::setCache($cache);

        $table = Pix_Table::getTable('Pix_Table_TableCacheTest_User');
        $table->enableTableCache();

        // 什麼都沒有是 false
        $this->assertTrue($table->getRowFromCache(2) === false);
        // cacheRow null 後是 null (表示被刪除)
        $table->cacheRow(3, null);
        $this->assertTrue($table->getRowFromCache(3) === null);
        // cacheRow false 後是 false
        $table->cacheRow(4, false);
        $this->assertTrue($table->getRowFromCache(4) === false);
    }

    public function testEnableCache()
    {
        Pix_Table::$_save_memory = false;

        $cache = new Pix_Table_TableCacheTest_Cache;
        Pix_Table::setCache($cache);

        $table = Pix_Table::getTable('Pix_Table_TableCacheTest_User');
        $table->enableTableCache();

        $table->cacheRow(5, array('id' => 5, 'name' => 'abc', 'password' => 'def'));

        $row = $table->getRowFromCache(5);
        $this->assertEquals($row->id, 5);
        $this->assertEquals($row->name, 'abc');
        $this->assertEquals($row->password, 'def');
    }

    public function testNullOrFalse()
    {
        Pix_Table::$_save_memory = false;

        $cache = new Pix_Table_TableCacheTest_Cache;
        Pix_Table::setCache($cache);

        $table = Pix_Table::getTable('Pix_Table_TableCacheTest_User');
        $table->enableTableCache();

        // 什麼都沒有是 false
        $this->assertTrue($table->getRowFromCache(6) === false);
        // cacheRow null 後是 null (表示被刪除)
        $table->cacheRow(7, null);
        $this->assertTrue($table->getRowFromCache(7) === null);
        // cacheRow false 後是 false
        $table->cacheRow(8, false);
        $this->assertTrue($table->getRowFromCache(8) === false);
    }

}
