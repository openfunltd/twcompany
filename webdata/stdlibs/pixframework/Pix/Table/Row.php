<?php

/**
 * Pix_Table_Row 
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Row
{
    protected $_tableClass;
    protected $_primary_values = null;

    protected $_data = array();
    protected $_orig_data = array();
    protected $_user_data = array();

    protected $_relation_data = array();

    public function __destruct()
    {
	unset($this->_data);
	unset($this->_orig_data);
    }

    public function equals($row)
    {
	if ($row instanceof Pix_Table_Row) {
	    if ($row->_tableClass != $this->_tableClass) {
		throw new Pix_Table_Exception('這兩個是不同的 ModelRow ，你的程式是不是寫錯了?');
	    }
	    $primary_b = $row->getPrimaryValues();
	} elseif (is_scalar($row)) {
	    $primary_b = array($row);
	} elseif (is_array($row)) {
	    $primary_b = $row;
	}

	$primary_a = $this->getPrimaryValues();

	return $primary_a == $primary_b;
    }

    public function updateByString($args)
    {
        try {
            $this->preSave();
            $this->preUpdate(array());
        } catch (Pix_Table_Row_Stop $e) {
            return;
        }
        $this->getRowDb()->updateOne($this, $args);

        $this->refreshRowData();

        $this->postUpdate(array());
        $this->cacheRow($this->_data);
        $this->postSave();
    }

    public function update($args)
    {
        if (!is_array($args)) {
            return $this->updateByString($args);
	}
	$table = $this->getTable();

        foreach ($args as $column => $value) {
            if ($table->isEditableKey($column)) {
                $this->{$column} = $value;
            }
	}
	$this->save();
    }

    /**
     * getPrimaryValues 取得這個 row 的 primary value
     *
     * @access public
     * @return array|null 若是 null 表示這個 row 還沒被存入 db 中
     */
    public function getPrimaryValues()
    {
        return $this->_primary_values;
    }

    public function getTableClass()
    {
	return $this->_tableClass;
    }

    protected $_table = null;

    /**
     * getTable get the Pix_Table of this row
     *
     * @access public
     * @return Pix_Table
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * delete 刪除這個 row
     * 
     * @param mixed $follow_relation 當設為 false 時，就不會順便刪除 delete=true 的 relation
     * @access public
     * @return void
     */
    public function delete($follow_relation = true)
    {
	if (is_null($this->_primary_values)) {
	    throw new Pix_Table_Exception('這個 Row 還不存在在 DB 上面，不能刪除');
        }

        $table = $this->getTable();

	try {
	    $this->preDelete();
	} catch (Pix_Table_Row_Stop $e) {
	    return;
	}

	if ($follow_relation) {
	    foreach ($table->_relations as $name => $relation) {
                if (array_key_exists('delete', $relation) and $relation['delete']) {
                    if ($relation['rel'] == 'belongs_to' || $relation['rel'] == 'has_one') {
                        if ($this->{$name}) {
                            $this->{$name}->delete();
                        }
		    } else {
			foreach ($this->{$name} as $row) {
			    $row->delete();
			}
		    }
		}
	    }
	}

        $this->getRowDb()->deleteOne($this);
	$this->postDelete();
	$this->cacheRow(null);
	$this->_orig_data = array();
	$this->_data = array();
        $this->_primary_values = null;

        return;
    }

    /**
     * getOriginalData 取得 save 之前的資料內容
     *
     * @access public
     * @return array
     */
    public function getOriginalData()
    {
        return $this->_orig_data;
    }

    /**
     * findPrimaryValues 尋找這個 Row 的 PrimaryValues，與 getPrimaryValues 不同的是，getPrimaryValues要已經存在資料庫
     * 內才能得到資料
     *
     * @access public
     * @return null 資料不全 array PrimaryValues
     */
    public function findPrimaryValues()
    {
	$table = $this->getTable();
	$ret = array();
	foreach ($table->getPrimaryColumns() as $c) {
	    if (!isset($this->_data[$c])) {
		return null;
	    }
	    $ret[] = $this->_data[$c];
	}
	return $ret;
    }

    public function init() { }
    public function preSave() { }
    public function preInsert() { }
    public function preUpdate($changed_fields = null) { }
    public function preDelete() { }
    public function postSave() { }
    public function postInsert() { }
    public function postUpdate($changed_fields = null) { }
    public function postDelete() { }

    /**
     * get changed columns-values assicoate array
     *
     * @access public
     * @return array
     */
    public function getChangedColumnValues()
    {
        $changed_column_values = array();

        foreach ($this->getTable()->_columns as $col => $options) {
            if (array_key_exists($col, $this->_data) and (!array_key_exists($col, $this->_orig_data) or $this->_orig_data[$col] != $this->_data[$col])) {
                $changed_column_values[$col] = $this->_data[$col];
            }
        }

        return $changed_column_values;
    }

    public function save()
    {
	try {
	    $this->preSave();
	} catch (Pix_Table_Row_Stop $e) {
	    return;
	}

        if (!is_null($this->_primary_values)) { // UPDATE
	    try {
                $changed_fields = $this->getChangedColumnValues();
		$this->preUpdate($changed_fields);
	    } catch (Pix_Table_Row_Stop $e) {
		return;
            }

            $changed_fields = $this->getChangedColumnValues();
	    if (!count($changed_fields)) {
		return;
            }

            $this->getRowDb()->updateOne($this, $changed_fields);
	    $this->refreshRowData();
	    $this->cacheRow($this->_data);
	    $this->postUpdate($changed_fields);
	    $this->postSave();
            return $this->_primary_values;
	} else { // INSERT
	    try {
		$this->preInsert();
	    } catch (Pix_Table_Row_Stop $e) {
		return;
	    }

	    // 先清空 cache ，以免在資料庫下完 INSERT 和之後更新 cache 之間的空檔會有問題。
            if ($primary_values = $this->findPrimaryValues()) {
		$this->cacheRow(false);
	    }

            $insert_id = $this->getRowDb()->insertOne($this->getTable(), $this->_data);

            if ($insert_id) {
                $this->_primary_values = array($insert_id);
		$primary_columns = $this->getTable()->getPrimaryColumns();
                $this->_data[$primary_columns[0]] = $insert_id;
	    } else {
                $this->_primary_values = array();
		foreach ($this->getTable()->getPrimaryColumns() as $col) {
                    $this->_primary_values[] = $this->_data[$col];
		}
	    }

	    $this->refreshRowData();
	    $this->cacheRow($this->_data);
	    $this->postInsert();
	    $this->postSave();
            return $insert_id;
	}
    }

    public function __construct($conf, $no_init = false)
    {
	if (!isset($conf['tableClass'])) {
	    throw new Pix_Table_Exception('建立 Row 必需要指定 tableClass');
	}
	$this->_tableClass = $conf['tableClass'];
        $this->_table = Pix_Table::getTable($this->_tableClass);

	if (isset($conf['data'])) {
            $this->_primary_values = array();
	    foreach ($this->getTable()->getPrimaryColumns() as $column) {
		if (!isset($conf['data'][$column])) {
                    throw new Pix_Table_Exception("{$this->_tableClass} Row 的資料抓的不完整(缺少 column: {$column})");
		}
                $this->_primary_values[] = $conf['data'][$column];
	    }
	    $this->_data = $conf['data'];
	    $this->_orig_data = $conf['data'];
	} elseif (isset($conf['default'])) {
	    $this->_data = $conf['default'];
	    $this->_orig_data = $conf['default'];
	}

	if (!$no_init) {
	    $this->init();
	}
    }

    public function getColumn($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        } else {
            return null;
        }
    }

    public function setColumn($name, $value)
    {
	while (Pix_Table::$_verify) {
	    $column = $this->getTable()->_columns[$name];
	    if ($value === null)
		break;
	    switch ($column['type']) {
		case 'int':
		case 'smallint':
		case 'tinyint':
		    // 這邊用 ctype_digit 而不用 is_int 是因為 is_int('123') 會 return false
		    //if (!ctype_digit(strval($value)))
		    // 這邊改用 regex ，因為 ctype_digit 會把負數也傳 false.. orz
                    if (!preg_match('#^[-]?[0-9]+$#', $value)) {
                        throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
                    }

                    if (array_key_exists('unsigned', $column) and $column['unsigned'] and $value < 0) {
                        throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
                    }
                    break;

		case 'enum':
		    if (is_array($column['list']) and !in_array($value, $column['list'])) {
                        throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
                    }
		    break;
		case 'varchar':
		case 'char':
/*		    if (strlen($value) > $column['size'])
			throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
		    break; */
	    }
	    break;
	}
	$this->_data[$name] = $value;
    }

    public function getHook($name)
    {
        if (!array_key_exists('get', $this->getTable()->_hooks[$name])) {
            throw new Pix_Table_Exception("沒有指定 {$name} 的 get 變數");
        }
        $get = $this->getTable()->_hooks[$name]['get'];

        if (is_scalar($get)) {
            return $this->{$get}();
        }

        if (is_callable($get)) {
            return call_user_func($get, $this);
        }

        throw new Pix_Table_Exception('不明的 hook 型態');
    }

    public function setHook($name, $value)
    {
        if (!array_key_exists('set', $this->getTable()->_hooks[$name])) {
            throw new Pix_Table_Exception("沒有指定 {$name} 的 set 變數");
        }
        $set = $this->getTable()->_hooks[$name]['set'];

        if (is_scalar($set)) {
            return $this->{$set}($value);
        }

        if (is_callable($set)) {
            return call_user_func($set, $this, $value);
        }

        throw new Pix_Table_Exception('不明的 hook 型態');
    }

    public function getRelation($name)
    {
	$table = $this->getTable();
	if (isset($this->_relation_data[$name])) {
	    return $this->_relation_data[$name];
        }

        if (!in_array($table->_relations[$name]['rel'], array('has_one', 'belongs_to'))) {
            $foreign_table = $this->getTable()->getRelationForeignTable($name);
            $foreign_keys = $this->getTable()->getRelationForeignKeys($name);
            $primary_values = $this->getPrimaryValues();
            if (count($foreign_keys) !== count($primary_values)) {
                throw new Pix_Table_Exception($this->getTableClass() . ' 在拉 ' . $name . ' relation 時， foreign key 數量不正確');
            }
            $where = array_combine($foreign_keys, $primary_values);

            return $this->_relation_data[$name] = $foreign_table->search($where, $this);
	}

        // A has_one B, A: $this->_table B: $type_Table
        $foreign_table = $this->getTable()->getRelationForeignTable($name);
        $foreign_keys = $this->getTable()->getRelationForeignKeys($name);

        $cols = array();
        foreach ($foreign_keys as $column) {
            $cols[] = $this->{$column};
        }

        if ($row = $foreign_table->find($cols, $this)) {
            $this->_relation_data[$name] = $row;
        } else {
            $row = null;
        }
        return $row;
    }

    public function setRelation($name, $value)
    {
	$table = $this->getTable();
	// 如果是 has_many 不給 set
	if ($table->_relations[$name]['rel'] == 'has_many') {
	    throw new Pix_Table_Exception("has_many 不能夠 ->{$name} = \$value; 請改用 ->{$name}[] = \$value");
	} elseif ('has_one' == $table->_relations[$name]['rel'] || 'belongs_to' == $table->_relations[$name]['rel']) {
	    $this->_relation_data[$name] = null;
	    $type = $table->_relations[$name]['type'];
	    $type_table = Pix_Table::getTable($type);

            $foreign_keys = $table->getRelationForeignKeys($name);

	    if (is_scalar($value)) {
		$value = array($value);
	    }

	    if ($value instanceof Pix_Table_Row and $value->getTableClass() == $type) {
		$value = $value->getPrimaryValues();
	    } elseif ($value == null) {
		$value = array(null);
	    } elseif (!is_array($value)) {
                throw new Pix_Table_Exception(' = 右邊的東西只能是 Row 或是 PK' . $type . get_class($value));
	    }

	    if (count($value) != count($foreign_keys)) {
                throw new Pix_Table_Exception('參數不夠');
	    }

	    $column_values = array_combine($foreign_keys, $value);
	    foreach ($column_values as $column => $value) {
		$this->{$column} = $value;
	    }
	    return;
	} else {
            throw new Pix_Table_Exception('relation 的 rel 只能是 has_many, has_one 或是 belongs_to 喔');
	}
    }

    public function __get($name)
    {
	$table = $this->getTable();
	// State1. 檢查是否在 column 裡面
	if (isset($table->_columns[$name])) {
	    return $this->getColumn($name);
	}

	// State2. 檢查是否在 Relations 裡面
	if (isset($table->_relations[$name])) {
	    return $this->getRelation($name);
	}

	// State3. User 自訂資料
	if ($name[0] === '_') {
	    return $this->_user_data[substr($name, 1)];
	}

	// State5. Aliases 資料
        if (array_key_exists($name, $table->_aliases)) {
            $aliases = $table->_aliases[$name];
	    $rel = $this->getRelation($aliases['relation']);
	    if ($aliases['where']) {
		$rel = $rel->search($aliases['where']);
	    }
	    if ($aliases['order']) {
		$rel = $rel->order($aliases['order']);
	    }
	    return $rel;
	}

	// 檢查 hook 資料
	if (isset($table->_hooks[$name])) {
	    return $this->getHook($name);
	}
        throw new Pix_Table_NoThisColumnException("{$this->getTableClass()} 沒有 {$name} 這個 column");
    }

    public function __set($name, $value)
    {
	$table = $this->getTable();
	// State1. 檢查是否在 column 裡面
	if (isset($table->_columns[$name])) {
	    $this->setColumn($name, $value);
	    return;
	}

	// State2. 檢查是否在 Relations 裡面
	if (isset($table->_relations[$name])) {
	    $this->setRelation($name, $value);
	    return;
	}

	// State3. User 自訂資料
	if ($name[0] === '_') {
	    $this->_user_data[substr($name, 1)] = $value;
	    return;
	}

	// 檢查 hook 資料
	if (isset($table->_hooks[$name])) {
	    $this->setHook($name, $value);
	    return;
	}
        throw new Pix_Table_NoThisColumnException("{$this->getTableClass()} 沒有 {$name} 這個 column");
    }

    public function __isset($name)
    {
	$table = $this->getTable();
	if (isset($table->_columns[$name]) or isset($table->_aliases[$name]))
	    return true;

	if ('has_many' == $table->_relations[$name]['rel']) {
	    return true;
	}

	if ($table->_relations[$name]) {
            $row = $this->getRelation($name);
	    if ($row)
		return true;
	    else
		return false;
	}

	if ($name[0] == '_') {
	    return isset($this->_user_data[substr($name, 1)]);
	}
	return false;
    }

    public function toArray()
    {
	$array = array();
	foreach ($this->getTable()->_columns as $name => $temp) {
	    $array[$name] = $this->{$name};
	}
	return $array;
    }

    public function __unset($name)
    {
	if ($name[0] == '_') {
	    unset($this->_user_data[substr($name, 1)]);
        } elseif ('has_one' == $this->getTable()->_relations[$name]['rel']) {
            $foreign_keys = $this->getTable()->getRelationForeignKeys($name);
	    if ($foreign_keys == $this->getTable()->getPrimaryColumns()) {
		throw new Exception('Foreign Key 等於 Primary Key 的 Relation 不可以直接 unset($obj->rel)');
	    }
	    $this->{$name} = null;
	    $this->save();
	} else {
	    throw new Exception('column name 不可以 unset');
	}
    }

    public function createRelation($relation, $values = array())
    {
        $table = $this->getTable();
        if (!$table->_relations[$relation])
            throw new Exception($relation . ' 不是 relation name ，不能 create_' . $relation);

        if (!is_array($values)) {
            $values = array();
        }

        if (!in_array($table->_relations[$relation]['rel'], array('has_one', 'belongs_to'))) {
            return $this->{$relation}->insert($values);
        }

        $foreign_table = $table->getRelationForeignTable($relation);
        $primary_keys = $foreign_table->getPrimaryColumns();

        $foreign_values = array();
        foreach ($table->getRelationForeignKeys($relation) as $key) {
            $foreign_values[] = $this->{$key};
        }
        $row = $foreign_table->createRow($this);

        foreach (array_merge(array_combine($primary_keys, $foreign_values), $values) as $key => $value) {
            if (!isset($foreign_table->_columns[$key]) and !isset($foreign_table->_relations[$key])) {
                continue;
            }
            $row->{$key} = $value;
        }

        $row->save();
        return $this->_relation_data[$relation] = $row;
    }

    public function __call($name, $args)
    {
	$table = $this->getTable();
        if (preg_match('#create_(.+)#', $name, $ret)) {
            if (count($args) > 0) {
                return $this->createRelation($ret[1], $args[0]);
            } else {
                return $this->createRelation($ret[1]);
            }
        } elseif ($table->getHelperManager('row')->hasMethod($name)) {
            $new_args = $args;
            array_unshift($new_args, $this);
            return $table->getHelperManager('row')->callHelper($name, $new_args);
        } elseif (Pix_Table::getStaticHelperManager('row')->hasMethod($name)) {
            $new_args = $args;
            array_unshift($new_args, $this);
            return Pix_Table::getStaticHelperManager('row')->callHelper($name, $new_args);
	} else {
	    throw new Pix_Table_Exception(get_class($this) . " 沒有 $name 這個 function 喔");
	}
    }

    /**
     * getUniqueID 取得這個 row 的 UNIQUE ID, 由 model name + primary value 的 string 組合，任兩個 row 一定不重覆
     * 
     * @access public
     * @return string
     */
    public function getUniqueID()
    {
	return $this->getTableClass() . ':' . json_encode($this->getPrimaryValues());
    }

    /**
     * refreshRowData 去資料庫更新這一個 row 的資料
     *
     * @access public
     * @return void
     */
    public function refreshRowData()
    {
        $db = $this->getRowDb();
        $this->_relation_data = array();

        // 若 db 不支援 immediate_consistency ，就不需要去 db 更新資料了
        if (!$db->support('immediate_consistency')) {
            return;
        }

        // XXX: 保險起見這邊強制從 master 抓
        if ($db->support('force_master')) {
            $old_force_master = Pix_Table::$_force_master;
            Pix_Table::$_force_master = true;
        }

        if ($row = $db->fetchOne($this->getTable(), $this->getPrimaryValues())) {
	    $this->_data = $this->_orig_data = $row;
        }

        if ($db->support('force_master')) {
            Pix_Table::$_force_master = $old_force_master;
        }
    }

    public function getRowDb()
    {
        return $this->getTable()->getDb();
    }

    public function cacheRow($data)
    {
        $this->getTable()->cacheRow($this->findPrimaryValues(), $data);
    }

    /**
     * stop 在 preXXX 動作呼叫這個可以中斷後面的動作
     *
     * @access public
     * @return void
     */
    public function stop()
    {
        throw new Pix_Table_Row_Stop();
    }
}
