<?php

/**
 * Pix_Table_Db_Adapter_Abstract
 * 
 * @abstract
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
abstract class Pix_Table_Db_Adapter_Abstract implements Pix_Table_Db_Adapter
{
    public function support($id)
    {
        return in_array($id, $this->getSupportFeatures());
    }

    public function getSupportFeatures()
    {
        return array();
    }
}
