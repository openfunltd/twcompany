<?php

class Elastic
{
    public static function dbQuery($url, $method = 'GET', $data = null)
    {
        $curl = curl_init(getenv('ELASTIC_URL') . $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $_user = getenv('ELASTIC_USER');
        $_password = getenv('ELASTIC_PASSWORD');
        curl_setopt($curl, CURLOPT_USERPWD, $_user . ':' . $_password);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        if ($method != 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!is_null($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $content = curl_exec($curl);
        $obj = json_decode($content);
        if (!$obj) {
            throw new Exception("error: " . $content);
        }
        if (property_exists($obj, 'error') and $obj->error) {
            file_put_contents('error.data', $data);
            throw new Exception("error: " . json_encode($obj, JSON_UNESCAPED_UNICODE));
        }
        curl_close($curl);
        return $obj;
    }

    public static $_db_bulk_pool = [];

    public static function dbBulkCommit($mapping = null)
    {
        if (is_null($mapping)) {
            $mappings = array_keys(self::$_db_bulk_pool);
        } else {
            $mappings = [$mapping];
        }
        $prefix = getenv('ELASTIC_PREFIX');
        foreach ($mappings as $mapping) {
            $ret = self::dbQuery("/{$prefix}{$mapping}/_bulk", 'PUT', self::$_db_bulk_pool[$mapping]);
            $ids = [];
            foreach ($ret->items as $command) {
                foreach ($command as $action => $result) {
                    if ($result->status == 200 or $result->status == 201) {
                        $ids[] = $result->_id;
                        continue;
                    }
                    file_put_contents('error.data', self::$_db_bulk_pool[$mapping]);
                    print_r($result);
                    continue;
                }
            }

            error_log(sprintf("bulk commit, update (%d) %s", count($ids), mb_strimwidth(implode(',', $ids), 0, 200)));
            self::$_db_bulk_pool[$mapping] = '';
        }
    }

    public static function dbBulkInsert($mapping, $id, $data)
    {
        if (!array_key_exists($mapping, self::$_db_bulk_pool)) {
            self::$_db_bulk_pool[$mapping] = '';
        }
        $encdata = json_encode([
            'doc' => $data,
            'doc_as_upsert' => true,
        ]);
        if (!$encdata) {
            return;
        }
        self::$_db_bulk_pool[$mapping] .=
            json_encode(array(
                'update' => array('_id' => $id),
            )) . "\n"
            . $encdata . "\n";
        if (strlen(self::$_db_bulk_pool[$mapping]) > 1000000) {
            self::dbBulkCommit($mapping);
        }
    }

    public static function createIndex($name, $data)
    {
        $prefix = getenv('ELASTIC_PREFIX');
        return self::dbQuery("/{$prefix}{$name}", 'PUT', json_encode([
            'mappings' => $data,
        ]));
    }

    public static function dropIndex($name)
    {
        $prefix = getenv('ELASTIC_PREFIX');
        return self::dbQuery("/{$prefix}{$name}", 'DELETE');
    }
}
