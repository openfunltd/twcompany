<?php

/**
 * Pix_Table_Db_Adapter_Mysqli 
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_Mysqli extends Pix_Table_Db_Adapter_MysqlCommon
{
    protected $_link;

    public function __construct($link)
    {
	$this->_link = $link;
    }

    public function getSupportFeatures()
    {
        return array('immediate_consistency', 'check_table');
    }

    /**
     * __get 為了與 MySQLi object 相容所加的 __get($name); 
     * 
     * @param mixed $name 
     * @access public
     * @return void
     */
    public function __get($name)
    {
	return $this->_link->{$name};
    }

    /**
     * __call 為了與 MySQLi 相容所加的 __call()
     * 
     * @param mixed $name 
     * @param mixed $args 
     * @access public
     * @return void
     */
    public function __call($name, $args)
    {
	return call_user_func_array(array($this->_link, $name), $args);
    }

    /**
     * query 對 db 下 SQL query
     * 
     * @param mixed $sql 
     * @access protected
     * @return Mysqli result
     */
    public function query($sql, $table = null)
    {
	if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
	    Pix_Table::debug(sprintf("[%s]\t%40s", $this->_link->host_info, $sql));
	}
	// TODO 需要 log SQL Query 功能
	if ($comment = Pix_Table::getQueryComment()) {
	    $sql = trim($sql, '; ') . ' #' . $comment;
	}

	$starttime = microtime(true);
	$res = $this->_link->query($sql);
	if (($t = Pix_Table::getLongQueryTime()) and ($delta = (microtime(true) - $starttime)) > $t) {
	    Pix_Table::debug(sprintf("[%s]\t%s\t%40s", $this->_link->host_info, $delta, $sql));
	}

	if ($res === false) {
	    if ($errno = $this->_link->errno) {
                switch ($errno) {
                case 1062:
                    throw new Pix_Table_DuplicateException($this->_link->error, $errno);
                case 1406:
                    throw new Pix_Table_DataTooLongException($this->_link->error, $errno);
		default:
                    throw new Exception("SQL Error: {$this->_link->error} SQL: $sql");
		}
            }
	}
	return $res;
    }

    /**
     * quote 將 $str 字串內容 quote 起來。
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function quoteWithColumn($table, $value, $column_name)
    {
	if (is_null($column_name)) {
            return "'" . $this->_link->real_escape_string(strval($value)) . "'";
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
	}
	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
	}
        return "'" . $this->_link->real_escape_string(strval($value)) . "'";
    }

    public function getLastInsertId($table)
    {
        return $this->_link->insert_id;
    }

}
