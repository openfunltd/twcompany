<?php

abstract class Pix_Table_Db_Adapter_MysqlCommon extends Pix_Table_Db_Adapter_SQL
{
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
		$s .= 'AUTO_INCREMENT ';
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

	$sql .= " (\n" . implode(", \n", $column_sql) . ") ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 \n";

	return $this->query($sql, $table);
    }

    /**
     * get Pix_Table object from db schema
     *
     * @param string $table_name
     * @param string $pix_table_name
     * @access public
     * @return Pix_Table
     */
    public function getTableFromDb($db_table_name, $pix_table_name = null)
    {
        $table = Pix_Table::newEmptyTable($pix_table_name);
        $table->_name = $table_name;
        $table->setDb($this);

        $res = $this->query("DESCRIBE " . $this->column_quote($table_name));

        while ($row = $res->fetch_assoc()) {
            $field = $row['Field'];
            $table->_columns[$field] = array();

            // check type & size & unsigned
            $type = $row['Type'];
            if (preg_match('#^([a-z]+)\((\d+)\)\s?(unsigned)?$#', $type, $matches)) {
                $table->_columns[$field]['type'] = $matches[1];
                if (array_key_exists(1, $matches) and in_array($matches[1], array('binary', 'varchar', 'char'))) {
                    $table->_columns[$field]['size'] = $matches[2];
                }
                if (array_key_exists(3, $matches) and $matches[3] == 'unsigned') {
                    $table->_columns[$field]['unsigned'] = 1;
                }
            } elseif (preg_match('#^([a-z]+)\s?(unsigned)?$#', $type, $matches)) {
                $table->_columns[$field]['type'] = $matches[1];
                if (array_key_exists(2, $matches) and $matches[2] == 'unsigned') {
                    $table->_columns[$field]['unsigned'] = 1;
                }
            } elseif (preg_match('#^enum\((.*)\)$#', $type, $matches)) {
                $table->_columns[$field]['type'] = 'enum';
                $table->_columns[$field]['list'] = $matches[1];
            } elseif (preg_match('#^set\((.*)\)$#', $type, $matches)) {
                $table->_columns[$field]['type'] = 'set';
                $table->_columns[$field]['list'] = $matches[1];
            } else {
                // XXX: unknown type
            }

            // check autoincrement
            if ($row['Extra'] == 'auto_increment') {
                $table->_columns[$field]['auto_increment'] = 1;
            }

            // check default
            if (!is_null($row['Default'])) {
                $table->_columns[$field]['default'] = $row['Default'];
            }
        }
        $res->free_result();

        // check INDEX
        $res = $this->query("SHOW INDEXES FROM " . $this->column_quote($table->_name));

        $db_indexes = array();
        while ($row = $res->fetch_assoc()) {
            if (!array_key_exists($row['Key_name'], $db_indexes)) {
                $db_indexes[$row['Key_name']] = array(
                    'type' => $row['Non_unique'] ? 'index' : 'unique',
                    'columns' => array(),
                );
            }
            $db_indexes[$row['Key_name']]['columns'][] = $row['Column_name'];
        }
        $res->free_result();

        foreach ($db_indexes as $name => $options) {
            if ('PRIMARY' == $name) {
                $table->_primary = $options['columns'];
            } else {
                $table->addIndex($name, $options['columns'], $options['type']);
            }
        }

        return $table;
    }

    /**
     * get table list on db
     *
     * @access public
     * @return array
     */
    public function getTables()
    {
        $res = $this->query("SHOW TABLES");
        $tables = array();
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
        $res->free_result();
        return $tables;
    }


    /**
     * dropTable 從資料庫內移除 $table 這個 Table
     *
     * @param Pix_Table $table
     * @access public
     * @return void
     */
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

    /**
     * quote 將 $str 字串內容 quote 起來。
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function quoteWithColumn($table, $value, $column_name)
    {
        $link = $this->_getLink('slave');

	if (is_null($column_name)) {
            return "'" . $link->real_escape_string(strval($value)) . "'";
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
	}
	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
	}
        return "'" . $link->real_escape_string(strval($value)) . "'";
    }

    public function checkTable($table)
    {
        $table_on_db = $this->getTableFromDb($table->_name);

        $ret = array();
        foreach (array_intersect(array_keys($table_on_db->_columns), array_keys($table->_columns)) as $column) {
            $options = $table->_columns[$column];
            $options_on_db = $table_on_db->_columns[$column];

            // check auto_increment
            if ((array_key_exists('auto_increment', $options) and $options['auto_increment']) xor (array_key_exists('auto_increment', $options_on_db) and $options_on_db['auto_increment'])) {
                $ret[] = array(
                    'auto_increment',
                    $table,
                    $this,
                    "column {$column} option auto_increment is not match (db: {$options_on_db['auto_increment']}, Model: {$options['auto_increment']})",
                );
            }

            // check default
            if ($options['default'] != $options_on_db['default']) {
                $ret[] = array(
                    'default',
                    $table,
                    $this,
                    "column {$column} option default is not match (db: {$options_on_db['default']}, Model: {$options['default']})",
                );
            }

            // check type
            if ($options['type'] != $options_on_db['type']) {
                $ret[] = array(
                    'type',
                    $table,
                    $this,
                    "column {$column} option type is not match (db: {$options_on_db['type']}, Model: {$options['type']})",
                );
            }

            if (array_key_exists('size', $options) and array_key_exists('size', $options_on_db) and ($options['size'] != $options_on_db['size'])) {
                $ret[] = array(
                    'size',
                    $table,
                    $this,
                    "column {$column} option size is not match (db: {$options_on_db['size']}, Model: {$options['size']})",
                );
            }

            if ($options['unsigned'] != $options_on_db['unsigned']) {
                $ret[] = array(
                    'unsigned',
                    $table,
                    $this,
                    "column {$column} option unsigned is not match (db: {$options_on_db['unsigned']}, Model: {$options['unsigned']})",
                );
            }
            // TODO: check set/enum list options
        }

        // column on db
        foreach (array_diff_key($table->_columns, $table_on_db->_columns) as $column => $options) {
            $ret[] = array(
                'field',
                $table,
                $this,
                "there is no column {$column} on db",
            );
        }

        foreach (array_diff_key($table_on_db->_columns, $table->_columns) as $column => $options) {
            $ret[] = array(
                'field',
                $table,
                $this,
                "column {$column} is on db, but not in pix table",
            );
        }

        if ($table->getPrimaryColumns() != $table_on_db->getPrimaryColumns()) {
            $ret[] = array(
                'primary',
                $table,
                $this,
                "primary key is not match (db: " . implode(', ', $table_on_db->getPrimaryColumns()) . ", model: " . implode(', ', $table->getPrimaryColumns()) .")",
            );
        }

        $indexes_on_table = $table->getIndexes();
        $indexes_on_db = $table_on_db->getIndexes();

        // column on db
        foreach (array_diff_key($indexes_on_table, $indexes_on_db) as $name => $options) {
            $ret[] = array(
                'index',
                $table,
                $this,
                "index {$name} (" . implode(', ', $options['columns']) . ") in table is not on db",
            );
        }

        foreach (array_diff_key($indexes_on_db, $indexes_on_table) as $name => $options) {
            $ret[] = array(
                'index',
                $table,
                $this,
                "index {$name} (" . implode(', ', $options['columns']) . ") on db is not in table, add \$this->addIndex('{$name}', array('" . implode("', '", $options['columns']) . "'), '{$options['type']}');",
            );
        }

        foreach (array_intersect_key($indexes_on_table, $indexes_on_db) as $name => $options) {
            if ($indexes_on_table[$name]['type'] != $indexes_on_db[$name]['type']) {
                $ret[] = array(
                    'index',
                    $table,
                    $this,
                    "index {$name} type is not match(model: {$indexes_on_table[$name]['type']}, db: {$indexes_on_db[$name]['type']})",
                );
            }

            if ($indexes_on_table[$name]['columns'] != $indexes_on_db[$name]['columns']) {
                $ret[] = array(
                    'index',
                    $table,
                    $this,
                    "index {$name} columns is not match(model: " . implode(',', $indexes_on_table[$name]['columns']) . ", db: " . implode(',', $indexes_on_db[$name]['columns']) . ')',
                );
            }

        }

        return $ret;
    }


    abstract public function query($sql);
}
