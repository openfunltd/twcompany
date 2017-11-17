<?php

class UnitRow extends Pix_Table_Row
{
    protected static $_columns = null;

    public function getData()
    {
        if (is_null(self::$_columns)) {
            self::$_columns = array();
            foreach (ColumnGroup::search(1) as $columngroup) {
                self::$_columns[$columngroup->id] = $columngroup->name;
            }
        }

        $data = new StdClass;
        foreach (UnitData::search(array('id' => $this->id)) as $unitdata) {
            $data->{self::$_columns[$unitdata->column_id]} = json_decode($unitdata->value);
        }

        $data->{'財政部'} = new StdClass;
        foreach (FIAUnitData::search(array('id' => $this->id)) as $unitdata) {
            $data->{'財政部'}->{FIAColumnGroup::getColumnName($unitdata->column_id)} = json_decode($unitdata->value);
        }

        return $data;
    }

    public function getSearchData()
    {
        $data = $this->getData();
        $data = Unit::walkObject($data);

        if (property_exists($data, '出資額(元)')) {
            $new_value = array();
            foreach ($data->{'出資額(元)'} as $name => $amount) {
                // elastic 中最好不要有名稱出現在 key 的情況，因此把他轉成 object
                $new_value[] = array('name' => $name, 'amount' => $amount);
            }
            $data->{'出資額(元)'} = $new_value;
        }

        if (property_exists($data, '公司所在地')) {
            $data->{'公司所在地'} = Unit::toNormalNumber($data->{'公司所在地'});
        }

        if (property_exists($data, '公司名稱') and property_exists($data, '分公司名稱')) {
            // 同時有公司名稱和分公司名稱的話，表示這是總公司，那讓 xxx公司 和 xxx公司台灣分公司 兩者都可以被搜尋到
            if (!is_array($data->{'公司名稱'})) {
                $data->{'公司名稱'} = array($data->{'公司名稱'});
            }
            $data->{'公司名稱'}[] = $data->{'公司名稱'}[0] . $data->{'分公司名稱'};
        } else if (property_exists($data, '分公司名稱')) {
            // 只有分公司名稱的話，就把全稱存進去公司名稱中，並且加上這是分公司，預設不要搜尋到
            $data->{'公司名稱'} = $this->name();
        }
        return $data;
    }

    public function updateSearch()
    {
        $data = $this->getSearchData();
        $curl = curl_init();
        $url = getenv('SEARCH_URL') . '/company/' . $this->id();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PROXY, '');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        $ret = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (!in_array($info['http_code'], array(200, 201))) {
            throw new Exception($info['http_code'] . ' ' . $ret);
        }

        // 處理名稱搜尋
        $columns = array(
            2 => '公司名稱',
            33 => '商業名稱',
            48 => '分公司名稱',
            50 => '總(本)公司統一編號',
        );
        $fia_columns = array(
            3 => '營業人名稱',
        );

        $names = array();
        $uni_name = function($str) {
            if (is_scalar($str)) {
                $name = $str;
            } elseif (is_array($str)) {
                if (is_scalar($str[0])) {
                    $name = $str[0];
                }
                if ($str[0][1] == '(在臺灣地區公司名稱)') {
                    $name = $str[0][0];
                }
            } else {
                return null;
            }
            $name = str_replace('　', '', $name);
            $name = Unit::changeRareWord($name);
            $name = Unit::toNormalNumber($name);
            return $name;
        };

        $values = array();
        foreach (UnitData::search(array('id' => $this->id))->searchIn('column_id', array_keys($columns)) as $ud) {
            if (!array_key_exists($ud->column_id, $values)) {
                $values[$ud->column_id] = array();
            }
            $values[$ud->column_id][] = json_decode($ud->value);
        }
        foreach (UnitChangeLog::search(array('id' => $this->id))->searchIn('column_id', array_keys($columns)) as $ud) {
            if (!array_key_exists($ud->column_id, $values)) {
                $values[$ud->column_id] = array();
            }
            $values[$ud->column_id][] = json_decode($ud->old_value);
            $values[$ud->column_id][] = json_decode($ud->new_value);
        }
        foreach (FIAUnitData::search(array('id' => $this->id))->searchIn('column_id', array_keys($fia_columns)) as $ud) {
            if (!array_key_exists($ud->column_id, $values)) {
                $values[$ud->column_id] = array();
            }
            $values[$ud->column_id][] = json_decode($ud->value);
        }
        foreach (FIAUnitChangeLog::search(array('id' => $this->id))->searchIn('column_id', array_keys($fia_columns)) as $ud) {
            if (!array_key_exists($ud->column_id, $values)) {
                $values[$ud->column_id] = array();
            }
            $values[$ud->column_id][] = json_decode($ud->old_value);
            $values[$ud->column_id][] = json_decode($ud->new_value);
        }

        $names = array();
        foreach (array(2, 3, 33) as $c) { // 公司名稱, 商業名稱, 營業人名稱
            if (!array_key_Exists($c, $values)) {
                continue;
            }
            foreach ($values[$c] as $n) {
                $n = $uni_name($n);
                if ($n) {
                    $names[$n] = true;
                }
            }
        }

        if (array_key_exists(50, $values)) {
            $parents_names = array();
            foreach ($values[50] as $n) {
                $id = $uni_name($n);
                if (!$id) {
                    continue;
                }
                foreach (UnitData::search(array('id' => $id, 'column_id' => 2)) as $ud) {
                    $parents_names[] = json_decode($ud->value);
                }
                foreach (UnitChangeLog::search(array('id' => $id, 'column_id' => 2)) as $ud) {
                    $parents_names[] = json_decode($ud->old_value);
                    $parents_names[] = json_decode($ud->new_value);
                }
            }

            foreach ($values[48] as $n) {
                $branch_name = $uni_name($n);
                if (!$branch_name) {
                    continue;
                }
                foreach ($parents_names as $parent_name) {
                    $parent_name = $uni_name($parent_name);
                    if ($parent_name) {
                        $names[$parent_name . $branch_name] = true;
                    }
                }
            }
        }

        // 先刪除舊的資料
        $curl = curl_init();
        $url = getenv('SEARCH_URL') . '/name_map/_query';
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PROXY, '');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
            'query' => array(
                'term' => array('id' => $this->id()),
            )
        )));
        $ret = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (!in_array($info['http_code'], array(200, 201, 404))) {
            throw new Exception($info['http_code'] . $ret);
        }

        // 新增新的資料
        $command = '';
        $id = $this->id();
        foreach ($names as $name => $true) {
            $command .= json_encode(array(
                'create' => array(
                    '_id' => $id . '-' . $name,
                ),
            ), JSON_UNESCAPED_UNICODE) . "\n";
            $command .= json_encode(array(
                'name' => $name,
                'id' => $id,
            ), JSON_UNESCAPED_UNICODE) . "\n";
        }
        if ($command) {
            $curl = curl_init();
            $url = getenv('SEARCH_URL') . "/name_map/_bulk";
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_PROXY, '');
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $command);
            $ret = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            if (!in_array($info['http_code'], array(200, 201))) {
                throw new Exception($info['http_code'] . $ret);
            }
        }
    }

    public function id()
    {
        return str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    public function getAddress()
    {
        if (1 == $this->type) { // 公司
            $column_id = 6; // 公司所在地
        } elseif (2 == $this->type) { // 商業登記
            $column_id = 38; // 地址
        } elseif (3 == $this->type) { // 分公司
            $column_id = 21; // 分公司所在地
        }
        $data = UnitData::search(array('id' => $this->id, 'column_id' => $column_id))->first();
        return str_replace('\\', '', json_decode($data->value));
    }

    /**
     * getPricipal 取得代表人
     * 
     * @access public
     * @return string
     */
    public function getPricipal()
    {
        if (1 == $this->type) { // 公司
            $column_id = 5; // 代表人
        } elseif (2 == $this->type) { // 商業登記
            $column_id = 34; // 負責人
        } elseif (3 == $this->type) { // 分公司
            $column_id = 49; // 分公司經理姓名
        }
        $data = UnitData::search(array('id' => $this->id, 'column_id' => $column_id))->first();
        $data = json_decode($data->value);
        while (is_array($data)) {
            $data = $data[0];
        }
        return $data;
    }

    /**
     * getCapital 取得資本額跟實收資本額
     * 
     * @access public
     * @return array(資本額,實收資本額)
     */
    public function getCapital()
    {
        if (1 == $this->type) { // 公司
            $columns = array(
                3, // 資本總額(元)
                4, // 實收資本額(元)
            );
        } elseif (2 == $this->type) { // 商業登記
            $columns = array(
                36, // 資本額(元)
                0, // 空
            );
        } elseif (3 == $this->type) { // 分公司
            $columns = array(0, 0);
        }
        $ret = array();
        foreach ($columns as $column_id) {
            if ($column_id == 0 or !$data = UnitData::search(array('id' => $this->id, 'column_id' => $column_id))->first()) {
                $ret[] = '';
            } else {
                $data = intval(str_replace(',', '', json_decode($data->value)));
                $ret[] = $data;
            }
        }
        return $ret;
    }

    public function name($depth = 0)
    {
        $prefix = '';
        if (1 == $this->type) { // 公司
            $column_id = 2;
        } elseif (2 == $this->type) { // 商業登記
            $column_id = 33;
        } elseif (3 == $this->type) { // 分公司
            // 先取總公司
            $data = UnitData::search(array('id' => $this->id, 'column_id' => 50))->first();
            if (!$data) {
                return '';
            }
            $unit = Unit::find(json_decode($data->value));
            if (!$unit) {
                return '';
            }
            if ($depth) {
                return false;
            }
            $prefix = $unit->name($depth + 1);
            if (false === $prefix) {
                return '';
            }
            $column_id = 48;
        } else {
            $column_id = 43;
        }

        if ($data = UnitData::search(array('id' => $this->id, 'column_id' => $column_id))->first()) { // 公司名稱
            $v = json_decode($data->value);
            if (is_scalar($v)) {
                return $prefix . $v;
            } elseif (is_array($v)) {
                return $prefix . $v[0];
            }
        }
    }

    public function get($column)
    {
        return UnitData::search(array('id' => $this->id, 'column_id' => ColumnGroup::getColumnId($column)))->first();
    }

    public function updateData($data)
    {
        $data = (array)$data;
        $old_data = array();
        foreach (UnitData::search(array('id' => $this->id)) as $unitdata) {
            $old_data[$unitdata->column_id] = $unitdata->value;
        }

        $add_data = $delete_data = $modify_data = array();
        foreach ($data as $column => $value) {
            $column_id = ColumnGroup::getColumnId($column);

            if (!array_key_exists($column_id, $old_data)) {
                $add_data[] = $column_id;
            } elseif (json_encode($value, JSON_UNESCAPED_UNICODE) != $old_data[$column_id]) {
                $modify_data[] = $column_id;
            }
        }

        foreach ($old_data as $column_id => $value) {
            if (!array_key_exists(ColumnGroup::getColumnName($column_id), $data)) {
                $delete_data[] = $column_id;
            }
        }

        if (count($add_data) + count($modify_data) + count($delete_data) == 0) {
            return;
        }
        $now = time();

        foreach ($add_data as $column_id) {
            $value = json_encode($data[ColumnGroup::getColumnName($column_id)], JSON_UNESCAPED_UNICODE);
            UnitData::insert(array(
                'id' => $this->id,
                'column_id' => $column_id,
                'value' => $value,
            ));
            UnitChangeLog::insert(array(
                'id' => $this->id,
                'updated_at' => $now,
                'column_id' => $column_id,
                'old_value' => '',
                'new_value' => $value,
            ));
        }

        foreach ($modify_data as $column_id) {
            $value = json_encode($data[ColumnGroup::getColumnName($column_id)], JSON_UNESCAPED_UNICODE);
            $unitdata = UnitData::find(array($this->id, $column_id));
            $unitdata->update(array(
                'value' => json_encode($data[ColumnGroup::getColumnName($column_id)], JSON_UNESCAPED_UNICODE),
            ));
            try {
                UnitChangeLog::insert(array(
                    'id' => $this->id,
                    'updated_at' => $now,
                    'column_id' => $column_id,
                    'old_value' => $old_data[$column_id],
                    'new_value' => $value,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

        foreach ($delete_data as $column_id) {
            try {
                UnitChangeLog::insert(array(
                    'id' => $this->id,
                    'updated_at' => $now,
                    'column_id' => $column_id,
                    'old_value' => $old_data[$column_id],
                    'new_value' => '',
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
            UnitData::find(array($this->id, $column_id))->delete();
        }
        $this->update(array('updated_at' => $now));
    }
}

class Unit extends Pix_Table
{
    public function init()
    {
        $this->_name = 'unit';
        $this->_primary = 'id';
        $this->_rowClass = 'UnitRow';

        $this->_columns['id'] = array('type' => 'int', 'unsigned' => true);
        // 1 - 公司, 2 - 商業登記, 3 - 工廠登記, 4 - 教育部, 99 - 未知來源
        $this->_columns['type'] = array('type' => 'tinyint');
        $this->_columns['updated_at'] = array('type' => 'int');
    }

    protected static $_rare_words = null;

    public static function changeRareWord($word)
    {
        if (is_null(self::$_rare_words)) {
            self::$_rare_words = array();
            $fp = fopen(__DIR__ . '/../maps/rare-word.csv', 'r');
            while ($rows = fgetcsv($fp)) {
                self::$_rare_words[$rows[0]] = $rows[1];
            }
        }

        foreach (self::$_rare_words as $old_word => $new_word) {
            $word = str_replace($old_word, $new_word, $word);
        }

        return $word;
    }

    public static function walkObject($obj)
    {
        if (is_string($obj)) {
            return self::changeRareWord($obj);
        } elseif (is_object($obj)) {
            foreach ($obj as $k => $v) {
                $obj->{$k} = self::walkObject($v);
            }
            return $obj;
        } elseif (is_array($obj)) {
            foreach ($obj as $k => $v) {
                $obj[$k] = self::walkObject($v);
            }
            return $obj;
        } else {
            return $obj;
        }
    }

    public static function chineseNumberToInt($w)
    {
        $chi_number_map = array_flip(array('○', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十'));

        $chars = preg_split('//u', $w, null, PREG_SPLIT_NO_EMPTY);
        if (count($chars) == 1) {
            if ($chars[0] == '廿') {
                return 20;
            }
            return $chi_number_map[$chars[0]];
        } elseif (count($chars) == 2 and $chars[0] == '廿') {
            return 20 + $chi_number_map[$chars[1]];
        } elseif (count($chars) == 2 and $chars[0] == '十') {
            return 10 + $chi_number_map[$chars[1]];
        } elseif (count($chars) == 2 and $chars[1] == '十') {
            return 10 * $chi_number_map[$chars[0]];
        } elseif (strpos($w, '十') === false) {
            $s = '';
            for ($i = 0; $i < count($chars); $i ++) {
                $s .= $chi_number_map[$chars[$i]];
            }
            return $s;
        } elseif (count($chars) == 3 and $chars[1] == '十') {
            return $chi_number_map[$chars[0]] * 10 + $chi_number_map[$chars[2]];
        }

        return $w;
    }

    public static function toNormalNumber($word)
    {
        $number_map = array('０', '１', '２', '３', '４', '５', '６', '７', '８', '９');
        foreach ($number_map as $num => $big_num) {
            $word = str_replace($big_num, $num, $word);
        }

        $word = preg_replace_callback('#[○一二三四五六七八九十廿]+#u', function($matches) {
            return Unit::chineseNumberToInt($matches[0]);
        }, $word);

        return $word;
    }
}
