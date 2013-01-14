<?php

/**
 * Pix_Table_ResultSet 
 * 用來存 Pix_Table 的 ResultSet
 * XXX: 無法 implements 是因為 Pix_Array 和 Pix_Array_Volumable 都有 limit, rewind
 *      PHP 不給同時 abstract 一個 function 兩次
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_ResultSet extends Pix_Array // implements Pix_Array_Volumable
{
    protected $_pointer;

    protected $_tableClass;
    protected $_rowset = array();
    protected $_search_object = null;

    protected $_belongs_row;

    protected $_relation_data = array();

    public function __construct($conf)
    {
        if (!isset($conf['tableClass'])) {
	    throw new Exception('new ResultSet 時必需要指定 tableClass');
	}
        $this->_tableClass = $conf['tableClass'];
        $this->_table = Pix_Table::getTable($this->_tableClass);

	if (isset($conf['belongs_row'])) {
	    $this->_belongs_row = $conf['belongs_row'];
	}

        $this->_search_object = Pix_Table_Search::factory();
    }

    public function __clone()
    {
        $this->_search_object = clone $this->_search_object;
    }

    /**
     * getRand 
     * 從 $resultSet 中取得隨機 $count 個 $row
     * 
     * @param mixed $count 
     * @access public
     * @return Pix_Array
     */
    public function getRand($count = null)
    {
	if (count($this->getTable()->getPrimaryColumns()) != 1) {
	    if ($count) {
		return $this->order('RAND()')->limit($count);
	    } else {
		return $this->order('RAND()')->first();
	    }
	}

	$primary = $this->getTable()->getPrimaryColumns();
	$primary = $primary[0];


	// 如果要取的數量超過 ResultSet Row 的數量的一半，就直接 ORDER BY RAND 了...
	$rs_count = count($this);
	if ($count >= intval($rs_count / 2)) {
	    if ($count) {
		return $this->order('RAND()')->limit($count);
	    } else {
		return $this->order('RAND()')->first();
	    }
	}

	$min = $this->min($primary)->{$primary};
	$max = $this->max($primary)->{$primary};

	if ($count) {
	    $used = array();
	    $res = array();

            for ($i = 0; $i < $count; ) {
                if (!$row = $this->search("$primary >= " . rand($min, $max))->order(array($primary => 'asc'))->first()) {
		    continue;
		}
                if ($used[$row->{$primary}]) {
		    continue;
                }
		$res[] = $row;
		$used[$row->{$primary}] = true;

		$i ++;
	    }
	    return Pix_Array::factory($res);
	} else {
	    while (1) {
		if (!$row = $this->search("$primary >= " . rand($min, $max))->first()) {
		    continue;
		}
		return $row;
	    }
	}
    }

    public function filterQuery()
    {

        $args = func_get_args();
	$filters = $this->getTable()->getFilters();
	$ret = $this;
	foreach ($args as $filter) {
	    if (!$func = $filters[$filter]) {
		throw new Exception("找不到 {$filter} 這個 filter");
	    }
	    if (is_array($func)) {
		if ($func['search']) {
		    $ret = $ret->search($func['search']);
		}
		if ($func['order']) {
		    $ret = $ret->search($func['order']);
		}
		if ($func['limit']) {
		    $ret = $ret->search($func['limit']);
		}
	    } elseif (is_scalar($func)) {
		if (!method_exists($ret, $func)) {
		    throw new Exception("找不到 {$func} 這個 function");
		}
		$ret = $ret->{$func}();
		if (!($ret instanceof Pix_Table_ResultSet)) {
		    throw new Exception("filter 回傳格式必須要是 Pix_Table_ResultSet");
		}
	    } else {
		throw new Exception('錯誤');
	    }
	}
	return $ret;
    }

    public function find($id)
    {
	$primary_columns = $this->getTable()->getPrimaryColumns();
	if (!is_array($id)) {
	    $id = array($id);
	}
	if (count($primary_columns) != count($id)) {
	    throw new Exception("使用 find 時 primary key 數量不正確");
	}
	return $this->search(array_combine($primary_columns, $id))->first();
    }

    /**
     * searchIn 搜尋 $column 的值是在 $values 裡面
     * 
     * @param mixed $column 
     * @param mixed $values 
     * @access public
     * @return void
     */
    public function searchIn($column, $values)
    {
	if (!is_array($values) or !$values) {
	    return $this->search(0);
	}

	$terms = array();
        $db = $this->getResultSetDb();
	foreach ($values as $v) { 
            $terms[] = $db->quoteWithColumn($this->getTable(), $v, $column);
        }
        return $this->search($db->column_quote($column) . " IN (" . implode(', ', $terms) . ")");
    }

    //public function pager($page, $pager); // 這函式寫在 Pix_Array::pager
    public function offset()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_search_object->offset();
        }
        $rs = clone $this;
        $rs->_search_object->offset($args[0]);
	return $rs;
    }

    public function index()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_search_object->index();
        }
        $rs = clone $this;
        $rs->_search_object->index($args[0]);
	return $rs;
    }

    /**
     * getOrderArray 把 order 字串轉成 array('column' => 'asc|desc') 格式
     * 
     * @param mixed $order 
     * @static
     * @access public
     * @return void
     */
    public static function getOrderArray($order, $reverse = false)
    {
	$resultorder = array();
	if (is_array($order)) {
	    foreach ($order as $column => $way) {
		if (is_int($column)) {
		    $resultorder[$way] = $reverse ? 'desc' : 'asc';
		    continue;
		}

		$resultorder[$column] = strtolower($way);
		if (!in_array(strtolower($way), array('asc', 'desc'))) {
		    $resultorder[$column] = $reverse ? 'desc' : 'asc';
		    continue;
		}
		if ($reverse) {
		    $way = 'asc' == strtolower($way) ? 'desc' : 'asc';
		}
	    }
	}

	if (is_scalar($order)) {
	    $orders = explode(',', $order);
	    $resultorder = array();
	    foreach ($orders as $ord) {
		if (preg_match('#^`?([^` ]*)`?( .*)?$#', trim($ord), $ret)) {
		    $way = strtolower(trim($ret[2]));
		    if (!in_array($way, array('asc', 'desc'))) {
			$resultorder[$ret[1]] = $reverse ? 'desc' : 'asc';
		    } else {
			if ($reverse) {
			    $way = 'asc' == strtolower($way) ? 'desc' : 'asc';
			}
			$resultorder[$ret[1]] = $way;
		    }
		} else {
		    throw new Pix_Array_Exception('->order($order) 的格式無法判斷');
		}
	    }
	}
	return $resultorder;

    }

    public function order()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_search_object->order();
        }
        $rs = clone $this;
        $rs->_search_object->order($args[0]);
	return $rs;
    }

    public function limit()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_search_object->limit();
        }

        $rs = clone $this;
        $rs->_search_object->limit($args[0]);
	return $rs;
    }

    public function getTableClass()
    {
	return $this->_tableClass;
    }

    protected $_table = null;

    /**
     * getTable get the Pix_Table of this result set
     *
     * @access public
     * @return Pix_Table
     */
    public function getTable()
    {
        return $this->_table;
    }

    public function update($data)
    {
	foreach ($this as $row) {
	    $row->update($data);
	}
	return true;
    }

    public function delete($where = NULL)
    {
	$count = 0;
	if ($where) {
	    $rowset = $this->search($where);
	} else {
	    $rowset = $this;
	}
	foreach ($rowset as $row) {
	    $count += $row->delete();
	}
	return $count;
    }

    public function count()
    {
        if ($this->getFilters()) {
            throw new Pix_Table_Exception("有指定 Filter 的情況下不能使用 count");
        }
        return $this->getResultSetDb()->fetchCount($this->getTable(), $this->_search_object);
    }

    public function getResultSetDb()
    {
        return $this->getTable()->getDb();
    }

    public function sum($column = null)
    {
	if (!$column) {
	    throw new Exception('一定要指定 $column');
        }

        if ($this->getFilters()) {
            throw new Pix_Table_Exception("有指定 Filter 的情況下不能使用 sum");
        }
        $db = $this->getResultSetDb();
        return $db->fetchSum($this->getTable(), $column, $this->_search_object);
    }

    public function rewind($select_columns = '*')
    {
        if ($this->getFilters() and ($this->_search_object->offset() or $this->_search_object->limit())) {
            throw new Pix_Table_Exception("不支援同時使用 offset 與 limit");
        }
        // 如果 search 條件包含 PK 的話，就直接用 find 的，這樣可以用到 find 的 cache 機制
        if ('*' == $select_columns and $this->_search_object->isMapOnly()) {
            $where = array();
            foreach ($this->_search_object->getSearchCondictions() as $condiction) {
                $where[$condiction[1]] = $condiction[2];
            }
        } else {
            $where = null;
        }

        // 如果沒有指定 Filter 或者是 PRIMARY KEY
        if (!$this->getFilters() and $where and 'PRIMARY' == $this->getTable()->findUniqueKey(array_keys($where))) {
	    $val = array();
	    foreach ($this->getTable()->getPrimaryColumns() as $col) {
		$val[] = $where[$col];
	    }

	    $this->_rowset = array();
	    $this->_pointer = 0;
	    if ($row = $this->getTable()->find($val, $this)) {
		foreach ($where as $key => $value) {
		    if ($row->{$key} != $value) {
			return $this;
		    }
		}

		$this->_rowset[] = $row->toArray();
	    }
	    return $this;
	}

	$this->_rowset = array();
        foreach ($this->getResultSetDb()->fetch($this->getTable(), $this->_search_object, $select_columns) as $row) {
            $this->_rowset[] = $row;
            // 如果 SELECT * 的話，就可以把這個 row 給 cache 起來了
            if ('*' == $select_columns) {
                $this->_cacheRow($row);
            }
	}
        $this->_pointer = 0;
        if ($this->valid() and !$this->filterRow()) {
            $this->next();
        }
	return $this;
    }

    /**
     * _cacheRow 將 rewind 時取到的資料 cache 到 Pix_Table 的 memory cache
     *
     * @param array $data 
     * @access protected
     * @return void
     */
    protected function _cacheRow($data)
    {
        $primary_values = array();
        foreach ($this->getTable()->getPrimaryColumns() as $column) {
            $primary_values[] = $data[$column];
        }
        $this->getTable()->cacheRow($primary_values, $data);
    }

    public function current()
    {
	if (!$this->_rowset) {
	    $this->rewind();
	}

        if (!array_key_exists($this->_pointer, $this->_rowset)) {
            return null;
        }

	$conf = array();
	$conf['tableClass'] = $this->_tableClass;
	$conf['data'] = $this->_rowset[$this->_pointer];
	$conf['belongs_row'] = $this->_belongs_row;

	$rowClass = $this->getTable()->_rowClass;
	$row = new $rowClass($conf);
	return $row;
    }

    public function key()
    {
	return $this->_pointer;
    }

    public function next()
    {
        do {
            ++$this->_pointer;
        } while ($this->valid() and !$this->filterRow());
    }

    public function valid()
    {
	return $this->_pointer < count($this->_rowset);
    }

    public function search($where)
    {
	$rs = clone $this;
        $rs->_rowset = null;


        // convert {relation_name} => {row} to {key} => {value}
        if (is_array($where)) {
            $table = $this->getTable();
            $new_where = array();
            foreach ($where as $key => $value) {
                if ($table->_columns[$key]) {
                    $keys = array($key);
                } else {
                    $keys = $table->getRelationForeignKeys($key);
                }

                if (is_object($value) and is_a($value, 'Pix_Table_Row')) {
                    $values = $value->getPrimaryValues();
                } elseif (is_array($value)) {
                    $values = $value;
                } else {
                    $values = array($value);
                }

                foreach (array_combine($keys, $values) as $key => $value) {
                    $new_where[$key] = $value;
                }
            }
            $where = $new_where;
        }
        $rs->_search_object->search($where);
	return $rs;
    }

    /**
     * ->after($row, [$is_included]) to set after condiction or ->after() to get after condiction
     *
     * @param Pix_Table_Row|array|null $row
     * @param boolean $is_included
     * @access public
     * @return mixed return if no parameters
     */
    public function after()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_search_object->after();
        }
	$rs = clone $this;
	$rs->_rowset = null;
        $rs->_search_object->after($args[0], array_key_exists(1, $args) ? $args[1] : false);
	return $rs;
    }

    /**
     * ->before($row, [$is_included]) to set before condiction or ->before() to get before condiction
     *
     * @param Pix_Table_Row|array|null $row
     * @param boolean $is_included
     * @access public
     * @return mixed return if no parameters
     */
    public function before()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_search_object->before();
        }
	$rs = clone $this;
	$rs->_rowset = null;
        $rs->_search_object->before($args[0], array_key_exists(1, $args) ? $args[1] : false);
	return $rs;
    }

    public function seek($position)
    {
        throw new Exception("Pix_Table 不提供 seek 的支援");
    }

    public function __get($name)
    {
        throw new Exception("Pix_Table_ResultSet({$this->getTable()->getClass()})->{$name} 沒有這個 {$name}");
    }

    public function offsetExists($row)
    {
	return $this->offsetGet($row);
    }

    public function offsetGet($row)
    {
	if ($row == null) {
	    return null;
	}

	$type_table = $this->getTable();
	if (is_scalar($row)) {
	    $row = array($row);
	}
	if (!is_array($row)) {
	    throw new Pix_Exception("不能夠使用 [Object] ，只能使用 [string] 或是 [int]");
	}
	$where = array_combine($type_table->getPrimaryColumns(), $row);

	return $this->search($where)->first();
    }

    public function offsetSet($pos, $row)
    {
	throw new Exception('TODO');
    }

    public function offsetUnset($pos)
    {
        throw new Exception('TODO');
    }

    public function max($column = null)
    {
	if (!$column) {
	    throw new Exception('一定要指定 $column');
	}
	$rs = clone $this;
        return $rs->order(array($column => 'desc'))->first();
    }

    public function min($column = null)
    {
	if (!$column) {
	    throw new Exception('一定要指定 $column');
	}
	$rs = clone $this;
        return $rs->order(array($column => 'asc'))->first();
    }

    public function first()
    {
        return $this->limit(1)->rewind()->current();
    }

    public function createRow()
    {
        $row = $this->getTable()->createRow($this->_belongs_row);

        foreach ($this->_search_object->getSearchCondictions('map') as $condiction) {
            list(/*map*/, $k, $v) = $condiction;
            $row->{$k} = $v;
        }
	return $row;
    }

    public function insert($data)
    {
	if (!is_array($data)) {
	    throw new Exception('insert 的參數一定要是 array');
	}
	$table = $this->getTable();
        $row = $this->createRow($this->_belongs_row);
	$array = array_intersect_key($data, $table->_columns);
	foreach ($array as $column => $value) {
	    $row->{$column} = $value;
	}
	$array = array_intersect_key($data, $table->_relations);
	foreach ($array as $column => $value) {
	    if ($value instanceof Pix_Table_Row) {
		$row->{$column} = $value;
	    }
	}
	$row->save();
	return $row;
    }

    public function distinct($columns)
    {
        if (!is_array($columns)) {
            return $this;
        }

        $data_array = $this->toArray($columns);
        $ret = array();

        foreach ($data_array as $values) {
            $keys = array();
            foreach ($columns as $col) {
                $keys[] = urlencode($values[$col]);
            }
            $ret[implode('&', $keys)] = $values;
        }

        return array_values($ret);
    }

    public function shuffle()
    {
	$ret = $this->toRowArray();
	shuffle($ret);
	return $ret;
    }

    public function toRowArray()
    {
	$ret = array();
	foreach ($this as $row) {
	    $ret[] = $row;
	}
	return $ret;
    }

    public function toArray($column = null)
    {
	if (is_array($column)) {
            $this->rewind(array_unique(array_merge($column, $this->getTable()->getPrimaryColumns())));
        } elseif (is_scalar($column)) {
            $this->rewind(array_unique(array_merge(array($column), $this->getTable()->getPrimaryColumns())));
	} else {
	    $this->rewind();
	}
	$array = array();
	$primary = $this->getTable()->getPrimaryColumns();

	if (count($primary) > 1 or !isset($this->_rowset[0][$primary[0]])) {
	    $primary = null;
	}
	$primary = $primary[0];

        if ($column == null) {
	    foreach ($this->_rowset as $row) {
		if ($primary) {
		    $array[$row[$primary]] = $row;
		} else {
		    $array[] = $row;
		}
	    }
	} elseif (is_scalar($column)) {
	    foreach ($this->_rowset as $row) {
		if ($primary) {
		    $array[$row[$primary]] = $row[$column];
		} else {
		    $array[] = $row[$column];
		}
	    }
	} elseif (is_array($column)) {
	    foreach ($this->_rowset as $row) {
		$t = array();
		foreach ($column as $col) {
		    $t[$col] = $row[$col];
		}
		if ($primary) {
		    $array[$row[$primary]] = $t;
		} else {
		    $array[] = $t;
		}
	    }
	}
	unset($this->_rowset);
	return $array;
    }

    public function getPosition($row)
    {
	$resultSet = clone $this;
	$resultSet = $resultSet->limit(null)->offset(null);
	$array = $resultSet->toArray(array());
	$i = 0;
	if ($row instanceof Pix_Table_Row) {
	    $row_pk = $row->getPrimaryValues();
	} elseif (is_scalar($row)) {
	    $row_pk = array($row);
	} elseif (is_array($row)) {
	    $row_pk = $row;
	} else {
	    return false;
	}

	foreach ($array as $pk => $unused) {
	    if (is_scalar($pk)){
		$pk = array($pk);
	    }
	    if ($pk == $row_pk) {
		return $i;
	    }
	    $i ++;
	}
	return false;
    }

    public function __call($func, $args)
    {
        if ($this->getTable()->getHelperManager('resultset')->hasMethod($func)) {
            array_unshift($args, $this);
            return $this->getTable()->getHelperManager('resultset')->callHelper($func, $args);
        } elseif (Pix_Table::getStaticHelperManager('resultset')->hasMethod($func)) {
            array_unshift($args, $this);
            return Pix_Table::getStaticHelperManager('resultset')->callHelper($func, $args);
	}

	throw new Pix_Table_Exception("找不到 function {$func}");
    }

    /**
     * getVolumeID 給 Pix_Array_Volumable 用的 ID，這邊因為用不到所以直接噴 NULL
     *
     * @access public
     * @return null
     */
    public function getVolumeID()
    {
	return null;
    }

    /**
     * getVolumePos 回傳這個 $row 在 Volumable 的位置
     *
     * @param Pix_Table_Row $row
     * @access public
     * @return array
     */
    public function getVolumePos($row)
    {
        if (!$orders = $this->_search_object->order()) {
	    foreach ($this->getTable()->getPrimaryColumns() as $col) {
		$orders[$col] = 'asc';
	    }
	}

	$terms = array();
        if (is_array($row)) {
	    $row = Pix_Array::factory($row);
        } elseif (is_object($row) and is_a($row, 'Pix_Table_Row')) {
            $row = $row;
        } else {
            return null;
        }
        foreach ($orders as $order => $way) {
            if (!is_null($row->{$order})) {
                $terms[$order] = $row->{$order};
            }
	}
	return $terms;
    }
}
