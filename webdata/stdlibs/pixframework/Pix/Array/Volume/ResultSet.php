<?php

/**
 * Pix_Array_Volume_ResultSet
 * 
 * @uses Iterator
 * @package Array
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Array_Volume_ResultSet implements Iterator, countable
{
    protected $_array = null;
    protected $_chunk = 100;
    protected $_last_row = null;
    protected $_limit = null;
    protected $_offset = 0;

    /**
     * __construct 設定一次要取多少筆
     * 
     * @param Pix_Array $array
     * @param int $chunk 
     * @access public
     */
    public function __construct($array, $options = array())
    {
        $this->_origin_array = $array;
	$this->_chunk = isset($options['chunk']) ? intval($options['chunk']) : 100;
        $this->_volume_id = isset($options['id']) ? $options['id'] : $array->getVolumeID();
        $this->_simple_mode = isset($options['simple_mode']) ? intval($options['simple_mode']) : false;
    }

    public function rewind()
    {
        $this->_last_row = $this->_after;
        $this->_pos = 0;
	$this->_array = Pix_Array::factory(
	    $this->_origin_array
	    ->after($this->_origin_array->getVolumePos($this->_last_row))
	    ->limit($this->_chunk)
	    ->rewind()
	);
	return $this;
    }

    public function first()
    {
        return $this->rewind()->current();
    }

    public function current()
    {
        $this->_last_row = $this->_array->current();

        if ($this->_simple_mode) {
            return $this->_last_row;
        }
	return new Pix_Array_Volume_Row($this->_last_row, $this);
    }

    public function key()
    {
        return $this->_array->key();
    }

    public function next()
    {
        $this->_array->next();
        if (0 !== $this->_limit and !$this->valid()) {
            $this->_array = Pix_Array::factory($this->_origin_array->after($this->_origin_array->getVolumePos($this->_last_row))->limit($this->_chunk)->rewind());
	}
    }

    public function valid()
    {
        if (!is_null($this->_limit) and ($this->_pos >= $this->_offset + $this->_limit)) {
	    return false; 
	}
        return $this->_array->valid();
    }

    public function offset($offset)
    {
        $rs = clone $this;
        $rs->_offset = $offset;
        return $rs;
    }

    public function after($row)
    {
        $rs = clone $this;
        $rs->_after = $row;
        return $rs;
    }

    public function limit($limit)
    {
        $rs = clone $this;
        $rs->_limit = $limit;
        return $rs;
    }

    public function rowOk($row)
    {
        if ($this->_pos < $this->_offset) {
            $this->_pos ++;
	    return false;
        }
	if (is_null($this->_limit)) {
	    $this->_pos ++;
	    return true;
        }
        if ($this->_limit + $this->_offset > $this->_pos) {
	    $this->_pos ++;
	    return true;
	}
	return false;
    }

    /**
     * getPos 取得一個 string ，之後可以當作 after 來用。 
     * 
     * @access public
     * @return void
     */
    public function getPos($row)
    {
	return $this->_array->getVolumePos($row->getRow());
    }

    public function getOrder($row)
    {
	return $this->_pos;
    }

    public function count()
    {
        if ($this->_after) {
            return count($this->_origin_array->after($this->_after));
        }
        return count($this->_origin_array);
    }
}
