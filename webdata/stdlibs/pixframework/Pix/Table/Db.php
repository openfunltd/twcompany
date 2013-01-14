<?php

/**
 * Pix_Table_Db 
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db
{
    static public function factory($obj)
    {
	if (is_object($obj) and 'mysqli' == get_class($obj)) {
	    return new Pix_Table_Db_Adapter_Mysqli($obj);
	} elseif (is_array($obj) and isset($obj['cassandra'])) {
	    return new Pix_Table_Db_Adapter_Cassandra($obj['cassandra']);
	} elseif (is_object($obj) and is_a($obj, 'Pix_Table_Db_Adapter')) {
	    return $obj;
	}

	throw new Exception('不知道是哪類的 db' . get_class($obj));
    }
}
