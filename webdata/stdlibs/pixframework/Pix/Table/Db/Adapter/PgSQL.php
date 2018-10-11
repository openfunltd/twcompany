<?php

/**
 * Pix_Table_Db_Adapter_PgSQL
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_PgSQL extends Pix_Table_Db_Adapter_SQL
{
    protected $_pdo = null;
    protected $_pdo_version;
    protected $_path;
    protected $_name = null;
    public static $_connect_version = 1;


    public function __construct($options)
    {
        $this->_path = $options['host'];
        $this->_options = $options;
    }

    public function resetConnect()
    {
        self::$_connect_version ++;
    }

    public function getPDO()
    {
        if ($this->_pdo_version == self::$_connect_version) {
            return $this->_pdo;
        }

        if (!is_null($this->_pdo)) {
            unset($this->_pdo);
        }

        $config = array();
        foreach ($this->_options as $key => $value) {
            if (in_array($key, array('host', 'port', 'user', 'password', 'dbname'))) {
                $config[] = $key . '=' . $value;
            }
        }
        $this->_pdo = new PDO("pgsql:" . implode(';', $config));
        $this->_pdo_version = self::$_connect_version;
        return $this->_pdo;
    }

    public function getSQLConditionByTerm(Pix_Table_Search_Term $term, $table = null)
    {
        switch ($term->getType()) {
        case 'location/distance-with-in':
            $arguments = $term->getArguments();
            $column = $arguments[0];
            $latlon = $arguments[1];
            $distance = $arguments[2];

            return "ST_DWithin(" . $this->column_quote($column) . ", ST_GeographyFromText('POINT(" . floatval($latlon[1]) . " " . floatval($latlon[0]) . ")'), " . intval($distance) . ")";
        }

        throw new Pix_Table_Exception('Unsupport Pix_Table_Search_Term: ' . $term->getType());
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
        $short_sql = mb_strimwidth($sql, 0, 512, "...len=" . strlen($sql));
        if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
            Pix_Table::debug(sprintf("[%s]\t%40s", $this->_path . $this->_name, $short_sql));
        }

        $starttime = microtime(true);
        $pdo = $this->getPDO();
        $statement = $pdo->prepare($sql);
        if (!$statement) {
            if ($errno = $pdo->errorCode()) {
                $errorInfo = $pdo->errorInfo();
            }
            if ($errorInfo[2] == 'PRIMARY KEY must be unique' or
                    preg_match('/duplicate key value violates unique constraint/', $errorInfo[2])) {
                throw new Pix_Table_DuplicateException($errorInfo[2]);
            }
            throw new Exception("SQL Error: ({$errorInfo[0]}:{$errorInfo[1]}) {$errorInfo[2]} (SQL: {$short_sql})");
        }
        $res = $statement->execute();
	if (($t = Pix_Table::getLongQueryTime()) and ($delta = (microtime(true) - $starttime)) > $t) {
            Pix_Table::debug(sprintf("[%s]\t%s\t%40s", $pdo->getAttribute(PDO::ATTR_SERVER_INFO), $delta, $short_sql));
	}

	if ($res === false) {
            if ($errno = $statement->errorCode()) {
                $errorInfo = $statement->errorInfo();
            }
            if ($errorInfo[2] == 'PRIMARY KEY must be unique' or
                    preg_match('/duplicate key value violates unique constraint/', $errorInfo[2])) {
                throw new Pix_Table_DuplicateException($errorInfo[2]);
            }
            throw new Exception("SQL Error: ({$errorInfo[0]}:{$errorInfo[1]}) {$errorInfo[2]} (SQL: {$short_sql})");
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
        $sql = "CREATE TABLE \"" . $table->getTableName() . '"';
        $types = array('bigint', 'tinyint', 'int', 'varchar', 'char', 'text', 'float', 'double', 'binary', 'geography', 'json');
        $primarys = is_array($table->_primary) ? $table->_primary : array($table->_primary);
        $pk_isseted = false;

	foreach ($table->_columns as $name => $column) {
            $s = $this->column_quote($name) . ' ';
            //$db_type = in_array($column['type'], $types) ? $column['type'] : 'text';
            $db_type = $column['type'];

	    if ($column['unsigned'] and !$column['auto_increment']) {
		$s .= 'UNSIGNED ';
	    }

            if ($column['auto_increment']) {
                $s .= 'SERIAL';
            } elseif ('int' == $db_type) {
                $s .= 'INTEGER';
            } elseif ('binary' == $db_type) {
                $s .= 'BYTEA';
            } elseif (in_array($db_type, array('geography', 'geometry'))) {
                if (array_key_exists('modifier', $column)) {
                    $type = in_array(strtoupper($column['modifier'][0]), array('GEOMETRYCOLLECTION', 'POINT', 'LINESTRING', 'POLYGON', 'MULTIPOINT', 'MULTILINESTRING', 'MULTIPOLYGON')) ? strtoupper($column['modifier'][0]) : 'GEOMETRY';
                    $srid = array_key_exists(1, $column['modifier']) ? intval($column['modifier'][1]) : 0;
                } else {
                    $type = 'POINT';
                    $srid = 0;
                }
                $s .= $db_type . '(' . $type . ',' . $srid . ')';
            } else {
                $s .= strtoupper($db_type);
            }

	    if (in_array($db_type, array('varchar', 'char'))) {
		if (!$column['size']) {
		    throw new Exception('you should set the option `size`');
		}
		$s .= '(' . $column['size'] . ')';
	    }

            $s .= ' ';

            if ($column['auto_increment']) {
                if ($primarys[0] != $name or count($primarys) > 1) {
                    throw new Exception('SQLITE 的 AUTOINCREMENT 一定要是唯一的 Primary Key');
                }
                $s .= ' PRIMARY KEY ';
                $pk_isseted = true;
	    }

            if (isset($column['default'])) {
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
        $sql = "DROP TABLE \""  . $table->getTableName() . '"';
	return $this->query($sql, $table);
    }

    /**
     * column_quote 把 $a 字串加上 quote
     * 
     * @param string $a 
     * @param Pix_Table $table
     * @access public
     * @return string
     */
    public function column_quote($a, $table = null)
    {
        if (is_null($table) or !array_key_exists($a, $table->_columns)) {
            return '"' . addslashes($a) . '"';
        }

        $column_options = $table->_columns[$a];
        if ('geography' == $column_options['type']) {
            return 'ST_AsGeoJSON("' . addslashes($a) . '"::geometry) AS "' . addslashes($a) . '"';
        } elseif ('geometry' == $column_options['type']) {
            return 'ST_AsGeoJSON("' . addslashes($a) . '") AS "' . addslashes($a) . '"';
        }

        return '"' . addslashes($a) . '"';
    }

    public function quoteWithColumn($table, $value, $column_name = null)
    {
        $pdo = $this->getPDO();
        if (is_null($column_name)) {
            return $pdo->quote($value);
        }
        if ($table->isNumbericColumn($column_name)) {
            return intval($value);
        }
        if (in_array($table->_columns[$column_name]['type'], array('geography', 'geometry'))) {
            return "ST_GeomFromGeoJSON(" . $pdo->quote(json_encode($value)) . ")";
        }

        if ('json' == $table->_columns[$column_name]['type'] and !is_scalar($value)) {
            return $pdo->quote(json_encode($value));
        }

        if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
        }
        return $pdo->quote($value);
    }

    public function getLastInsertId($table)
    {
        $pdo = $this->getPDO();
        foreach ($table->getPrimaryColumns() as $col) {
            if ($table->_columns[$col]['auto_increment']) {
                return $pdo->lastInsertId($table->getTableName() . '_' . $col . '_seq');
            }
        }
        return null;
    }

    /**
     * get all tables in this db
     *
     * @access public
     * @return array
     */
    public function getTables()
    {
        $res = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        $tables = array();
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
        $res->free_result();
        return $tables;
    }

    /**
     * _get_clause 從 $search 條件中，回傳 ORDER BY ... LIMIT ...
     * 
     * @param Pix_Table_Search $search 
     * @access protected
     * @return string
     */
    protected function _get_clause($search)
    {
        $sql = '';
        if ($order = $search->order()) {
            if (is_array($order)) {
                // 如果指定 before 的話，順序要調過來
                if ($search->before()) {
                    $order = Pix_Table_Search::reverseOrder($order);
                }
                $order_term = array();
                foreach ($order as $column => $way) {
                    $order_term[] = $this->column_quote($column) . ' ' . $way;
                }
                $sql .= ' ORDER BY ' . implode(', ', $order_term);
            } else {
                $sql .= ' ORDER BY ' . $order;
            }
        }

        $limit = $search->limit();
        if (!is_null($limit)) {
            $offset = $search->offset();
            if (!is_null($offset)) {
                $sql .= ' OFFSET ' . $offset . ' LIMIT ' . $limit;
            } else {
                $sql .= ' LIMIT ' . $limit;
            }
        }
        return $sql;
    }
}
