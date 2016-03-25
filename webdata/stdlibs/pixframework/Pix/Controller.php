<?php

/**
 * Pix_Controller
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller
{
    protected static $_dispatchers = array();

    public $view = null;
    protected $controllerName = 'index';
    protected $actionName = 'index';

    public function __construct()
    {
        $this->view = new Pix_Partial(null, array('cache_prefix' => crc32($_SERVER['HTTP_HOST'])));;
    }

    public function init()
    {
    }

    /**
     * noview finish action without drawing view
     *
     * @access public
     * @return void
     */
    public function noview()
    {
        throw new Pix_Controller_NoViewException();
    }

    /**
     * setView set Controller view
     *
     * @param Pix_Partial $v
     * @access public
     * @return void
     */
    public function setView($v)
    {
        $this->view = $v;
    }

    /**
     * getControllerName get Controller name
     *
     * @access public
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * getActionName get Action name
     *
     * @access public
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * draw draw a partial file
     *
     * @param string $filename
     * @access public
     * @return string output
     */
    public function draw($filename)
    {
        return $this->view->partial($filename, $this->view);
    }

    /**
     * redraw finish action and draw another partial $partial_name
     *
     * @param string $partial_name
     * @access public
     * @return void
     */
    public function redraw($partial_name)
    {
        echo $this->draw($partial_name);
        return $this->noview();
    }

    /**
     * getURI get request URI
     *
     * @access public
     * @return string
     */
    public function getURI()
    {
        list($uri, $params) = explode('?', $_SERVER['REQUEST_URI'], 2);
        return $uri;
    }

    /**
     * redirect finish action and redirect to $url
     *
     * @param string $url
     * @param int $code
     * @access public
     * @return void
     */
    public function redirect($url, $code = 302)
    {
        $url = preg_replace_callback('#[^A-Za-z0-9&/=\#?()%]*#', function($matches) { return urlencode($matches[0]); }, $url);
        Pix_HttpResponse::redirect($url, $code);
        return $this->noview();
    }

    /**
     * addDispatcher add new Dispatcher
     *
     * @param Pix_Controller_Dispatcher|callable $dispatcher
     * @static
     * @access public
     * @return void
     */
    public static function addDispatcher($dispatcher)
    {
        if (!($dispatcher instanceof Pix_Controller_Dispatcher ) and !is_callable($dispatcher)) {
            throw new Exception("addDispatcher 只能指定 Pix_Controller_Dispatcher & callable function");
        }
        self::$_dispatchers[] = $dispatcher;
    }

    /**
     *  Pix_Helper_Manager
     */
    protected static $_helper_manager = null;

    /**
     * getHelperManager get Helper Manager
     *
     * @static
     * @access public
     * @return Pix_Helper_Manager
     */
    public static function getHelperManager()
    {
        if (is_null(self::$_helper_manager)) {
            self::$_helper_manager = new Pix_Helper_Manager();
        }
        return self::$_helper_manager;
    }

    /**
     * addHelper add static helper in Pix_Controller
     *
     * @param string $helper Helper name
     * @param array $methods
     * @param array $options
     * @static
     * @access public
     * @return void
     */
    public static function addHelper($helper, $methods = null, $options = array())
    {
        $manager = self::getHelperManager();
        $manager->addHelper($helper, $methods, $options);
    }

    /**
     * addCommonHelpers add common helper
     *
     * @static
     * @access public
     * @return void
     */
    public static function addCommonHelpers()
    {
        self::addHelper('Pix_Controller_Helper_Http');
        self::addHelper('Pix_Controller_Helper_Json');
        self::addHelper('Pix_Controller_Helper_Alert');
    }

    public function __call($func, $args)
    {
        if (self::getHelperManager()->hasMethod($func)) {
            array_unshift($args, $this);
            return self::getHelperManager()->callHelper($func, $args);
        }

        throw new Pix_Controller_Dispatcher_Exception("Unknown action {$func}");
    }

    /**
     * dispatch dispatch the request action
     *
     * @param string $data_path
     * @static
     * @access public
     * @return void
     */
    public static function dispatch($data_path)
    {
        $baseDir = rtrim($data_path, '/');

        // dispatch
        foreach (self::$_dispatchers as $dispatcher) {
            list($uri, $params) = explode('?', $_SERVER['REQUEST_URI'], 2);
            if (is_callable($dispatcher)) {
                list($controllerName, $actionName, $params) = $dispatcher($uri);
            } elseif ($dispatcher instanceof Pix_Controller_Dispatcher) {
                list($controllerName, $actionName, $params) = $dispatcher->dispatch($uri);
            } else {
                throw new Exception('不明的 Dispatcher');
            }
            if (!is_null($controllerName) and !is_null($actionName)) {
                break;
            }
        }

        if (is_null($controllerName) or is_null($actionName)) {
            list($uri, $params) = explode('?', $_SERVER['REQUEST_URI'], 2);
            $default_dispatcher = new Pix_Controller_Dispatcher_Default();
            list($controllerName, $actionName, $params) = $default_dispatcher->dispatch($uri);
        }

        try {
            if (is_null($controllerName)) {
                throw new Pix_Controller_Dispatcher_Exception('no controllerName');
            }

            $className = ucfirst($controllerName) . 'Controller';
            $file = $baseDir . '/controllers/' . $className . '.php';
            if (!class_exists($className, false)) {
                if (file_exists($file)) {
                    include($file);
                } else {
                    throw new Pix_Controller_Dispatcher_Exception('404 Controller file not found: ' . $file);
                }
            }

            if (!class_exists($className)) {
                throw new Pix_Controller_Dispatcher_Exception('404 Class not found');
            }

            $controller = new $className();
            $controller->controllerName = $controllerName;
            $controller->actionName = $actionName;
            $controller->view->setPath("$baseDir/views/");
            $controller->init($params);
            if (is_null($controller->actionName)) {
                throw new Pix_Controller_Dispatcher_Exception();
            }

            if (!method_exists($controller, $controller->actionName . 'Action')) {
                throw new Pix_Controller_Dispatcher_Exception('404 Method not found');
            }
            $controller->{$controller->actionName . 'Action'}($params);

            $file = $controller->view->getPath() . "$controllerName/$controller->actionName.phtml";
            if (file_exists($file)) {
                echo $controller->draw("$controllerName/$controller->actionName.phtml");
            } else {
                throw new Pix_Controller_Dispatcher_Exception("404 View file not found!");
            }
        } catch (Pix_Controller_NoViewException $exception) {
            // no view, do nothing
        } catch (Exception $exception) {
            if (file_exists($baseDir . '/controllers/ErrorController.php')) {
                include($baseDir . '/controllers/ErrorController.php');
                $controller = new ErrorController();
                $controller->view->setPath("$baseDir/views/");
            } else {
                $controller = new Pix_Controller_DefaultErrorController();
                $controller->view->setPath(__DIR__ . '/Controller/DefaultErrorController/views');
            }

            $controller->view->exception = $exception;
            try {
                $controller->init($params);
                $controller->errorAction($params);
                $file = $controller->view->getPath() . "error/error.phtml";
                if (file_exists($file)) {
                    echo $controller->draw("error/error.phtml");
                }
            } catch (Pix_Controller_NoViewException $exception) {
                // no view, do nothing
            }
        }
    }
}
