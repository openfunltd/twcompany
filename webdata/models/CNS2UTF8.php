<?php

class CNS2UTF8
{
    protected static $_maps = null;

    public static function convert($page, $code)
    {
        $maps = self::_getMap();

        if (!$unicode = $maps[intval(hexdec($page))][strtolower($code)]) {
            return '';
        }

        return html_entity_decode('&#x' . $unicode . ';');
    }

    protected static function _getMap()
    {
        if (!is_null(self::$_maps)) {
            return self::$_maps;
        }

        self::$_maps = array();
        foreach (glob(__DIR__ . '/../maps/cns2unicode/cns_unicode*.txt') as $file) {
            $fp = fopen($file, 'r');
            while (false !== ($line = fgets($fp))) {
                if ($line[0] == '#') {
                    continue;
                }
                list($cns, $unicode) = explode("\t", trim($line));
                list($page, $code) = explode('-', $cns);

                self::$_maps[intval($page)][strtolower($code)] = $unicode;
            }
        }
        return self::$_maps;
    }
}
