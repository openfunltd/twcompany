<?php

/**
 * Pix_Table_Helper_EAV 
 * 
 * @uses Pix
 * @options relation => relation name (default eavs)
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Helper_EAV extends Pix_Helper
{
    protected function _getRelation()
    {
	return ($relation = $this->getOption('relation')) ? $relation : 'eavs';
    }

    protected function _getKeyColumn()
    {
	return ($column = $this->getOption('key_column')) ? $column : 'key';
    }

    protected function _getValueColumn()
    {
	return ($column = $this->getOption('value_column')) ? $column : 'value';
    }

    public function incEAV($row, $key, $delta = 1, $max = null)
    {
	// TODO : 如果 backend support a = a + 1 就應該改用 a = a + 1 用法
	if (is_null($max)) {
	    $this->setEAV($row, $key, intval($this->getEAV($row, $key)) + $delta);
	} else {
	    $this->setEAV($row, $key, min(intval($this->getEAV($row, $key)) + $delta, $max));
	}
    }

    public function decEAV($row, $key, $delta = 1, $min = null)
    {
	// TODO : 如果 backend support a = a + 1 就應該改用 a = a + 1 用法
	if (is_null($min)) {
	    $this->setEAV($row, $key, intval($this->getEAV($row, $key)) - $delta);
	} else {
	    $this->setEAV($row, $key, max(intval($this->getEAV($row, $key)) - $delta, $min));
	}
    }

    public function getEAV($row, $key)
    {
	$key_column = $this->_getKeyColumn();
	$value_column = $this->_getValueColumn();
	$data = ($eav = $row->{$this->_getRelation()}->search(array($key_column => $key))->first()) ? $eav->{$value_column} : null;

	return $data;
    }

    public function setEAV($row, $key, $value)
    {
	$key_column = $this->_getKeyColumn();
	$value_column = $this->_getValueColumn();
	if (is_null($value)) {
	    $row->{$this->_getRelation()}->search(array($key_column => $key))->delete();
	    return true;
	}

	try {
	    $row->{$this->_getRelation()}->insert(array($key_column => $key, $value_column => $value));
	} catch (Pix_Table_DuplicateException $e) {
	    $row->{$this->_getRelation()}->search(array($key_column => $key))->update(array($value_column => $value));
	}
	return true;
    }
}
