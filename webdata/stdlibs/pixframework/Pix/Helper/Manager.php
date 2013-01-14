<?php

/**
 * Pix_Helper_Manager Helper manager for all Pix Framework class
 * 
 * @package Helper
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Helper_Manager
{
    protected $_method_to_helper_map = array();
    protected $_wildcard_helpers = array();

    protected $_helper_infos = array();

    /**
     * addHelper add helper
     *
     * @param string $helper a Pix_Helper class name
     * @param array|null $methods helper list (default: $helper->getFuncs())
     * @param array|null $options helper options
     * @access public
     * @return void
     */
    public function addHelper($helper, $methods = null, $options = null)
    {
        if (!is_scalar($helper)) {
            throw new Pix_Helper_Exception('Helper name must be string');
        }

        if (is_null($methods)) {
            if (!class_exists($helper)) {
                throw new Pix_Helper_Exception("{$helper} is not a class");
            }

            if (!is_scalar($helper) or !is_subclass_of($helper, 'Pix_Helper')) {
                throw new Pix_Helper_Exception("{$helper} is not a Pix_Helper");
            }

            $methods = call_user_func(array($helper, 'getFuncs'));
        }

        if (!is_array($methods)) {
            throw new Pix_Helper_Exception("Pix_Helper::addHelper() expects parameter 3 to be array, " . gettype($methods) . " given");
        }

        $id = array_push($this->_helper_infos, array(
            'helper' => $helper,
            'options' => $options
        )) - 1;

        foreach ($methods as $method) {
            if ('*' == $method) {
                $this->_wildcard_helpers[] = $id;
            } else {
                $this->_method_to_helper_map[strtolower($method)] = $id;
            }
        }
    }

    /**
     * getMethods get all method names in this helper manager
     *
     * @access public
     * @return array method names (lower case)
     */
    public function getMethods()
    {
        // TODO: wildcard helper support
        return array_keys($this->_method_to_helper_map);
    }

    /**
     * hasMethod check if method is in this manager
     *
     * @param string $method method name (case insensitive)
     * @access public
     * @return boolean
     */
    public function hasMethod($method)
    {
        return !is_null($this->_getHelperIdByMethodName($method));
    }

    /**
     * _getHelperIdByMethodName get Helper ID by method name
     *
     * @param string $method
     * @access protected
     * @return null-not found, int-helper id
     */
    protected function _getHelperIdByMethodName($method)
    {
        if (array_key_exists(strtolower($method), $this->_method_to_helper_map)) {
            return $this->_method_to_helper_map[strtolower($method)];
        }

        foreach ($this->_wildcard_helpers as $helper_id) {
            $helper_info = $this->_getHelperInfo($helper_id);
            if ($helper_info['object']->hasMethod($method)) {
                $this->_method_to_helper_map[strtolower($method)] = $helper_id;
                return $helper_id;
            }
        }

        return null;
    }

    /**
     * _getHelperInfo get Helper info by Id
     *
     * @param int $helper_id
     * @access protected
     * @return array array(object-helper object, helper-helper class name, options-helper options)
     */
    protected function _getHelperInfo($helper_id)
    {
        $helper_info = $this->_helper_infos[$helper_id];

        if (!array_key_exists('object', $helper_info)) {
            $helper = $helper_info['helper'];

            if (!class_exists($helper)) {
                throw new Pix_Helper_Exception("{$helper} is not a class");
            }

            if (!is_scalar($helper) or !is_subclass_of($helper, 'Pix_Helper')) {
                throw new Pix_Helper_Exception("{$helper} is not a Pix_Helper");
            }

            $helper_info['object'] = new $helper($helper_info['options']);
            $this->_helper_infos[$helper_id] = $helper_info;
        }

        return $helper_info;
    }

    /**
     * callHelper call a helper method
     *
     * @param string $method method name (case insensitive)
     * @param array $args arguments
     * @access public
     * @return mixed helper method return value
     */
    public function callHelper($method, $args)
    {
        $helper_id = $this->_getHelperIdByMethodName($method);
        if (is_null($helper_id)) {
            throw new Pix_Helper_Exception("There is no {$method} in Helper");
        }
        $helper_info = $this->_getHelperInfo($helper_id);

        return call_user_func_array(array($helper_info['object'], $method), $args);
    }
}
