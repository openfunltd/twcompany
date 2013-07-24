<?php

/**
 * Pix_Table_Search_Term
 *
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Search_Term
{
    /**
     * __construct
     *
     * @param string $term_type
     * @param mixed $term_arguments
     * @access public
     */
    public function __construct()
    {
        $args = func_get_args();

        if (count($args)) {
            $this->_type = array_shift($args);
            if (count($args)) {
                $this->_arguments = $args;
            }
        }
    }

    /**
     * getType
     *
     * @access public
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * getArguments
     *
     * @access public
     * @return array
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * setType
     *
     * @param string $type
     * @access public
     * @return void
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * setArguments
     *
     * @param array $arguments
     * @access public
     * @return void
     */
    public function setArguments($arguments)
    {
        $this->_arguments = $arguments;
    }
}
