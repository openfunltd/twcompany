<?php

/**
 * Pix_Array
 * 這是做出一個可以 ArrayAccess 並且可以支援 limit(), first(), offset(), order() 等 function 的 Array
 * 以方便讓我們的 Array 可以跟 Pix_Table_ResultSet 通用
 *
 * @uses Countable
 * @uses SeekableIterator
 * @uses ArrayAccess
 * @abstract
 * @package Array
 * @version $id$
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 * @author Shang-Rung Wang <srwang@pixnet.tw>
 */
abstract class Pix_Array implements Countable, SeekableIterator, ArrayAccess
{
    protected $_filters = array();

    /**
     * factory
     * 建立一個 Pix_Array array ，可以傳 Pix_Array Object 進去 or Array
     *
     * @param mixed $obj
     * @static
     * @access public
     * @return void
     */
    public static function factory($obj = null)
    {
	if (is_object($obj) and is_a($obj, 'Pix_Array')) {
	    return $obj;
	}

	if (is_array($obj)) {
	    return new Pix_Array_Array($obj);
	}

	if (is_null($obj)) {
	    return new Pix_Array_Array(array());
	}
	return null;
    }

    /**
     * getRand  回傳這個 Pix_Array 中最多 $num 個的隨機物品
     * $num 為 0 表示只回傳一樣東西
     *
     * @param int $num
     * @abstract
     * @access public
     * @return Pix_Array
     */
    abstract public function getRand($num = null);
    abstract public function offset();
    abstract public function order();
    abstract public function limit();
    abstract public function sum($column = null);
    abstract public function max($column = null);
    abstract public function min($column = null);
    abstract public function first();
    abstract public function toArray($column = null);
    abstract public function getPosition($obj);

    // XXX: 需要檢查所有繼承 Pix_Array 的 class，並 implement 這五個 function
    public function push($value)
    {
	throw new Pix_Array_Exception('尚未實做');
    }

    public function pop()
    {
	throw new Pix_Array_Exception('尚未實做');
    }

    public function shift()
    {
	throw new Pix_Array_Exception('尚未實做');
    }

    public function unshift($value)
    {
	throw new Pix_Array_Exception('尚未實做');
    }

    public function reverse($preserve_keys = false)
    {
	throw new Pix_Array_Exception('尚未實做');
    }

    public function pager($page, $perPage)
    {
	$page = max(1, intval($page));
	return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    public function paginate($page = 1, $options = array())
    {
        $default_per_page = 20;
	$page = max(1, intval($page));
	$default_settings = array(
	    // per_page:每頁幾項, order:SQL排序QUERY
	    'per_page' => $default_per_page,
	    'order' => '`id` DESC',
	);
	$settings = array_merge($default_settings, $options);
	foreach ($settings as $key => $val) {
	    $$key = $val;
	}
        if ($per_page <= 0) $per_page = $default_per_page;
	$this->total_page = ceil(count($this) / $per_page);
	$this->per_page = $per_page;
	$this->now_page = $page;
	return $this->limit($per_page)->offset(($page - 1) * $per_page)->order($order);
    }

    public function filter($filter, $options = array())
    {
        $obj = clone $this;
        $obj->addFilter($filter, $options);
        return $obj;
    }

    public function filterBuiltIn($filter, $options = array())
    {
        $obj = clone $this;
        $filter_name = 'Pix_Array_Filter_' . $filter;
        $filter_obj = new $filter_name;
        $obj->addFilter(array($filter_obj, 'filter'), $options);
        return $obj;
    }

    protected function addFilter($filter, $options)
    {
        $this->_filters[] = array($filter, $options);
    }

    protected $_after;
    protected $_after_included = false;

    public function after()
    {
        $args = func_get_args();
        if (!count($args)) {
            return $this->_after;
        }
        $rs = clone $this;
        $rs->_after = $args[0];
        $rs->_after_included = array_key_exists(1, $args) ? $args[1] : false;
        return $rs;
    }

    protected function filterRow()
    {
        if ($this->_after) {
            $compare = $this->_sort($this->current(), $this->_after);

            if ($compare < 0) {
                return false;
            }

            if ($compare == 0 and !$this->_after_included) {
                return false;
            }
        }

        if (count($this->_filters)) {
            foreach ($this->_filters as $filter) {
                list($callback, $options) = $filter;
                if (is_callable($callback)) {
                    return call_user_func_array($callback, array($this->current(), $options));
                }
            }
        }
        return TRUE;
    }

    protected function getFilters()
    {
        return $this->_filters;
    }
}
