<?php

/**
 * Pix_Table_Db_Adapter_MysqlConf 可以吃 PIXNET 專用 config 格式來建立 DB 的功能
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_MysqlConf extends Pix_Table_Db_Adapter_MysqlCommon
{
    protected $_config;
    public static $_connect_version = 1;

    public function __get($name)
    {
        throw new Exception("不知名的 column: {$name}");
    }
    public function __construct($config)
    {
	$this->_config = $config;
    }

    protected $_link_pools = array();
    protected $_link_pool_version;

    public function getSupportFeatures()
    {
        return array('force_master', 'immediate_consistency', 'check_table');
    }

    protected function _getLink($type = 'master', $with_ping = true)
    {
        if (array_key_exists('master', $this->_link_pools) and ($link = $this->_link_pools['master']) and $this->_link_pool_version == self::$_connect_version) {
            if ($with_ping) {
                $link->ping();
            }
            return $link;
        }
        if (array_key_exists($type, $this->_link_pools) and ($link = $this->_link_pools[$type])  and $this->_link_pool_version == self::$_connect_version) {
            if ($with_ping) {
                $link->ping();
            }
            return $link;
        }

        $link = mysqli_init();

        $wrong = array();
        $retry = 3;

        for ($i = 0; $i < $retry; $i ++) {
            $confs = $this->_config;

            // 只有設定數量大於 1 筆才需要 shuffle
            while (count($confs) > 1) {
                shuffle($confs);

                // 如果有失敗的 log 並且是在五分鐘以內，暫時不連這一台
                $conf = $confs[0]->{$type};
                if ($time = intval(@file_get_contents("/tmp/Pix_Table_Db_Adapter_MysqlConf-{$conf->host}-{$conf->dbname}")) and $time > time() - 300) {
                    array_shift($confs);
                    continue;
                }
                break;
            }

            $conf = $confs[0]->{$type};
            $link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $starttime = microtime(true);
            @$link->real_connect($conf->host, $conf->username, $conf->password, $conf->dbname);
            $delta = microtime(true) - $starttime;
            if ($delta > 0.5) {
                trigger_error("{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} connect to {$conf->host} time: $delta", E_USER_NOTICE);
            }

            if (!$link->connect_errno) {
                break;
            }
            $error = mysqli_connect_error();
            $wrong[] = "[{$conf->host} $error]";
            file_put_contents("/tmp/Pix_Table_Db_Adapter_MysqlConf-{$conf->host}-{$conf->dbname}", time() . ' ' . $error);
        }

        // $retry 用完了還是失敗
        if ($link->connect_errno) {
            trigger_error("{$_SERVER['HTTP_HOST']} reconnect to ($conf_file) $i times failed: " . implode(', ', $wrong), E_USER_NOTICE);
            throw new Pix_DbConnectErrorException("Connect to ($conf_file)($i times) failed: " . implode(', ', $wrong));
            // 有失敗過
        } elseif ($i) {
            trigger_error("{$_SERVER['HTTP_HOST']} reconnect to ($conf_file) $i times: " . implode(', ', $wrong), E_USER_NOTICE);
        }

        if (property_exists($conf, 'init') and $conf->init) {
            $link->query($conf->init);
        }

        if (property_exists($conf, 'charset') and $conf->charset) {
            $link->set_charset($conf->charset);
        } else {
            $link->set_charset('UTF8');
        }

        $this->_link_pool_version = self::$_connect_version;
        return $this->_link_pools[$type] = $link;
    }

    static public function resetConnect()
    {
        self::$_connect_version ++;
    }

    public function query($sql, $table = null)
    {
        // 判斷要用 Master 還是 Slave
        $type = 'master';
        if (!Pix_Table::$_force_master and preg_match('#^SELECT #', strtoupper($sql))) {
            $type = 'slave';
        }

        if (Pix_Setting::get('Table:ExplainFileSortEnable')) {
            if (preg_match('#^SELECT #', strtoupper($sql))) {
                $res = $this->_getLink($type)->query("EXPLAIN $sql");
                $row = $res->fetch_assoc();
                if (preg_match('#Using filesort#', $row['Extra'])) {
                    trigger_error("Using Filesort Query {$sql}", E_USER_WARNING);
                }
                $res->free_result();
            }
        }

        if (Pix_Setting::get('Table:SQLNoCache')) {
            if (preg_match('#^SELECT #', strtoupper($sql))) {
                $sql = 'SELECT SQL_NO_CACHE ' . substr($sql, 7);
            }
        }

        // 加上 Query Comment
        if ($comment = Pix_Table::getQueryComment()) {
            $sql = trim($sql, '; ') . ' #' . $comment;
        }

        for ($i = 0; $i < 3; $i ++) {
            if (!$link = $this->_getLink($type)) {
                throw new Exception('找不到 Link');
            }

            $starttime = microtime(true);
            $res = $link->query($sql);
            $this->insert_id = $link->insert_id;
            $this->affected_rows = $link->affected_rows;
            $delta = microtime(true) - $starttime;
            $short_sql = mb_strimwidth($sql, 0, 512, "...len=" . strlen($sql));
            if (array_key_exists(Pix_Table::LOG_QUERY, Pix_Table::$_log_groups) and Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
                Pix_Table::debug(sprintf("[%s-%s](%f)%s", strval($link->host_info), $type, $delta, $short_sql));
            } elseif (($t = Pix_Table::getLongQueryTime()) and $delta > $t) {
                Pix_Table::debug(sprintf("[%s-%s](%f)%s", strval($link->host_info), $type, $delta, $short_sql));
            }

            if ($res === false) {
                if ($errno = $link->errno) {
                    $message = (is_null($table) ? '': "Table: {$table->getClass()}") . "SQL Error: ({$errno}){$link->error} " . substr($sql, 0, 128);
                    switch ($errno) {
                    case 1146:
                        throw new Pix_Table_TableNotFoundException($message);
                    case 1062:
                        throw new Pix_Table_DuplicateException((is_null($table) ? '': "(Table: {$table->getClass()})") . $link->error, $errno);
                    case 1406:
                        throw new Pix_Table_DataTooLongException($message);

                    case 2006: // MySQL server gone away
                    case 2013: // Lost connection to MySQL server during query
                        trigger_error("Pix_Table " . $message, E_USER_WARNING);
                        $this->resetConnect();
                        continue 2;
                    }
                }
                throw new Pix_Table_Exception($message);
            }

            if ($link->warning_count) {
                $e = $link->get_warnings();

                do {
                    if (1592 == $e->errno) {
                        continue;
                    }
                    trigger_error("Pix_Table " . (is_null($table) ? '': "Table: {$table->getClass()}") . "SQL Warning: ({$e->errno}){$e->message} " . substr($sql, 0, 128), E_USER_WARNING);
                } while ($e->next());
            }
            return $res;
        }

        throw new Pix_Table_Exception("query 三次失敗");
    }

    public function getLastInsertId($table = null)
    {
        return $this->insert_id;
    }

    public function getAffectedRows($table = null)
    {
        return $this->affected_rows;
    }

}
