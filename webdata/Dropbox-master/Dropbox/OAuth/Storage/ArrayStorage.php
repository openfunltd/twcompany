<?php

namespace Dropbox\OAuth\Storage;

class ArrayStorage implements StorageInterface
{
    public function __construct()
    {
        $this->_SESSION = array();
    }
    
    /**
     * Get an OAuth token from the session
     * If the encrpytion object is set then decrypt the token before returning
     * @param string $type Token type to retrieve
     * @return array|bool
     */
    public function get($type)
    {
        if ($type != 'request_token' && $type != 'access_token') {
            throw new \Dropbox\Exception("Expected a type of either 'request_token' or 'access_token', got '$type'");
        } else {
            if (isset($this->_SESSION[$type])) {
                $token = $this->decrypt($this->_SESSION[$type]);
                return $token;
            }
            return false;
        }
    }
    
    /**
     * Set an OAuth token in the session by type
     * If the encryption object is set then encrypt the token before storing
     * @param \stdClass Token object to set
     * @param string $type Token type
     * @return void
     */
    public function set($token, $type)
    {
        if ($type != 'request_token' && $type != 'access_token') {
            throw new \Dropbox\Exception("Expected a type of either 'request_token' or 'access_token', got '$type'");
        } else {
            $token = $this->encrypt($token);
            $this->_SESSION[$type] = $token;
        }
    }
    
    /**
     * Delete the request and access tokens currently stored in the session
     * @return bool
     */
    public function delete()
    {
        $this->_SESSION = array();
        return true;
    }
    
    /**
     * Use the Encrypter to encrypt a token and return it
     * If there is not encrypter object, return just the 
     * serialized token object for storage
     * @param stdClass $token OAuth token to encrypt
     * @return stdClass|string
     */
    protected function encrypt($token)
    {
        // Serialize the token object
        $token = serialize($token);
        
        // Encrypt the token if there is an Encrypter instance
        if ($this->encrypter instanceof Encrypter) {
            $token = $this->encrypter->encrypt($token);
        }
        
        // Return the token
        return $token;
    }
    
    /**
     * Decrypt a token using the Encrypter object and return it
     * If there is no Encrypter object, assume the token was stored
     * serialized and return the unserialized token object
     * @param stdClass $token OAuth token to encrypt
     * @return stdClass|string
     */
    protected function decrypt($token)
    {
        // Decrypt the token if there is an Encrypter instance
        if ($this->encrypter instanceof Encrypter) {
            $token = $this->encrypter->decrypt($token);
        }
        
        // Return the unserialized token
        return @unserialize($token);
    }
}
