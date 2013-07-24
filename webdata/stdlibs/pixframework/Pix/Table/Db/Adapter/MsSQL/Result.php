<?php

/**
 * Pix_Table_Db_Adapter_MsSQL_Result
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_MsSQL_Result
{
    public function __construct($res)
    {
        $this->_res = $res;
    }

    public function fetch_assoc()
    {
        return mssql_fetch_assoc($this->_res);
    }

    public function fetch_array()
    {
        return mssql_fetch_array($this->_res);
    }

    public function fetch_object()
    {
        return mssql_fetch_object($this->_res);
    }

    public function free_result()
    {
        return mssql_free_result($this->_res);
    }
}
