<?php 
/**
* Memo Router
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

class Router
{
    /**
     * Routes
     *
     * @var array
     */
    public $routes = array();

    /**
     * Environment
     *
     * @var array
     */
    public $environment = array();

    /**
     * Controller
     *
     * @var string
     */
    public $controller = "index";

    /**
     * Action
     *
     * @var string
     */
    public $action = "index";

    /**
     * Method extension
     *
     * @var string
     */
    public $methodExt = "Get";

    /**
     * Memo controller
     *
     * @var string
     */
    public $memoController = "\\Memo\\Controller";

    /**
     * Callback
     *
     * @var mixed
     */
    public $callback = null;

    /**
     * Params
     *
     * @var mixed
     */
    public $params = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->environment = array_merge($this->environment, $_SERVER);
    }

    /**
     * Add route
     *
     * @param string $route 
     * @param mixed $callback closure or array
     */
    public function addRoute($route, $callback)
    {
        array_push($this->routes, array($route => $callback));
    }

    /**
     * Get controller
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Set default controller
     *
     * @param string $controller
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultController($controller) 
    {
        if (!is_string($controller)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Controller name must be of the type string, %s given.",
                    getType($controller)
                )
            );
        }
        $this->controller = $controller;
    }

    /**
     * Get action
     * 
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set default action
     *
     * @param string $action
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultAction($action)
    {
        if (!is_string($action)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Action name must be of the type string, %s given.",
                    getType($action)
                )             
            );
        }
        $this->action = $action;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @return mixed
     */
    public function dispatch()
    {
        if (!isset($this->environment["PATH_INFO"])) {
            $this->environment["PATH_INFO"] = "/";
        }
        $pathInfo = $this->environment["PATH_INFO"];
        if (!empty(trim($pathInfo, "/")) && !$this->matchRoutes($pathInfo)) {
            $this->parsePathInfo($pathInfo);
        }
        return $this->response();
    }

    /**
     * Match custom routes
     *
     * @param mixed $pathInfo
     *
     * @return boolean
     */
    protected function matchRoutes($pathInfo)
    {
        if (empty($this->routes)) {
            return false;
        }
        foreach ($this->routes as $route) {
            preg_match(sprintf("#^%s$#", key($route)), $pathInfo, $matches);
            if (empty($matches)) {
                continue;
            }
            if ($this->processMatchedRoute($route, $matches)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Process metched route
     *
     * @param mixed $route
     * @param array $matches
     *
     * @return boolean
     */
    protected function processMatchedRoute($route, $matches)
    {
        $callback = current($route);
        if (is_object($callback) && method_exists($callback, "__invoke")) {
            $this->callback = $callback;
            $this->processParams($matches);
            return true;
        }
        if (is_array($callback) && count($callback) > 1) {
            list($controller, $action) = $callback;
            $this->controller = ucfirst(strtolower($controller));
            $this->action = strtolower($action);
            $this->processParams($matches);
            return true;
        }
        return false;
    }

    /**
     * Process params
     *
     * @param mixed $matches
     *
     * @TODO: multiple params support
     */
    protected function processParams($matches)
    {
        if (count($matches) > 1) {
            $this->params = array_shift(array_slice($matches, 1));
        }
    }

    /**
     * Parse pathInfo
     *
     * @param mixed $pathInfo
     */
    protected function parsePathInfo($pathInfo) 
    {
        $pathArray = explode("/", trim($pathInfo, "/"));
        $this->controller = ucfirst(strtolower(array_shift($pathArray)));
        if (!empty($pathArray)) {
            $this->action = strtolower(array_shift($pathArray));
        }                 
        $this->params = empty($pathArray) ?: array_shift($pathArray);            
    }

    /**
     * Response HTTP request
     *
     * @return mixed
     */
    protected function response()
    {
        if (!is_null($this->callback)) {
            return $this->invokeCallback();
        }
        return $this->invokeAction();
    }

    /**
     * Invoke callback function
     *
     * @return mixed
     */
    protected function invokeCallback()
    {
        return call_user_func($this->callback, $this->params);
    }

    /**
     * Invoke atction method
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    protected function invokeAction()
    {
        $controllerInstance = $this->instantiateController();
        if (isset($this->environment["REQUEST_METHOD"])) {
            $this->methodExt = ucfirst(
                strtolower($this->environment["REQUEST_METHOD"])
            );            
        }
        $method = $this->action . $this->methodExt;

        if (!method_exists($controllerInstance, $method)) {
            throw new \BadMethodCallException(
                sprintf(
                    "Call to undefined method %s::%s",
                    $this->controller,
                    $method
                )
            );
        }

        if ($controllerInstance instanceof $this->memoController) {
            call_user_func(array($controllerInstance, "beforeActionHook"));
        }

        return call_user_func(
            array($controllerInstance, $method), 
            $this->params
        );    
    }

    /**
     * Instantiate controller
     *
     * @throws \RuntimeException
     *
     * @return object
     */
    protected function instantiateController()
    {
        $memoController = sprintf(
            "%s\\%s", 
            $this->memoController, 
            $this->controller
        );

        if (class_exists($memoController)) {
            return new $memoController();
        }

        if (class_exists($this->controller)) {
            return new $this->controller();
        }

        throw new \RuntimeException(
            sprintf("Controller dose not exists: %s", $this->controller)
        );
    }


    /**
     * Mock HTTP request environment
     *
     * @param array $userSettings
     */
    public function mock($userSettings = array())
    {
        $defaults = array(
            "REQUEST_METHOD" => "GET",
            "SCRIPT_NAME" => "",
            "PATH_INFO" => "",
            "QUERY_STRING" => "",
            "SERVER_NAME" => "localhost",
            "SERVER_PORT" => 80,
            "ACCEPT" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "ACCEPT_LANGUAGE" => "zh-CN;q=0.8",
            "ACCEPT_CHARSET" => "utf-8,ISO-8859-1;q=0.7,*;q=0.3",
            "USER_AGENT" => "Memo Framework",
            "REMOTE_ADDR" => "127.0.0.1",
        );
        $environment = array_merge($defaults, $userSettings);
        $this->environment = array_merge($this->environment, $environment);
    }
}
