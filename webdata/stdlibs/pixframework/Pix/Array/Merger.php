<?php

/**
 * Pix_Array_Merger merge many pix_array
 * 
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 * @author Shang-Rung Wang <srwang@pixnet.tw>
 */
class Pix_Array_Merger implements Iterator
{
    protected $_arrays = array();
    protected $_array_orders = array();
    protected $_array_datas = array();
    protected $_array_last = array();
    protected $_after = null;
    protected $_after_included = false;
    protected $_chunk_size = 10;

    /**
     * __construct 
     * 
     * @param $array... arrays you want to merge
     * @access public
     */
    public function __construct()
    {
        $this->_arrays = func_get_args();
    }

    /**
     * set the order of merged array
     * 
     * @param $order or $orders for each array
     * @access public
     * @return Pix_Array_Merger
     */
    public function order()
    {
        $orders = func_get_args();
        $rs = clone $this;

        $rs->_array_orders = array();
        foreach ($orders as $order) {
            $rs->_array_orders[] = Pix_Table_Search::getOrderArray($order);
        }

        return $rs;
    }

    public function chunkSize()
    {
        $args = func_get_args();

        if (0 == count($args)) {
            return $this->_chunk_size;
        }

        $rs = clone $this;
        $rs->_chunk_size = intval($args[0]);
        return $rs;
    }

    protected function _fetchArray($pos)
    {
        if (array_key_exists($pos, $this->_array_last) and $this->_array_last[$pos] === false) {
            return ;
        }

        $array = $this->_arrays[$pos]->order(array_combine($this->_getSortColumns($pos), $this->_getSortOrders()));
        if (array_key_exists($pos, $this->_array_last)) {
            $array = $array->after($this->_array_last[$pos]);
        } elseif (is_scalar($this->_after)) {
            $sort_values = explode('&', $this->_after);
            $after_position = array_pop($sort_values);
            $sort_values = array_map('urldecode', $sort_values);
            $after_data = array_combine($this->_getSortColumns($pos), $sort_values);

            $array = $array->after($after_data, $pos < $after_position);
        } elseif (!is_null($this->_after)) {
            $array = $array->after($this->_after, $this->_after_included);
        }

        foreach ($array->limit($this->_chunk_size) as $row) {
            $this->_array_datas[$pos][] = $row;
        }

        if (!array_key_exists($pos, $this->_array_datas) or !count($this->_array_datas[$pos])) {
            $this->_array_last[$pos] = false;
            return false;
        }

        return true;
    }

    protected function _getSortOrders()
    {
        return array_values($this->_array_orders[0]);
    }

    protected function _getSortColumns($pos)
    {
        if (array_key_exists($pos, $this->_array_orders)) {
            return array_keys($this->_array_orders[$pos]);
        }

        if (array_key_exists(0, $this->_array_orders)) {
            return array_keys($this->_array_orders[0]);
        }

        throw new Pix_Exception('unknown order');
    }

    public function after($position, $included = false)
    {
        $rs = clone $this;
        $rs->_after = $position;
        $rs->_after_included = $included;
        return $rs;
    }

    public function rewind()
    {
        $this->_array_datas = array();
        $this->_array_last = array();
        $this->next();
    }

    protected $_current = null;
    protected $_current_pos = null;

    public function next()
    {
        $this->_current = null;
        $min_pos = null;
        $min_row = null;

        foreach ($this->_arrays as $pos => $array) {
            // fetch more rows from array if it is empty
            if (!array_key_exists($pos, $this->_array_datas) or !count($this->_array_datas[$pos])) {
                if (!$this->_fetchArray($pos)) {
                    continue;
                }
            }
            $data = $this->_array_datas[$pos];

            if (is_null($min_pos)) {
                $min_pos = $pos;
                $min_row = $data[0];
                $min_columns = $this->_getSortColumns($pos);
                continue;
            }

            // compare current_row to min_row
            $current_row = $data[0];

            $sort_orders = $this->_getSortOrders();

            foreach ($this->_getSortColumns($pos) as $i => $column) {
                if (is_array($current_row)) {
                    $current_value = $current_row[$column];
                } else {
                    $current_value = $current_row->{$column};
                }

                if (is_array($min_row)) {
                    $min_value = $min_row[$min_columns[$i]];
                } else {
                    $min_value = $min_row->{$min_columns[$i]};
                }
                if ('asc' == $sort_orders[$i]) {
                    if ($current_value < $min_value) {
                        break;
                    } elseif ($current_value > $min_value) {
                        continue 2;
                    }
                } else {
                    if ($current_value > $min_value) {
                        break;
                    } elseif ($current_value < $min_value) {
                        continue 2;
                    }
                }
            }

            $min_pos = $pos;
            $min_row = $current_row;
            $min_columns = $this->_getSortColumns($pos);
        }

        if (is_null($min_pos)) {
            return null;
        }

        $this->_current = array_shift($this->_array_datas[$min_pos]);
        $this->_current_pos = $min_pos;
        $this->_array_last[$min_pos] = $this->_current;
    }

    public function current()
    {
        return $this->_current;
    }

    public function key()
    {
        $sort_values = array();
        foreach ($this->_getSortColumns($this->_current_pos) as $column) {
            $current = $this->_current;
            if (is_array($current)) {
                $sort_values[] = urlencode($current[$column]);
            } else {
                $sort_values[] = urlencode($current->$column);
            }
        }
        $sort_values[] = $this->_current_pos;
        return implode('&', $sort_values);
    }

    public function valid()
    {
        return !is_null($this->_current);
    }
}
