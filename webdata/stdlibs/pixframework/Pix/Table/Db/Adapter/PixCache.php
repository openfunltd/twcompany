<?php

/**
 * Pix_Table_Db_Adapter_PixCache
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_PixCache extends Pix_Table_Db_Adapter_Abstract
{
    protected $_cache;
    protected $_prefix;

    public function __construct($cache, $prefix = 'PixTableDbAdapterPixCache:')
    {
        $this->_cache = $cache;
        $this->_prefix = $prefix;
    }

    /**
     * createTable 將 $table 建立進資料庫內
     * 
     * @param Pix_Table $table 
     * @access public
     * @return void
     */
    public function createTable($table)
    {
    }

    /**
     * dropTable 從資料庫內移除 $table 這個 Table
     * 
     * @param Pix_Table $table 
     * @access public
     * @return void
     */
    public function dropTable($table)
    {
    }

    protected function getKey($primary_values)
    {
        $keys = array();
        foreach ($primary_values as $v) {
            $keys[] = urlencode($v);
        }
        $key = $this->_prefix . implode('&', $keys);
        return $key;
    }

    public function fetchOne($table, $primary_values)
    {
        $c = $this->_cache;

        $keys = array();
        foreach ($table->_columns as $col => $col_options) {
            // PK 不用抓
            if (in_array($col, $table->getPrimaryColumns())) {
                continue;
            }
            $keys[] = $this->getKey($primary_values) . ':' . $col;
        }

        if (!$values = $c->gets($keys)) {
            return null;
        }

        $return_values = array_combine($table->getPrimaryColumns(), $primary_values);
        foreach ($table->_columns as $col => $col_options) {
            // PK 不用抓
            if (in_array($col, $table->getPrimaryColumns())) {
                continue;
            }
            $return_values[$col] = $values[$this->getKey($primary_values) . ':' . $col];
        }

        return $return_values;
    }

    public function deleteOne($row)
    {
        $table = $row->getTable();
        $c = $this->_cache;
        foreach ($table->_columns as $col => $col_options) {
            // PK 不用抓
            if (in_array($col, $table->getPrimaryColumns())) {
                continue;
            }
            $c->delete($this->getKey($row->getPrimaryValues()) . ':' . $col);
        }
    }

    public function updateOne($row, $data)
    {
        $c = $this->_cache;
        if (!is_array($data)) {
            throw new Exception('must array');
        }
        $table = $row->getTable();
        $update_keys_values = array();
        foreach ($data as $key => $value) {
            if (!$table->_columns[$key]) {
                throw new Exception($table->getClass() . " column {$key} not found");
            }
            // PK 不用抓
            if (in_array($key, $table->getPrimaryColumns())) {
                continue;
            }
            $update_keys_values[$this->getKey($row->getPrimaryValues()) . ':' . $key] = $value;
        }
        $c->sets($update_keys_values);
    }

    public function insertOne($table, $keys_values)
    {
        $c = $this->_cache;
        $row = $table->createRow();
        foreach ($keys_values as $k => $v) {
            $row->{$k} = $v;
        }
        $update_keys_values = array();
        foreach ($row->toArray() as $key => $value) {
            if (!$table->_columns[$key]) {
                throw new Exception("column {$key} not found");
            }
            if (in_array($key, $table->getPrimaryColumns())) {
                continue;
            }
            $update_keys_values[$this->getKey($row->findPrimaryValues()) . ':' . $key] = $value;
        }
        $c->sets($update_keys_values);
    }
}
