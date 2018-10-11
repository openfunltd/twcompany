<?php

class Pix_Partial_Helper_Capture extends Pix_Helper
{
    public static function getFuncs()
    {
        return array('captureStart', 'captureSet', 'captureAppend', 'getCapture');
    }

    protected $_capture_data = array();

    public function captureStart($partial)
    {
	ob_start();
    }

    public function captureSet($partial, $name)
    {
        $this->_capture_data[$name] = ob_get_clean();
    }

    public function captureAppend($partial, $name)
    {
        if (!isset($this->_capture_data[$name])) {
            $this->_capture_data[$name] = '';
	}
        $this->_capture_data[$name] .= ob_get_clean();
    }

    public function getCapture($partial, $name)
    {
        return $this->_capture_data[$name];
    }
}
