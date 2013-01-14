<?php

/**
 * Pix_Table_Db_Adapter_Sqlite
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_Sqlite extends Pix_Table_Db_Adapter_SQL
{
    protected $_pdo;
    protected $_path;
    protected $_name = null;

    public function __construct($path)
    {
        $this->_path = $path;
        $this->_pdo = new PDO("sqlite:" . $path);
    }

    public function getSupportFeatures()
    {
        return array('immediate_consistency');
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function query($sql)
    {
        if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
            Pix_Table::debug(sprintf("[%s]\t%40s", $this->_path . $this->_name, $sql));
        }

        $starttime = microtime(true);
        $statement = $this->_pdo->prepare($sql);
        if (!$statement) {
            if ($errno = $this->_pdo->errorCode()) {
                $errorInfo = $this->_pdo->errorInfo();
            }
            if ($errorInfo[2] == 'PRIMARY KEY must be unique' or
                    preg_match('/columns? .+ (are|is) not unique/', $errorInfo[2])) {
                throw new Pix_Table_DuplicateException();
            }
            throw new Exception("SQL Error: ({$errorInfo[0]}:{$errorInfo[1]}) {$errorInfo[2]} (SQL: {$sql})");
        }
        $res = $statement->execute();
	if (($t = Pix_Table::getLongQueryTime()) and ($delta = (microtime(true) - $starttime)) > $t) {
            Pix_Table::debug(sprintf("[%s]\t%s\t%40s", $this->_path, $delta, $sql));
	}

	if ($res === false) {
            if ($errno = $this->_pdo->errorCode()) {
                $errorInfo = $this->_pdo->errorInfo();
            }
            if ($errorInfo[2] == 'PRIMARY KEY must be unique' or
                    preg_match('/columns? .+ (are|is) not unique/', $errorInfo[2])) {
                throw new Pix_Table_DuplicateException();
            }
            throw new Exception("SQL Error: ({$errorInfo[0]}:{$errorInfo[1]}) {$errorInfo[2]} (SQL: {$sql})");
	}
        
        return new Pix_Table_Db_Adapter_PDO_Result($statement);
    }

    /**
     * createTable 將 $table 建立進資料庫內
     * 
     * @param Pix_Table $table 
     * @access public
     * @return void
     */
    public function createTable($table)
    {
        $sql = "CREATE TABLE " . $this->column_quote($table->getTableName());
        $types = array('bigint', 'tinyint', 'int', 'varchar', 'char', 'text', 'float', 'double', 'binary');
        $primarys = is_array($table->_primary) ? $table->_primary : array($table->_primary);
        $pk_isseted = false;

	foreach ($table->_columns as $name => $column) {
            $s = $this->column_quote($name) . ' ';
            $db_type = in_array($column['type'], $types) ? $column['type'] : 'text';

	    if ($column['unsigned'] and !$column['auto_increment']) {
		$s .= 'UNSIGNED ';
	    }

            if ('int' == $db_type or $column['auto_increment']) {
                $s .= 'INTEGER';
            } else {
                $s .= strtoupper($db_type);
            }

	    if (in_array($db_type, array('varchar', 'char', 'binary'))) {
		if (!$column['size']) {
		    throw new Exception('you should set the option `size`');
		}
		$s .= '(' . $column['size'] . ')';
	    }

            // 在 varchar, char, text 加上 COLLATE NOCASE, 比較時忽略大小寫
            if (in_array($db_type, array('varchar', 'char', 'text'))) {
                $s .= ' COLLATE NOCASE';
            }
	    $s .= ' ';

            if ($column['auto_increment']) {
                if ($primarys[0] != $name or count($primarys) > 1) {
                    throw new Exception('SQLITE 的 AUTOINCREMENT 一定要是唯一的 Primary Key');
                }
                $s .= ' PRIMARY KEY AUTOINCREMENT ';
                $pk_isseted = true;
	    }

	    if (isset($column['default']) and !$column['auto_increment']) {
                $s .= 'DEFAULT ' . $this->quoteWithColumn($table, $column['default'], $name) . ' ';
	    }

	    $column_sql[] = $s;
	}

        if (!$pk_isseted) {
            $s = 'PRIMARY KEY ' ;
            $index_columns = array();
            foreach ((is_array($table->_primary) ? $table->_primary : array($table->_primary)) as $pk) {
                $index_columns[] = $this->column_quote($pk);
            }
            $s .= '(' . implode(', ', $index_columns) . ")\n";
            $column_sql[] = $s;
        }

	$sql .= " (\n" . implode(", \n", $column_sql) . ") \n";

        // CREATE TABLE
        $this->query($sql);

        foreach ($table->getIndexes() as $name => $options) {
            if ('unique' == $options['type']) {
                $s = 'CREATE UNIQUE INDEX ';
            } else {
                $s = 'CREATE INDEX ';
            }
            $columns = $options['columns'];

            $s .= $this->column_quote($table->getTableName() . '_' . $name) . ' ON ' . $this->column_quote($table->getTableName());
            $index_columns = array();
            foreach ($columns as $column_name) {
                $index_columns[] = $this->column_quote($column_name);
            }
            $s .= '(' . implode(', ', $index_columns) . ') ';

            $this->query($s);
        }
    }

    public function dropTable($table)
    {
        if (!Pix_Setting::get('Table:DropTableEnable')) {
            throw new Pix_Table_Exception("要 DROP TABLE 前請加上 Pix_Setting::set('Table:DropTableEnable', true);");
        }
        $sql = "DROP TABLE " . $this->column_quote($table->getTableName());
	return $this->query($sql, $table);
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

    public function quoteWithColumn($table, $value, $column_name = null)
    {
	if (is_null($column_name)) {
            return $this->_pdo->quote($value);
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
	}
	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
	}
        return $this->_pdo->quote($value);
    }

    public function getLastInsertId($table)
    {
        foreach ($table->getPrimaryColumns() as $col) {
            if ($table->_columns[$col]['auto_increment']) {
                return $this->_pdo->lastInsertId();
            }
        }
        return null;
    }
}
