<?php

class Big52003
{
    public static function iconv($content)
    {
        return preg_replace_callback('/[\x81-\xfe]([\x40-\x7e]|[\xa1-\xfe])/', function($matches){
            return Big52003::big5ToUTF8($matches[0]);
        }, $content);
    }

    protected static $_maps = null;

    public function big5ToUTF8($word)
    {
        if (is_null(self::$_maps)) {
            $fp = fopen(__DIR__ . '/../maps/big5-2003/big5uni.txt', 'r');
            self::$_maps = array();
            while (false !== ($line = fgets($fp))) {
                if (0 === strpos($line, '#')) {
                    continue;
                }

                list($big5, $utf8) = explode(' ', trim($line), 2);
                $utf8_word = html_entity_decode("&#" . hexdec($utf8) . ";");
                self::$_maps[hexdec($big5) / 256][hexdec($big5) % 256] = $utf8_word;
            }
            fclose($fp);
        }
        $chars = unpack('C2', $word);
        return self::$_maps[$chars[1]][$chars[2]];
    }
}
