<?php

class UnitLocation extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unit_location';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['geo'] = array('type' => 'geography', 'modifier' => array('POINT', 4326));
    }

    public function _getDb()
    {
        if (!preg_match('#pgsql://([^:]*):([^@]*)@([^/]*)/(.*)#', strval(getenv('PGSQL_DATABASE_URL')), $matches)) {
            die('pgsql only');
        }
        $options = array(
            'host' => $matches[3],
            'user' => $matches[1],
            'password' => $matches[2],
            'dbname' => $matches[4],
        );
        return new Pix_Table_Db_Adapter_PgSQL($options);
    }
}
