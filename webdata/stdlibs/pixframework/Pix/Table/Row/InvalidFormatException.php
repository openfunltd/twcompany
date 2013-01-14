<?php

/**
 * Pix_Table_Row_InvalidFormatException 
 * 
 * @uses Exception
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Row_InvalidFormatException extends Exception
{
    public $column;

    public function __construct($name, $column, $row)
    {
        parent::__construct($row->getTableClass() . '的欄位 ' . $name. ' 格式不正確');

	$this->column = $column;
    }
}
