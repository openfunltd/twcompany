<?php

/**
 * Pix_Table_Db_Adapter_SQL
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_SQL extends Pix_Table_Db_Adapter_Abstract
{
    /**
     * fetchOne 從 $table 找出符合 $primary_values 條件的一筆
     * 
     * @param Pix_Table $table 
     * @param array $primary_values
     * @access public
     * @return array or null 
     */
    public function fetchOne($table, $primary_values, $select_columns = '*')
    {
        $select_expression = $this->_getSelectExpression($table, $select_columns);

        $sql = 'SELECT ' . $select_expression . ' FROM ' . $this->column_quote($table->getTableName());
        $sql .= ' WHERE ';
        $terms = array();
        foreach (array_combine($table->getPrimaryColumns(), $primary_values) as $k => $v) {
            $terms[] = $this->column_quote($k) . ' = ' . $this->quoteWithColumn($table, $v, $k);
        }
        $sql .= implode(' AND ', $terms);

        if (!$res = $this->query($sql)) {
            return null;
        }

        $row = $res->fetch_assoc();
        $res->free_result();
        return $this->_filterRow($row);
    }

    /**
     * fetchCount 從 $table 找出符合 $search 條件的數量
     *
     * @param Pix_Table $table
     * @param mixed $search
     * @access public
     * @return int
     */
    public function fetchCount($table, $search)
    {
        $sql = "SELECT COUNT(*) AS " . $this->column_quote('count') . " FROM " . $this->column_quote($table->getTableName());
        $sql .= ' WHERE ';
        $sql .= $this->_get_where_clause($search, $table);

        $res = $this->query($sql);
        $row = $res->fetch_assoc();
        $res->free_result();
	return $row['count'];
    }

    /**
     * fetchSum 從 $table 找出符合 $search 數量的 $column 總合
     *
     * @param Pix_Table $table
     * @param string $column
     * @param Pix_Table_Search $search
     * @access public
     * @return int
     */
    public function fetchSum($table, $column, $search)
    {
        $sql = "SELECT SUM(" . $this->column_quote($column) . ") AS " . $this->column_quote('sum') . " FROM " . $this->column_quote($table->getTableName());
        $sql .= ' WHERE ';
        $sql .= $this->_get_where_clause($search, $table);

	$res = $this->query($sql);
        $row = $res->fetch_assoc();
        $res->free_result();
	return intval($row['sum']);
    }

    /**
     * filter database raw data to Pix_Table_Row data
     *
     * @param array $row
     * @access protected
     * @return array
     */
    protected function _filterRow($row)
    {
        if (!is_array($row)) {
            return $row;
        }
        $return_row = array();

        foreach ($row as $col => $value) {
            if (FALSE === strpos($col, ':')) {
                $return_row[$col] = $value;
                continue;
            }

            list($col, $id) = explode(':', $col, 2);
            $return_row[$col][$id] = $value;
        }
        return $return_row;
    }

    /**
     * check $table has special column or not
     *
     * @param Pix_Table $table
     * @access protected
     * @return boolean
     */
    protected function _hasSpecialColumns($table)
    {
        foreach ($table->_columns as $options) {
            if (in_array($options['type'], array('geography', 'geometry'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * get select expression for SELECT
     *
     * @param Pix_Table $table
     * @param string|array $select_columns
     * @access protected
     * @return string
     */
    protected function _getSelectExpression($table, $select_columns = '*')
    {
        if ('*' == $select_columns) {
            if ($this->_hasSpecialColumns($table)) {
                $select_columns = array_keys($table->_columns);
            } else {
                return '*';
            }
        } elseif (is_scalar($select_columns)) {
            $select_columns = array($select_columns);
        }

        if (is_array($select_columns)) {
            $cols = array();
            foreach ($select_columns as $col) {
                $cols[] = $this->column_quote($col, $table);
            }
            $select_expression = implode(', ', $cols);
        }

        return $select_expression;

    }

    /**
     * fetch 從 $table 找出符合 $search 的所有 column
     * 
     * @param Pix_Table $table 
     * @param Pix_Table_Search $search 
     * @param string $select_columns 
     * @access public
     * @return array
     */
    public function fetch($table, $search, $select_columns = '*')
    {
        $select_expression = $this->_getSelectExpression($table, $select_columns);

        $sql = 'SELECT ' . $select_expression . ' FROM ' . $this->column_quote($table->getTableName());
        if ($search->index()) {
            $sql .= " USE INDEX (" . $search->index() . ") ";
        }
        $sql .= ' WHERE ';
        $sql .= $this->_get_where_clause($search, $table);
        $sql .= $this->_get_clause($search);

	$res = $this->query($sql);
	$rows = array();
	while ($row = $res->fetch_assoc()) {
            $rows[] = $this->_filterRow($row);
	}
	$res->free_result();

	return $rows;
    }

    /**
     * deleteOne 從 db 上刪除一個 Row
     * 
     * @param Pix_Table_Row $row 
     * @access public
     * @return void
     */
    public function deleteOne($row)
    {
        $table = $row->getTable();
        $sql = 'DELETE FROM ' . $this->column_quote($table->getTableName());
        $sql .= ' WHERE ';
        $sql .= $this->_get_where_clause(Pix_Table_Search::factory(array_combine($table->getPrimaryColumns(), $row->getPrimaryValues())), $table);

        $this->query($sql);
    }

    /**
     * updateOne 從 db 上更新一個 $row 的 data
     * 
     * @param Pix_Table_Row $row 
     * @param array|string $data
     * @access public
     * @return void
     */
    public function updateOne($row, $data)
    {
        $table = $row->getTable();
        $sql = 'UPDATE ' . $this->column_quote($table->getTableName());
        $sql .= ' SET ' . $this->_get_set_clause($data, $table);
        $sql .= ' WHERE ';
        $sql .= $this->_get_where_clause(Pix_Table_Search::factory(array_combine($table->getPrimaryColumns(), $row->getPrimaryValues())), $table);

	return $this->query($sql);
    }

    /**
     * bulk insert
     *
     * @param Pix_Table $table
     * @param array $keys
     * @param array $values_list
     * @param array $options
     * @access public
     * @return void
     */
    public function bulkInsert($table, $keys, $values_list, $options = array())
    {
        if (array_key_exists('replace', $options) and $options['replace']) {
            $sql = 'REPLACE INTO ';
        } else if (array_key_exists('ignore', $options) and $options['ignore']) {
            $sql = 'INSERT IGNORE INTO ';
        } else {
            $sql = 'INSERT INTO ';
        }
        $sql .= $this->column_quote($table->getTableName());
        $sql .= ' (' . implode(',', array_map(array($this, 'column_quote'), $keys)) . ')';
        $sql .= ' VALUES ';
        $sql .= implode(',', array_map(function($values) use ($table, $keys){
            return '(' . implode(',', array_map(function($value, $key) use ($table){
                return $this->quoteWithColumn($table, $value, $key);
            }, $values, $keys)) . ')';
        }, $values_list));

        $this->query($sql);
    }

    /**
     * insertOne 從 db 上增加一筆資料
     * 
     * @param Pix_Table $table 
     * @param array|string $keys_values 
     * @access public
     * @return void
     */
    public function insertOne($table, $keys_values)
    {
        if (!is_array($keys_values)) {
            $keys_values = array();
        }
        $sql = 'INSERT INTO '. $this->column_quote($table->getTableName());
        $keys = $values = array();
        foreach ($keys_values as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $keys[] = $this->column_quote($key);
            $values[] = $this->quoteWithColumn($table, $value, $key);
        }
        if ($keys) {
            $sql .= '(' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
        } else {
            $sql .= ' DEFAULT VALUES ';
        }

        $this->query($sql);

        return $this->getLastInsertId($table);
    }

    /**
     * column_quote 把 $a 字串加上 quote
     * 
     * @param string $a 
     * @access public
     * @return string
     */
    public function column_quote($a)
    {
        return "`" . addslashes($a) . "`";
    }

    public function getSQLConditionByTerm(Pix_Table_Search_Term $term, $table)
    {
        throw new Pix_Table_Exception('Unsupport Pix_Table_Search_Term: ' . $term->getType());
    }

    /**
     * _get_where_clause 依照 $search 條件以及指定的 $table 回傳 WHERE 的 SQL 
     * 
     * @param Pix_Table_Search $search 
     * @param Pix_Table $table 
     * @access protected
     * @return string
     */
    protected function _get_where_clause($search, $table)
    {
        $terms = array();
        foreach ($search->getSearchCondictions() as $condiction) {
            switch ($condiction[0]) {
            case 'map':
                $terms[] = "(" . $this->column_quote($condiction[1]) . ' = ' . $this->quoteWithColumn($table, $condiction[2], $condiction[1]) . ")";
                break;
            case 'string':
                $terms[] = "(" . $condiction[1] . ")";
                break;
            case 'term':
                $terms[] = $this->getSQLConditionByTerm($condiction[1], $table);
                break;
            default:
                throw new Pix_Table_Exception('不知名的狀態');
            }
        }

        if ($search->after() or $search->before()) {
            $orders = $search->order() ?: array_fill_keys($table->getPrimaryColumns(), 'asc');
            if (!is_array($orders)) {
                throw new Pix_Table_Exception("指定的 ORDER 無法使用 after 或是 before");
            }
            if ($row = $search->after()) {
                $is_include = $search->afterInclude();
                // 如果指定 before 的話，順序要調過來
            } else {
                $row = $search->before();
                $is_include = $search->beforeInclude();
                $orders = Pix_Table_Search::reverseOrder($orders);
            }

	    $equal_orders = array();
            $or_terms = array();

	    foreach ($orders as $order => $way) {
		$and_terms = array();
		foreach ($equal_orders as $equal_order) {
                    $and_terms[] = $this->column_quote($equal_order) . " = " . $this->quoteWithColumn($table, $row->{$equal_order}, $equal_order);
		}
                $and_terms[] = $this->column_quote($order) . ('asc' == $way ? '>' : '<') . " " . $this->quoteWithColumn($table, $row->{$order}, $order);
		$or_terms[] = '(' . implode(' AND ', $and_terms) . ')';
		$equal_orders[] = $order;
            }

            if ($is_include) {
                $and_terms = array();
                foreach ($equal_orders as $equal_order) {
                    $and_terms[] = $this->column_quote($equal_order) . ' = ' . $this->quoteWithColumn($table, $row->{$equal_order}, $equal_order);
                }
                $or_terms[] = '(' . implode(' AND ', $and_terms) . ')';
            }
            $terms[] = '(' . implode(' OR ', $or_terms) . ')';
	}

        if (!$terms) {
            return '1 = 1';
        }

        return implode(' AND ', $terms);
    }

    /**
     * _get_clause 從 $search 條件中，回傳 ORDER BY ... LIMIT ...
     * 
     * @param Pix_Table_Search $search 
     * @access protected
     * @return string
     */
    protected function _get_clause($search)
    {
	$sql = '';
        if ($order = $search->order()) {
            if (is_array($order)) {
                // 如果指定 before 的話，順序要調過來
                if ($search->before()) {
                    $order = Pix_Table_Search::reverseOrder($order);
                }
                $order_term = array();
                foreach ($order as $column => $way) {
                    $order_term[] = $this->column_quote($column) . ' ' . $way;
                }
                $sql .= ' ORDER BY ' . implode(', ', $order_term);
            } else {
                $sql .= ' ORDER BY ' . $order;
            }
	}

        $limit = $search->limit();
        if (!is_null($limit)) {
            $offset = $search->offset();
            if (!is_null($offset)) {
		$sql .= ' LIMIT ' . $offset . ', ' . $limit;
	    } else {
		$sql .= ' LIMIT ' . $limit;
	    }
	}

	return $sql;
    }

    /**
     * _get_set_clause 從 $keys_values 條件中，回傳 SET 後面的 SQL
     * 
     * @param mixed $keys_values 
     * @access protected
     * @return string
     */
    protected function _get_set_clause($keys_values, $table)
    {
	$sql = '';

	if (is_scalar($keys_values)) {
	    return trim($keys_values);
	}

	$terms = array();
	foreach ($keys_values as $column => $value) {
            $terms[] = $this->column_quote($column) . " = " . $this->quoteWithColumn($table, $value, $column);
	}
	$sql .= implode(', ', $terms);

	return $sql;
    }
}
