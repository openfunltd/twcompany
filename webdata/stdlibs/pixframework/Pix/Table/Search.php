<?php

/**
 * Pix_Table_Search Pix_Table Serach 用的 model
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Search
{
    protected $_after = null;
    protected $_after_include = false;
    protected $_before = null;
    protected $_before_include = false;
    protected $_order = array();
    protected $_limit = null;
    protected $_index = null;
    protected $_offset = null;
    protected $_search_conditions = array();
    protected $_search_condition_types = array('string' => 0, 'map' => 0);

    static public function factory($data = null)
    {
        if ($data instanceof Pix_Table_Search) {
            return $data;
        }

        $search = new Pix_Table_Search();
        if (!is_null($data)) {
            $search = $search->search($data);
        }
        return $search;
    }

    public function search($search)
    {
        if (is_scalar($search)) {
            if (1 == $search) {
                return $this;
            }

            // TODO: 看看能不能轉成其他形式, Ex: "a" = 1 ...
            $this->_search_conditions[] = array('string', $search);
            $this->_search_condition_types['string'] ++;
            return $this;
        }

        if (is_array($search)) {
            foreach ($search as $key => $value) {
                $this->_search_conditions[] = array('map', $key, $value);
                $this->_search_condition_types['map'] ++;
            }
            return $this;
        }

	throw new Pix_Table_Search_Exception('Unknown search type');
    }

    public function getSearchCondictions($type = null)
    {
        if (is_null($type)) {
            return $this->_search_conditions;
        }

        $ret = array();
        foreach ($this->_search_conditions as $condiction) {
            if ($condiction[0] != $type) {
                continue;
            }
            $ret[] = $condiction;
        }
        return $ret;
    }

    public function isMapOnly()
    {
        return $this->_search_condition_types['map'] and (!$this->_search_condition_types['string']);
    }

    public function order()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_order;
        }
	$this->_order = self::getOrderArray($args[0]);
	return $this;
    }

    public function limit()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_limit;
        }
	$this->_limit = $args[0];
	return $this;
    }

    public function index()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_index;
        }
	$this->_index = $args[0];
	return $this;
    }

    public function offset()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_offset;
        }
	$this->_offset = $args[0];
	return $this;
    }

    public function after()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_after;
        }
        $row = $args[0];

	$this->_before = null;
        $this->_after = is_array($row) ? Pix_Array::factory($row) : $row;
        $this->_after_include = array_key_exists(1, $args) ? $args[1] : false;
	return $this;
    }

    public function afterInclude()
    {
        return $this->_after_include;
    }

    public function before()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_before;
        }
        $row = $args[0];

	$this->_after = null;
	$this->_before = is_array($row) ? Pix_Array::factory($row) : $row;
        $this->_before_include = array_key_exists(1, $args) ? $args[1] : false;
	return $this;
    }

    public function beforeInclude()
    {
        return $this->_before_include;
    }

    public static function reverseOrder($order)
    {
	foreach ($order as $column => $way) {
	    $order[$column] = ('asc' == $order[$column]) ? 'desc' : 'asc';
	}
	return $order;
    }

    public static function getOrderArray($order)
    {
	$resultorder = array();
	if (is_array($order)) {
	    foreach ($order as $column => $way) {
		if (is_int($column)) {
		    $resultorder[$way] = 'asc';
		    continue;
		}

		$resultorder[$column] = strtolower($way);
		if (!in_array(strtolower($way), array('asc', 'desc'))) {
		    $resultorder[$column] = 'asc';
		    continue;
		}
	    }
	}

        if (is_scalar($order)) {
            if ('RAND()' == $order) {
                return 'RAND()';
            }
	    $orders = explode(',', $order);
	    $resultorder = array();
	    foreach ($orders as $ord) {
                if (preg_match('#^`?([^` ]*)`?( .*)?$#', trim($ord), $matches)) {
                    if (array_key_exists(2, $matches) and in_array(strtolower(trim($matches[2])), array('asc', 'desc'))) {
                        $way = strtolower(trim($matches[2]));
                    } else {
                        $way = 'asc';
                    }
                    $resultorder[$matches[1]] = $way;
                } else {
                    throw new Pix_Array_Exception('->order($order) 的格式無法判斷');
                }
	    }
	}
	return $resultorder;
    }
}
