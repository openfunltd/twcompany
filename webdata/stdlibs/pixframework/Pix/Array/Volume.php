<?php

/**
 * Pix_Array_Volume VolumnMode 的加強版，順便修掉當初的錯字 XD
 * 
 * @package Array
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Array_Volume extends Pix_Helper
{
    static public function getFuncs()
    {
        return array('volume', 'volumemode');
    }

    static public function volumemode($array, $chunk = 100)
    {
        return new Pix_Array_Volume_ResultSet($array, array('chunk' => $chunk, 'simple_mode' => true));
    }

    static public function volume($array, $options = array())
    {
        return new Pix_Array_Volume_ResultSet($array, $options);
    }
}
