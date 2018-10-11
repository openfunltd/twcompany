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

    public static function addDbFromURI($uri)
    {
        if (strpos($uri, 'mysql://') === 0) {
            if (!preg_match('#mysql://([^:]*):([^@]*)@([^/]*)/(.*)#', strval($uri), $matches)) {
                throw new Exception("wrong mysql URI format, must be mysql://{user}:{password}@{host}/{db}");
            }

            $db = new StdClass;
            $db->host = $matches[3];
            $db->username = $matches[1];
            $db->password = $matches[2];
            $db->dbname = $matches[4];
            $config = new StdClass;
            $config->master = $config->slave = $db;
            Pix_Table::setDefaultDb(new Pix_Table_Db_Adapter_MysqlConf(array($config)));
        } elseif (strpos($uri, 'pgsql://') === 0) {
            if (!preg_match('#pgsql://([^:]*):([^@]*)@([^/:]*):?([0-9]*)?/(.*)#', strval($uri), $matches)) {
                throw new Exception("wrong pgsql URI format, must be pgsql://{user}:{password}@{host}/{db}");
            }
            $options = array(
                'host' => $matches[3],
                'user' => $matches[1],
                'password' => $matches[2],
                'dbname' => $matches[5],
            );
            if ($matches[4]) {
                $options['port'] = $matches[4];
            }
            Pix_Table::setDefaultDb(new Pix_Table_Db_Adapter_PgSQL($options));
        } elseif (strpos($uri, 'sqlite://') === 0) {
            $file = substr($uri, strlen('sqlite://'));
            Pix_Table::setDefaultDb(new Pix_Table_Db_Adapter_Sqlite($file));
        } else {
            throw new Exception("add DB failed, unknown uri {$uri}");
        }
    }
}
