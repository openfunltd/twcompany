<?php

/**
 * Pix_Table_Db_Adapter_AmazonDynamoDb
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_AmazonDynamoDb extends Pix_Table_Db_Adapter_Abstract
{
    public function __construct()
    {
    }

    protected $_db = null;

    /**
     * _getDb 回傳 AmazonDynamoDB object, lazyconnection
     *
     * @access protected
     * @return AmazonDynamoDB
     */
    protected function _getDb()
    {
        if (is_null($this->_db)) {
            $this->_db = new AmazonDynamoDB();
        }
        return $this->_db;
    }

    /**
     * _getColumnType 回傳在這個 $table 的 $col columns 是 NUMBER 還是 STRING
     *
     * @param Pix_Table $table
     * @param string $col
     * @access protected
     * @return AmazonDynamoDB::TYPE_NUMBER or TYPE_STRING
     */
    protected function _getColumnType($table, $col)
    {
        if (!array_key_exists($col, $table->_columns)) {
            throw new Pix_Table_Exception("找不到{$col}這個column");
        }

        if (in_array($table->_columns[$col]['type'], array('int', 'bigint', 'tinyint'))) {
            return AmazonDynamoDB::TYPE_NUMBER;
        }
        return AmazonDynamoDB::TYPE_STRING;
    }

    /**
     * insertOne 在 $table 插入 $keys_values 資料
     *
     * @param Pix_Table $table
     * @param array $keys_values
     * @access public
     * @return null (不支援 auto_increment)
     */
    public function insertOne($table, $keys_values)
    {
        $db = $this->_getDb();
        $items = array();
        $excepted = array();
        $primary_values = array();
        foreach ($table->getPrimaryColumns() as $col) {
            if (!isset($keys_values[$col])) {
                throw new Pix_Table_Exception("沒有提供 {$col} column 的值");
            }
            $excepted[$col] = array('Exists' => false);
        }
        foreach ($keys_values as $key => $value) {
            $items[$key] = array($this->_getColumnType($table, $key) => $value);
        }
        $put_response = $db->put_item(array(
            'TableName' => $table->getTableName(),
            'Item' => $items,
            'Expected' => $excepted,
        ));
        if ($put_response->status == 400 and $put_response->body->__type == 'com.amazonaws.dynamodb.v20111205#ConditionalCheckFailedException') {
            throw new Pix_Table_DuplicateException();
        }

        if (200 != $put_response->status) {
            throw new Pix_Table_Exception("AmazonDynamoDB: " . $put_response->body->Message);
        }

        return;
    }

    /**
     * fetchOne 取得符合 $primary_values 的資料
     *
     * @param Pix_Table $table
     * @param array $primary_values
     * @access public
     * @return array | null
     */
    public function fetchOne($table, $primary_values)
    {
        $db = $this->_getDb();
        $primary_keys = $table->getPrimaryColumns();
        $get_key = array();
        if (count($primary_keys) == 1) {
            $get_key['HashKeyElement'] = array($this->_getColumnType($table, $primary_keys[0]) => $primary_values[0]);
        } elseif (count($primary_keys) == 2) {
            $get_key['HashKeyElement'] = array($this->_getColumnType($table, $primary_keys[0]) => $primary_values[0]);
            $get_key['RangeKeyElement'] = array($this->_getColumnType($table, $primary_keys[1]) => $primary_values[1]);
        } else {
            throw new Pix_Table_Exception("AmazonDynamoDB 只支援最多兩個 Primary Key 的 Table");
        }
        $get_response = $db->get_item(array(
            'TableName' => $table->getTableName(),
            'Key' => $get_key,
        ));
        if (200 != $get_response->status) {
            throw new Pix_Table_Exception("AmazonDynamoDB: " . $get_response->body->Message);
        }
        if (!$item = $get_response->body->Item) {
            return null;
        }
        $ret = array();
        foreach ($table->_columns as $name => $col) {
            if ($item->{$name}) {
                $ret[$name] = strval($item->{$name}->S);
            }
        }
        return $ret;
    }

    /**
     * deleteOne 在 DB 上刪掉 $row 的資料
     *
     * @param Pix_Table_Row $row
     * @access public
     * @return void
     */
    public function deleteOne($row)
    {
        $db = $this->_getDb();
        $table = $row->getTable();
        $primary_values = $row->getPrimaryValues();
        $primary_keys = $table->getPrimaryColumns();
        $get_key = array();
        if (count($primary_keys) == 1) {
            $get_key['HashKeyElement'] = array($this->_getColumnType($table, $primary_keys[0]) => $primary_values[0]);
        } elseif (count($primary_keys) == 2) {
            $get_key['HashKeyElement'] = array($this->_getColumnType($table, $primary_keys[0]) => $primary_values[0]);
            $get_key['RangeKeyElement'] = array($this->_getColumnType($table, $primary_keys[1]) => $primary_values[1]);
        } else {
            throw new Pix_Table_Exception("AmazonDynamoDB 只支援最多兩個 Primary Key 的 Table");
        }
        $get_response = $db->delete_item(array(
            'TableName' => $table->getTableName(),
            'Key' => $get_key,
        ));

        if (200 != $get_response->status) {
            throw new Pix_Table_Exception("AmazonDynamoDB: " . $get_response->body->Message);
        }
    }

    /**
     * fetch 取得符合 $search 條件的資料
     *
     * @param Pix_Table $table
     * @param Pix_Table_Search $search
     * @param string|array $select_columns
     * @access public
     * @return void
     */
    public function fetch($table, $search, $select_columns = '*')
    {
        $db = $this->_getDb();

        $condictions = $search->getSearchCondictions();
        if (count($condictions) == 0) { // 完全沒有條件就是 scan table
            $options = array();
            $options['TableName'] = $table->getTableName();

            // 加上指定 column
            if ('*' != $select_columns) {
                $options['AttributesToGet'] = $select_columns;
            }

            $response = $db->scan($options);
        } elseif (count($condictions) == 1) { // 只給一個條件的話只能是 HashKey
            $primary_keys = $table->getPrimaryColumns();

            // 只能是 map
            if ('map' != $condictions[0][0]) {
                throw new Pix_Table_Exception("不支援的 search 條件");
            }
            // 只能是 Primary Key 的第一個
            if ($primary_keys[0] != $condictions[0][1]) {
                throw new Pix_Table_Exception("不支援的 search 條件");
            }

            $options = array();
            $options['TableName'] = $table->getTableName();
            $options['HashKeyValue'] = array($this->_getColumnType($table, $primary_keys[0]) => $condictions[0][2]);

            // 加上 Limit
            if (!is_null($limit = $search->limit())) {
                $options['Limit'] = $limit;
            }

            // 加上 after or before
            if ($row = $search->after()) {
                $options['RangeKeyCondition'] = array(
                    'ComparisonOperator' => ($search->afterInclude() ? AmazonDynamoDB::CONDITION_GREATER_THAN_OR_EQUAL : AmazonDynamoDB::CONDITION_GREATER_THAN),
                    'AttributeValueList' => array(
                        array($this->_getColumnType($table, $primary_keys[1]) => $row->{$primary_keys[1]}),
                    ),
                );
            } elseif ($row = $search->before()) {
                $options['RangeKeyCondition'] = array(
                    'ComparisonOperator' => ($search->beforeInclude() ? AmazonDynamoDB::CONDITION_LESS_THAN_OR_EQUAL : AmazonDynamoDB::CONDITION_LESS_THAN),
                    'AttributeValueList' => array(
                        array($this->_getColumnType($table, $primary_keys[1]) => $row->{$primary_keys[1]}),
                    ),
                );
                $options['ScanIndexForward'] = false;
            }

            // 加上指定 column
            if ('*' != $select_columns) {
                $options['AttributesToGet'] = $select_columns;
            }

            $response = $db->query($options);
        } else {
            throw new Pix_Table_Exception("不支援的 search 條件");
        }

        if (200 != $response->status) {
            throw new Pix_Table_Exception("AmazonDynamoDB: " . $get_response->body->Message);
        }
        $ret = array();

        foreach ($response->body->Items[0] as $item) {
            $row = array();
            foreach ($table->_columns as $name => $col) {
                if ($item->{$name}) {
                    $row[$name] = strval($item->{$name}->S);
                }
            }
            $ret[] = $row;
        }
        return $ret;
    }

    /**
     * updateOne 從 db 上更新一個 $row 的 data
     *
     * @param Pix_Table_Row $row
     * @param array $data
     * @access public
     * @return void
     */
    public function updateOne($row, $data)
    {
        if (!is_array($data)) {
            throw new Pix_Table_Exception('Pix_Table_Db_Adapter_AmazonDynamoDb 只允許提供 update->(array)');
        }

        $table = $row->getTable();
        $primary_values = $row->getPrimaryValues();
        $primary_keys = $table->getPrimaryColumns();
        $get_key = array();
        if (count($primary_keys) == 1) {
            $get_key['HashKeyElement'] = array($this->_getColumnType($table, $primary_keys[0]) => $primary_values[0]);
        } elseif (count($primary_keys) == 2) {
            $get_key['HashKeyElement'] = array($this->_getColumnType($table, $primary_keys[0]) => $primary_values[0]);
            $get_key['RangeKeyElement'] = array($this->_getColumnType($table, $primary_keys[1]) => $primary_values[1]);
        } else {
            throw new Pix_Table_Exception("AmazonDynamoDB 只支援最多兩個 Primary Key 的 Table");
        }

        $updates = array();
        foreach ($data as $key => $value) {
            $updates[$key] = array(
                'Action' => AmazonDynamoDB::ACTION_PUT,
                'Value' => array($this->_getColumnType($table, $key) => $value),
            );
        }
        $db = $this->_getDb();
        $db->update_item(array(
            'TableName' => $table->getTableName(),
            'Key' => $get_key,
            'AttributeUpdates' => $updates,
        ));
    }
}
