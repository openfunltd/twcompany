<?php

/**
 * Pix_Array_Array 
 * 這是傳入一個 array ，會把他生成 Pix_Array class
 * 
 * @uses Pix
 * @uses _Array
 * @package Array
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Array_Array extends Pix_Array
{
    protected $_data = array();
    protected $_offset = 0;
    protected $_order = array();
    protected $_limit = null;
    protected $_cur_data = array();
    protected $_row_count = 0;

    /**
     * __construct 
     * 傳一個 array $data 進來
     * 
     * @param array $data 
     * @access public
     * @return void
     */
    public function __construct(array $data)
    {
	$this->_data = $data;
	$this->_cur_data = $data;
    }

    public function getRand($count = null)
    {
	$rand_data = $this->_data;
	shuffle($rand_data);
	if ($count) {
	    return new Pix_Array_Array(array_slice($rand_data, 0, $count));
	} else {
            return $rand_data[0];
	}
    }

    public function getOffset()
    {
	return $this->_offset;
    }

    public function offset($offset = 0)
    {
	$this->_offset = $offset;
	return $this;
    }

    public function _sort($a, $b)
    {
	$way_num = array('asc' => 1, 'desc' => -1);
        foreach ($this->_order as $column => $way) {
            if (is_array($a)) {
                if (!array_key_exists($column, $a) or !array_key_exists($column, $b)) {
                    return 0;
                }
                if (strtolower($a[$column]) > strtolower($b[$column])) {
		    return $way_num[$way];
		}
		if (strtolower($a[$column]) < strtolower($b[$column])) {
		    return -1 * $way_num[$way];
		}
	    } else {
		if (strtolower($a->{$column}) > strtolower($b->{$column})) {
		    return $way_num[$way];
		}
		if (strtolower($a->{$column}) < strtolower($b->{$column})) {
		    return -1 * $way_num[$way];
		}
	    }
	}
	return 0;
    }

    public function getOrder()
    {
	return $this->_order;
    }

    public function order($order = null)
    {
        $obj = clone $this;
        $obj->_order = Pix_Table_Search::getOrderArray($order);
        return $obj;
    }

    public function getLimit()
    {
	return $this->_limit;
    }

    public function limit($limit = null)
    {
        $obj = clone $this;
        $obj->_limit = $limit;
        return $obj;
    }

    public function sum($column = null)
    {
	if (!$column) {
	    return array_sum($this->_data);
	}
	throw new Pix_Array_Exception('TODO');
    }

    public function max($column = null)
    {
	throw new Pix_Array_Exception('TODO');
    }

    public function min($column = null)
    {
	throw new Pix_Array_Exception('TODO');
    }

    public function first()
    {
	return $this->rewind()->current();
    }

    public function toArray($column = null)
    {
        $ret = array();
        foreach ($this as $key => $row) {
            if (is_null($column)) {
                $ret[$key] = $row;
            } else {
                if (is_array($row) and array_key_exists($column, $row)) {
                    $ret[$key] = $row[$column];
                } elseif (is_object($row)) {
                    $ret[$key] = $row->{$column};
                }
            }
        }
        return $ret;
    }

    public function getPosition($obj)
    {
	throw new Pix_Array_Exception('TODO');
    }

    public function count()
    {
        $this->rewind();

        while ($this->valid()) {
            $this->next();
        }

        return $this->_row_count;
    }

    public function seek($pos)
    {
	return $this->_data[$pos];
    }

    public function current()
    {
	return current($this->_cur_data);
    }

    public function next()
    {
        do {
            next($this->_cur_data);
        } while ($this->valid() and !$this->filterRow());

        $this->_row_count ++;

        return $this;
    }

    public function key()
    {
	return key($this->_cur_data);
    }

    public function valid()
    {
        $valid = array_key_exists(key($this->_cur_data), $this->_cur_data);

        if (is_numeric($this->_limit)) {
            $valid = ($valid and ($this->_row_count < $this->_limit));
        }

        return $valid;
    }

    public function rewind()
    {
	$this->_cur_data = $this->_data;
	if ($this->_order) {
	    uasort($this->_cur_data, array($this, '_sort'));
        }

        $this->_row_count = 0;

        $offset = 0;
        while ($this->valid() and $offset < $this->_offset) {
            if ($this->filterRow()) {
                $offset ++;
            }
            next($this->_cur_data);
        }

        while ($this->valid() and !$this->filterRow()) {
            next($this->_cur_data);
        }

	return $this;
    }

    public function offsetExists($pos)
    {
        return array_key_exists($pos, $this->_data);
    }

    public function offsetGet($pos)
    {
	return $this->_data[$pos];
    }

    public function __get($name)
    {
	return $this->_data[$name];
    }

    public function offsetSet($pos, $value)
    {
	if (is_null($pos)) {
	    $this->_data[] = $value;
	} else {
	    $this->_data[$pos] = $value;
	}
    }

    public function offsetUnset($pos)
    {
	unset($this->_data[$pos]);
    }

    public function push($value)
    {
	return array_push($this->_data, $value);
    }

    public function pop()
    {
	return array_pop($this->_data);
    }

    public function shift()
    {
	return array_shift($this->_data);
    }

    public function unshift($value)
    {
	return array_unshift($this->_data, $value);
    }

    public function reverse($preserve_keys = false)
    {
	return array_reverse($this->_data, $preserve_keys);
    }
}
