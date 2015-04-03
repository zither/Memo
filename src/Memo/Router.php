<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Slim\Http\Environment;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router
{
    /**
     * Environment
     *
     * @var \Slim\Http\Environment
     */
    public $environment;

    /**
     * Routes
     *
     * @var array
     */
    public $routes = array();

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
     * Params
     *
     * @var mixed
     */
    public $params = null;

    /**
     * Constructor
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
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
        return $this->action . $this->methodExt;
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
     * Dispatch
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function dispatch(RequestInterface $request, ResponseInterface $response)
    {
        if (!isset($this->environment["PATH_INFO"])) {
            $this->environment["PATH_INFO"] = "/";
        }
        $pathInfo = $this->environment["PATH_INFO"];
        if (!empty(trim($pathInfo, "/")) && !$this->matchRoutes($pathInfo)) {
            $this->parsePathInfo($pathInfo);
        }

        try {
            ob_start();
            $newResponse = $this->invokeAction($request, $response);
            $content = ob_get_clean();
            if ($newResponse instanceof ResponseInterface) {
                $response = $newResponse;
            } elseif (is_string($newResponse)) {
                $response->write($newResponse);
            } elseif (is_string($content)) {
                $response->write($content);
            }
        } catch (\Exception $e) {
            ob_end_clean();
            $notFound = new \Slim\Handlers\NotFound();
            $response = $notFound($request, $response);
        }
        return $response;
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
        if (is_array($callback) && count($callback) > 1) {
            $this->controller = $callback[0];
            $this->action = $callback[1];
            $this->processParams(array_slice($matches, 1));
            return true;
        }
        return false;
    }

    /**
     * Process params
     *
     * @param array $params
     *
     * @TODO: multiple params support
     */
    protected function processParams($params)
    {
        if (count($params) > 0) {
            $this->params = array_shift($params);
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
        $this->controller = array_shift($pathArray);
        if (!empty($pathArray)) {
            $this->action = array_shift($pathArray);
        }                 
        $this->params = empty($pathArray) ?: array_shift($pathArray);            
    }

    /**
     * Invoke atction method
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    protected function invokeAction(RequestInterface $request, ResponseInterface $response)
    {
        $controllerInstance = $this->instantiateController($request, $response);
        if (isset($this->environment["REQUEST_METHOD"])) {
            $this->methodExt = ucfirst(
                strtolower($this->environment["REQUEST_METHOD"])
            );            
        }
        $method = strtolower($this->action) . $this->methodExt;

        if (!method_exists($controllerInstance, $method)) {
            var_dump($method);
            throw new \BadMethodCallException(
                sprintf(
                    "Call to undefined method %s::%s",
                    $this->controller,
                    $method
                )
            );
        }

        if (method_exists($controllerInstance, "beforeActionHook")) {
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
    protected function instantiateController(RequestInterface $request, ResponseInterface $response)
    {
        $memoController = sprintf(
            "%s\\%s", 
            $this->memoController, 
            ucfirst(strtolower($this->controller))
        );

        if (class_exists($memoController)) {
            return new $memoController($request, $response);
        }

        if (class_exists($this->controller)) {
            return new $this->controller($request, $response);
        }

        throw new \RuntimeException(
            sprintf("Controller dose not exist: %s", $this->controller)
        );
    }


    /**
     * Mock HTTP request environment
     *
     * @param array $userSettings
     */
    public function mock($userSettings = array())
    {
        $this->environment = $this->environment->mock($userSettings);
    }
}
