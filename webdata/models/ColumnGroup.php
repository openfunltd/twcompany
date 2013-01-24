<?php

class ColumnGroup extends Pix_Table
{
    public function init()
    {
        $this->_name = 'columngroup';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 32);

        $this->addIndex('name', array('name'), 'unique');
    }

    protected static $_column_groups = null;
    protected static $_column_names = null;

    public static function getColumnName($id)
    {
        if (is_null(self::$_column_names)) {
            self::$_column_names = ColumnGroup::search(1)->toArray('name');
        }
        return self::$_column_names[$id];
    }

    public static function getColumnId($name)
    {
        $name = trim($name);
        if (is_null(self::$_column_groups)) {
            self::$_column_groups = array();
            foreach (ColumnGroup::search(1) as $group) {
                self::$_column_groups[$group->name] = $group->id;
            }
        }

        if (!array_key_exists($name, self::$_column_groups)) {
            self::$_column_groups[$name] = ColumnGroup::insert(array('name' => $name))->id;
        }

        return self::$_column_groups[$name];
    }
}
