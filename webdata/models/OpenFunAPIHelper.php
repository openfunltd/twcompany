<?php

/**
 * 處理歐噴公司相關 API 的使用統計以及認證
 */
class OpenFunAPIHelper
{
    protected static $_db = null;
    public static function getDb()
    {
        if (is_null(self::$_db)) {
            if (!$database_url = getenv('API_COUNTER_DATABASE_URL')) {
                throw new Exception('API_COUNTER_DATABASE_URL not set');
            }
            $url = parse_url($database_url);
            $dsn = "{$url['scheme']}:host={$url['host']};port={$url['port']};dbname=" . ltrim($url['path'], '/');
            self::$_db = new PDO($dsn, $url['user'], $url['pass']);
            self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$_db;
    }

    protected static $_api_options = null;
    public static function checkUsage($options)
    {
        self::$_api_options = $options;
        // TODO: 檢查是否有到用量限制
    }

    public static function apiDone($options)
    {
        $size = $options['size'] ?? 0;
        $project = self::$_api_options['project'] ?? 'unknown';
        $class = self::$_api_options['class'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'unknown';
        $counts = [];
        $counts[] = ['project-count', "project-count:{$project}", 1];
        $counts[] = ['project-size', "project-size:{$project}", $size];
        $counts[] = ['call-count-class', "call-count:{$project}:class:{$class}", 1];
        $counts[] = ['call-size-class', "call-size:{$project}:class:{$class}", $size];
        $counts[] = ['call-count-ip', "call-count:{$project}:ip:{$ip}", 1];
        $counts[] = ['call-size-ip', "call-size:{$project}:ip:{$ip}", $size];

        if (preg_match('/^https?:\/\/([^\/]+)(.*)$/', $referer, $matches)) {
            $domain = $matches[1];
            $counts[] = ['call-count-referer', "call-count:{$project}:referer:{$domain}", 1];
            $counts[] = ['call-size-referer', "call-size:{$project}:referer:{$domain}", $size];
        }
        self::counterInc($counts);
    }

    public static function getMappingId($values)
    {
        // 先查一輪是否有資料
        $checking_values = array_combine($values, $values);
        $found_values = [];

        $db = self::getDb();
        $sql = "SELECT id, value FROM mapping WHERE (key, value) IN ";
        $terms = [];
        $params = [];
        foreach ($values as $idx => $value) {
            $terms[] = "(:key_{$idx}, :value_{$idx})";
            $params[":key_{$idx}"] = crc32($value);
            $params[":value_{$idx}"] = $value;
        }
        $sql .= '(' . implode(',', $terms) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $found_values[$row['value']] = $row['id'];
            unset($checking_values[$row['value']]);
        }

        if (!count($checking_values)) {
            // 如果 $checking_values 沒有值，表示都已經找到對應的 id
            return $found_values;
        }

        // 如果 $checking_values 還有值，表示沒有找到對應的 id，需要插入新的資料
        // begin transaction
        $db->beginTransaction();

        // 啟動 transcaction 之後再檢查一次看看有沒有資料
        $sql = "SELECT id, value FROM mapping WHERE (key, value) IN ";
        $terms = [];
        $params = [];
        foreach (array_keys($checking_values) as $idx => $value) {
            $terms[] = "(:key_{$idx}, :value_{$idx})";
            $params[":key_{$idx}"] = crc32($value);
            $params[":value_{$idx}"] = $value;
        }
        $sql .= '(' . implode(',', $terms) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $found_values[$row['value']] = $row['id'];
            unset($checking_values[$row['value']]);
        }

        if (!count($checking_values)) {
            // 如果 $checking_values 沒有值，表示都已經找到對應的 id
            $db->commit();
            return $found_values;
        }

        // 剩下的再寫入 maping 中
        $sql = "INSERT INTO mapping (key, value, created_at) VALUES ";
        $terms = [];
        $params = [];
        $params[':created_at'] = time();
        foreach (array_keys($checking_values) as $idx => $value) {
            $terms[] = "(:key_{$idx}, :value_{$idx}, :created_at)";
            $params[":key_{$idx}"] = crc32($value);
            $params[":value_{$idx}"] = $value;
        }
        $sql .= implode(',', $terms);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // 取得剛剛寫入的 id
        $sql = "SELECT id, value FROM mapping WHERE (key, value) IN ";
        $terms = [];
        $params = [];
        foreach (array_keys($checking_values) as $idx => $value) {
            $terms[] = "(:key_{$idx}, :value_{$idx})";
            $params[":key_{$idx}"] = crc32($value);
            $params[":value_{$idx}"] = $value;
        }
        $sql .= '(' . implode(',', $terms) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $found_values[$row['value']] = $row['id'];
            unset($checking_values[$row['value']]);
        }

        // commit transaction
        $db->commit();
        return $found_values;
    }

    public static function counterInc($counts)
    {
        try {
            self::counterInc_exec($counts);
        } catch (Exception $e) {
            // 如果有錯誤就不做別的事
            error_log($e->getMessage());
            return false;
        }
    }

    public static function counterInc_exec($counts)
    {
        $start_time = microtime(true);
        // 先抓取所有 value ，來取得他的 id
        $map = [];
        foreach ($counts as $count_list) {
            list($group_val, $count_val, $count) = $count_list;
            $map[$group_val] = null;
            $map[$count_val] = null;
        }
        $map = self::getMappingId(array_keys($map));

        // 取得 id 之後，開始寫入資料
        $db = self::getDb();
        $sql = "INSERT INTO counter (id, group_id, count) VALUES ";
        $terms = [];
        $params = [];
        foreach ($counts as $idx => $count_list) {
            list($group_val, $count_val, $count) = $count_list;
            $terms[] = "(:id_{$idx}, :group_id_{$idx}, :count_{$idx})";
            $params[":id_{$idx}"] = $map[$count_val];
            $params[":group_id_{$idx}"] = $map[$group_val];
            $params[":count_{$idx}"] = $count;
        }
        $sql .= implode(',', $terms);
        $sql .= " ON CONFLICT (id) DO UPDATE SET count = counter.count + EXCLUDED.count";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // 寫入 hourly, daily
        foreach ([
            'hourly' => date('YmdH'),
            'daily' => date('Ymd'),
        ] as $period => $time) {
            $sql = "INSERT INTO counter_{$period} (id, group_id, count, time) VALUES ";
            $terms = [];
            $params = [];
            foreach ($counts as $idx => $count_list) {
                list($group_val, $count_val, $count) = $count_list;
                $terms[] = "(:id_{$idx}, :group_id_{$idx}, :count_{$idx}, :time_{$idx})";
                $params[":id_{$idx}"] = $map[$group_val];
                $params[":group_id_{$idx}"] = $map[$count_val];
                $params[":count_{$idx}"] = $count;
                $params[":time_{$idx}"] = $time;
            }
            $sql .= implode(',', $terms);
            $sql .= " ON CONFLICT (id, time) DO UPDATE SET count = counter_{$period}.count + EXCLUDED.count";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        $delta = microtime(true) - $start_time;
        $ret = new stdClass();
        $ret->time = $delta;
        $ret->map = $map;
        // 如果沒錯誤就不做別的事
    }
}
