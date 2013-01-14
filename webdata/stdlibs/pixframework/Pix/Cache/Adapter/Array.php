<?php
/**
 * Pix_Cache_Adapter_Array
 *
 * @uses Pix_Cache_Adapter
 * @package Cache
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Cache_Adapter_Array extends Pix_Cache_Adapter
{
    protected $_big_array = array();

    /**
     * @codeCoverageIgnore
     */
    public function __construct($config)
    {
    }

    /**
     * add 新增一筆 cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|array $options
     * @access public
     * @return bool 如果 $key 已經存在，則會回傳 false
     */
    public function add($key, $value, $options = array())
    {
        if (array_key_exists($key, $this->_big_array)) {
            return false;
        }

        self::set($key, $value, $options);
        return true;
    }

    /**
     * set 設定一筆 cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|array $options
     *      int: cache 過期的秒數, array(): 不會過期
     * @access public
     * @return bool
     */
    public function set($key, $value, $options = array())
    {
        if (array() === $options) {
            $this->_big_array[$key] = array('value' => $value);
        } elseif (is_numeric($options)) {
            $start_at = time();
            $end_at = $start_at + (int)$options;
            $this->_big_array[$key] = array(
                'value' => $value,
                'start_at' => $start_at,
                'end_at' => $end_at
            );
        }

        return true;
    }

    /**
     * delete 刪除一筆 cache
     *
     * @param string $key
     * @access public
     * @return bool
     */
    public function delete($key)
    {
        unset($this->_big_array[$key]);
        return true;
    }

    /**
     * replace 取代一筆 cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|array $options
     * @access public
     * @return bool 如果 $key 不存在，就會回傳 false
     */
    public function replace($key, $value, $options = array())
    {
        if (!array_key_exists($key, $this->_big_array)) {
            return false;
        }

        self::set($key, $value, $options);
        return true;
    }

    /**
     * inc 對 cache 的值增加 $inc ，當原本的值不是數字時則視為 set()
     *
     * @param string $key
     * @param int $inc
     * @access public
     * @return bool
     */
    public function inc($key, $inc = 1)
    {
        $old_value = self::get($key);
        if (!is_numeric($old_value)) {
            self::set($key, $inc);
        } else {
            $new_value = $old_value + $inc;
            self::set($key, $new_value);
        }

        return true;
    }

    /**
     * dec 對 cache 的值減少 $dec ，當原本的值不是數字時則視為 set()
     *
     * @param string $key
     * @param int $dec
     * @access public
     * @return bool
     */
    public function dec($key, $dec = 1)
    {
        $old_value = self::get($key);
        if (!is_numeric($old_value)) {
            self::set($key, $dec);
        } else {
            $new_value = $old_value - $dec;
            self::set($key, $new_value);
        }

        return true;
    }

    /**
     * append 在 cache 的值後方加上 $data
     *
     * @param string $key
     * @param string $data
     * @param int|array $options
     * @access public
     * @return bool 當 $key 不存在時，則會回傳 false
     */
    public function append($key, $data, $options = array())
    {
        if (!is_scalar($data)) {
            throw new InvalidArgumentException('append 只能加文字');
        }
        if (!array_key_exists($key, $this->_big_array)) {
            return false;
        }

        $data = strval($data);
        $old_value = self::get($key);
        $new_value = $old_value . $data;
        self::set($key, $new_value, $options);
        return true;
    }

    /**
     * prepend 在 cache 的值前方加上 $data
     *
     * @param string $key
     * @param string $data
     * @param int|array $options
     * @access public
     * @return bool 當 $key 不存在時，則會回傳 false
     */
    public function prepend($key, $data, $options = array())
    {
        if (!is_scalar($data)) {
            throw new InvalidArgumentException('prepend 只能加文字');
        }
        if (!array_key_exists($key, $this->_big_array)) {
            return false;
        }

        $data = strval($data);
        $old_value = self::get($key);
        $new_value = $data . $old_value;
        self::set($key, $new_value, $options);
        return true;
    }

    /**
     * get 取得一筆 cache
     *
     * @param string $key
     * @access public
     * @return string
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->_big_array)) {
            return false;
        }
        $result = $this->_big_array[$key];
        if ($result['end_at'] && (time() > $result['end_at'])) {
            self::delete($key);
        }

        return $this->_big_array[$key]['value'];
    }

    /**
     * gets 取得多筆 cache 的值
     *
     * @param array $keys
     * @access public
     * @return array
     */
    public function gets(array $keys)
    {
        $data = array();
        foreach ($keys as $key) {
            $data[$key] = self::get($key);
        }
        return $data;
    }
}
