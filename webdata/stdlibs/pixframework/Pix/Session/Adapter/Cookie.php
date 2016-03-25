<?php

/**
 * Pix_Session_Adapter_Cookie
 *
 * @package Session
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Session_Adapter_Cookie extends Pix_Session_Adapter
{
    protected $_data = array();

    public function __construct($config = array())
    {
        Pix_Session_Adapter::__construct($config);

        list($sig, $data) = explode('|', $_COOKIE[$this->_getCookieKey()], 2);
        if (!$secret = $this->getOption('secret')) {
            throw new Pix_Exception('you should set the option `secret`');
        }
        if ($this->_sig($data . $this->_getCookieDomain(), $secret) != $sig) {
            return;
        }

        $data = json_decode($data, true);

        $this->_data = $data;
    }

    protected function _getSignatureMethod()
    {
        return $this->hasOption('signature_method') ? $this->getOption('signature_method') : 'hmac_sha256';
    }

    protected function _getCookieKey()
    {
        return $this->hasOption('cookie_key') ? $this->getOption('cookie_key') : session_name();
    }

    protected function _getCookiePath()
    {
        return $this->hasOption('cookie_path') ? $this->getOption('cookie_path') : '/';
    }

    protected function _getCookieDomain()
    {
        return $this->hasOption('cookie_domain') ? $this->getOption('cookie_domain') : $_SERVER['HTTP_HOST'];
    }

    protected function _getSecure()
    {
        return $this->hasOption('secure') ? $this->getOption('secure') : false;
    }

    protected function _getTimeout()
    {
        return $this->hasOption('timeout') ? $this->getOption('timeout') : null;
    }

    protected function setCookie()
    {
        $data = json_encode($this->_data);
        $sig = $this->_sig($data . $this->_getCookieDomain(), $this->getOption('secret'));
        $params = session_get_cookie_params();
        Pix_HttpResponse::setcookie(
            $this->_getCookieKey(),
            $sig . '|' . $data,
            $this->_getTimeout() ? (time() + $this->_getTimeout()) : null,
            $this->_getCookiePath(),
            $this->_getCookieDomain(),
            $this->_getSecure()
        );
    }

    public function set($key, $value)
    {
        if ($this->_data[$key] !== $value) {
            $this->_data[$key] = $value;
            $this->setCookie();
        }
    }

    protected function _sig($string, $secret)
    {
        $signature_method = $this->_getSignatureMethod();

        if (is_scalar($signature_method) and in_array($signature_method, array('crc32', 'hmac_sha256'))) {
            switch ($signature_method) {
            case 'crc32':
                return crc32($string . $secret);
            case 'hmac_sha256':
                return hash_hmac('sha256', $string, $secret);
            }
        }

        if (is_callable($signature_method)) {
            return call_user_func($signature_method, $string);
        }

        throw new Pix_Exception('unknown signature method');
    }

    public function get($key)
    {
        return $this->_data[$key];
    }

    public function delete($key)
    {
        unset($this->_data[$key]);
        $this->setCookie();
    }

    public function clear()
    {
        $this->_data = array();
        $this->setCookie();
    }
}
