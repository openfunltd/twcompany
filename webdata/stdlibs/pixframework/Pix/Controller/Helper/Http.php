<?php

/**
 * Pix_Controller_Helper_Http
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller_Helper_Http extends Pix_Helper
{
    public function getFuncs()
    {
        return array('checkModified', 'isPost', 'isPut', 'isDelete', 'isGet');
    }

    public function checkModified($controller, $options = array())
    {
        if (isset($options['etag']) and trim($_SERVER['HTTP_IF_NONE_MATCH']) == $options['etag']) {
            $not_modified = true;
        } elseif (isset($options['last_modified_time']) and strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $options['last_modified_time']) {
            $not_modified = true;
        } else {
            $not_modified = false;
        }

        if ($options['last_modified_time']) {
            header('Age: ' . (time() - $options['last_modified_time']));
        }

        $max_age = isset($options['max_age']) ? intval($options['max_age']) : 86400;
        // 有 Last-Modified-Time 的話, max-age 會按照 Last-Modified-Time 起算, 不是 max-age 之後 expire
        if (!$options['last_modified_time']) {
            header('Cache-Control: max-age=' . $max_age);
        }

        if ($not_modified) {
            header("HTTP/1.1 304 Not Modified");
            return $controller->noview();
        }

        if ($options['last_modified_time']) {
            header('Last-Modified: ' . date("r", $options['last_modified_time']));
            header('Expires: ' . date("r", time() + $max_age));
        }
        if ($options['etag']) {
            header('Etag: ' . $options['etag']);
        }
        // 不要設定 Pragma
        header('Pragma: ');
    }

    /**
     * isGet 判斷是否為 GET
     *
     */
    public function isGet($controller)
    {
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * isDelete 判斷是否為 Delete
     *
     */
    public function isDelete($controller)
    {
        if ('DELETE' == $_SERVER['REQUEST_METHOD']) {
            return TRUE;
        }

        if ('POST' == $_SERVER['REQUEST_METHOD'] and array_key_exists('_method', $_POST) and 'delete' == strtolower($_POST['_method'])) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * isPost 判斷是否為 POST
     *
     */
    public function isPost($controller, $raw = FALSE)
    {
        // 不是 POST HTTP method 的一定不會是 POST
        if ('POST' != $_SERVER['REQUEST_METHOD']) {
            return FALSE;
        }

        // 如果表示只看 raw status，加上這邊一定會是 POST HTTP method，就直接傳回 TRUE
        if ($raw) {
            return TRUE;
        }

        // 如果沒有 _method (表示不是 convention)，傳回 TRUE
        if (!array_key_exists('_method', $_POST)) {
            return TRUE;
        }

        // 如果有 _method，就要判斷裡面是不是 post
        if ('post' == strtolower($_POST['_method'])) {
            return TRUE;
        }

        // 剩下都是 FALSE
        return FALSE;
    }

    /**
     * isPut 判斷是否為 Put
     *
     */
    public function isPut($controller)
    {
        if ('PUT' == $_SERVER['REQUEST_METHOD']) {
            return TRUE;
        }

        if ('POST' == $_SERVER['REQUEST_METHOD'] and array_key_exists('_method', $_POST) and 'put' == strtolower($_POST['_method'])) {
            return TRUE;
        }

        return FALSE;
    }

}
