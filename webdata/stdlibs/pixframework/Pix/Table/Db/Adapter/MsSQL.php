<?php

/**
 * Pix_Table_Db_Adapter_MsSQL
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_MsSQL extends Pix_Table_Db_Adapter_MysqlCommon
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
     * query 
     * 
     * @param string $sql 
     * @access protected
     * @return mssql result
     */
    public function query($sql, $table = null)
    {
	if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
	    Pix_Table::debug(sprintf("[%s]\t%40s", strval($this->_link), $sql));
	}
        // TODO: log sql query
	if ($comment = Pix_Table::getQueryComment()) {
	    $sql = trim($sql, '; ') . ' #' . $comment;
	}

	$starttime = microtime(true);
        $res = mssql_query($sql, $this->_link);
	if (($t = Pix_Table::getLongQueryTime()) and ($delta = (microtime(true) - $starttime)) > $t) {
	    Pix_Table::debug(sprintf("[%s]\t%s\t%40s", strval($this->_link), $delta, $sql));
	}

	if ($res === false) {
            throw new Exception("SQL Error: {$this->_link} SQL: $sql");
	}
	return new Pix_Table_Db_Adapter_MsSQL_Result($res);
    }

    public function column_quote($a)
    {
        return "[" . addslashes($a) . "]";
    }

    /**
     * create table on db
     * 
     * @param Pix_Table $table 
     * @access public
     * @return void
     */
    public function createTable($table)
    {
        $sql = "CREATE TABLE " . $this->column_quote($table->getTableName());
	$types = array('bigint', 'tinyint', 'int', 'varchar', 'char', 'text', 'float', 'double', 'binary');

	foreach ($table->_columns as $name => $column) {
            $s = $this->column_quote($name) . ' ';
	    $db_type = in_array($column['type'], $types) ? $column['type'] : 'text';
	    $s .= strtoupper($db_type);

	    if (in_array($db_type, array('varchar', 'char', 'binary'))) {
		if (!$column['size']) {
		    throw new Exception('you should set the option `size`');
		}
		$s .= '(' . $column['size'] . ')';
	    }
	    $s .= ' ';

	    if ($column['unsigned']) {
		$s .= 'UNSIGNED ';
	    }

	    if (isset($column['not-null']) and !$column['not-null']) {
		$s .= 'NULL ';
	    } else {
		$s .= 'NOT NULL ';
	    }

	    if (isset($column['default'])) {
                $s .= 'DEFAULT ' . $this->quoteWithColumn($table, $column['default'], $name) . ' ';
	    }

	    if ($column['auto_increment']) {
		$s .= 'IDENTITY (1, 1) ';
	    }

	    $column_sql[] = $s;
	}

	$s = 'PRIMARY KEY ' ;
	$index_columns = array();
	foreach ((is_array($table->_primary) ? $table->_primary : array($table->_primary)) as $pk) {
            $index_columns[] = $this->column_quote($pk);
	}
	$s .= '(' . implode(', ', $index_columns) . ")\n";
	$column_sql[] = $s;

        foreach ($table->getIndexes() as $name => $options) {
            if ('unique' == $options['type']) {
                $s = 'UNIQUE KEY ' . $this->column_quote($name) . ' ';
            } else {
                $s = 'KEY ' . $this->column_quote($name);
            }
            $columns = $options['columns'];

            $index_columns = array();
            foreach ($columns as $column_name) {
                $index_columns[] = $this->column_quote($column_name);
            }
            $s .= '(' . implode(', ', $index_columns) . ') ';

            $column_sql[] = $s;
	}

	$sql .= " (\n" . implode(", \n", $column_sql) . ") \n";

	return $this->query($sql, $table);
    }

    /**
     * quote $str with column type
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function quoteWithColumn($table, $value, $column_name)
    {
        if (is_null($column_name)) {
            // from http://php.net/manual/en/function.mssql-query.php vollmer at ampache dot org
            $value = str_replace("'", "''", $value);
            $value = str_replace("\0", "[NULL]", $value); 
            return "'" . strval($value) . "'";
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
	}
	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
        }
        return $this->quoteWithColumn($table, $value, null);
    }

    /**
     * get Pix_Table object from db schema
     *
     * @param string $db_table_name
     * @param string $pix_table_name
     * @access public
     * @return Pix_Table
     */
    public function getTableFromDb($db_table_name, $pix_table_name = null)
    {
        $table = Pix_Table::newEmptyTable($pix_table_name);
        $table->_name = $db_table_name;
        $table->setDb($this);

        $res = $this->query("SP_PKEYS " . $this->column_quote($db_table_name));
        $table->_primary = array();
        while ($row = $res->fetch_object()) {
            $table->_primary[] = $row->COLUMN_NAME;
        }
        $res->free_result();

        $res = $this->query("SP_COLUMNS " . $this->column_quote($db_table_name));
        // http://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.help.ase_15.0.sprocs/html/sprocs/sprocs225.htm
        $data_types = array(
            1 => 'char',
            3 => 'int', // decimal
            8 => 'double',
            6 => 'float',
            4 => 'int',
            2 => 'int', // numeric
            7 => 'double', // real
            5 => 'smallint',
            12 => 'varchar',
            -5 => 'bigint',
            -2 => 'binary',
            9 => 'string', // date
            -1 => 'text', // long varchar
            -4 => 'blob', // long binary
        );
        while ($row = $res->fetch_object()) {
            $field = $row->COLUMN_NAME;
            $table->_columns[$field] = array();

            if (!array_key_exists($row->DATA_TYPE, $data_types)) {
                throw new Pix_Table_Exception("Unknown DATA_TYPE: " . $row->DATA_TYPE);
            }

            $table->_columns[$field]['type'] = $data_types[$row->DATA_TYPE];

            if ($row->TYPE_NAME == 'int identity') {
                $table->_columns[$field]['auto_increment'] = 1;
            }

            // check default
            if (!is_null($row->COLUMN_DEFAULT)) {
                $table->_columns[$field]['default'] = $row->COLUMN_DEFAULT;
            }
        }
        $res->free_result();

        return $table;
    }

    public function getTables()
    {
        $res = $this->query("SP_TABLES");
        $tables = array();
        while ($row = $res->fetch_object()) {
            if ($row->TABLE_TYPE == 'TABLE') {
                $tables[] = $row->TABLE_NAME;
            }
        }
        $res->free_result();
        return $tables;
    }

    public function getLastInsertId($table)
    {
        $res = $this->query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]");
        if ($row = $res->fetch_object()) {
            return $row->SCOPE_IDENTITY;
        }
        return null;
    }

}
