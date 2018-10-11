<?php

class KeyValue extends Pix_Table
{
    public function init()
    {
        $this->_name = 'key_value';
        $this->_primary = 'key';

        $this->_columns['key'] = array('type' => 'char', 'size' => 32);
        $this->_columns['value'] = array('type' => 'text');
    }

    public static function get($key)
    {
        return KeyValue::find(strval($key))->value;
    }

    public static function set($key, $value)
    {
        try {
            KeyValue::insert(array(
                'key' => $key,
                'value' => $value,
            ));
        } catch (Pix_Table_DuplicateException $e) {
            KeyValue::search(array('key' => $key))->update(array('value' => $value));
        }
    }
}
