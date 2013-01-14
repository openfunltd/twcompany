<?php

/**
 * Pix_Array_Filter_Row Row of Pix_Array_Filter_
 * 
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Array_Filter_Row implements Pix_Array_Filter
{
    public function filter($row, $options)
    {
        $method = array_shift($options);
        return call_user_func_array(array($row, $method), $options);
    }
}
