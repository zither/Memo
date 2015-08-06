<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    use ContainerAware;

    /**
     * Routes
     *
     * @var array
     */
    public $routes = [];

    /**
     * Controller prefix
     *
     * @var string
     */
    public $controllerNamespace = "App\\Controller\\";

    /**
     * Controller name
     *
     * @var string
     */
    public $controller = "index";

    /**
     * Action name
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
     * Params
     *
     * @var array
     */
    public $params = [];

    /**
     * Add route
     *
     * @param string $route 
     * @param array $callback an array contains controller and action name 
     */
    public function addRoute($route, $callback)
    {
        array_push($this->routes, [$route => $callback]);
    }

    /**
     * Set controller namespace
     *
     * @param string $namespace
     *
     * @throw InvalidArgumentException
     */
    public function setControllerNamespace($namespace)
    {
        if (!is_string($namespace)) {
            throw new InvalidArgumentException(sprintf(
                "Controller prefix must be of the type string, %s given",
                getType($namespace)
            ));
        }
        $this->controllerNamespace = $namespace;
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
     * @throws InvalidArgumentException
     */
    public function setDefaultController($controller) 
    {
        if (!is_string($controller)) {
            throw new InvalidArgumentException(
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
     * @throws InvalidArgumentException
     */
    public function setDefaultAction($action)
    {
        if (!is_string($action)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Action name must be of the type string, %s given.",
                    getType($action)
                )
            );
        }
        $this->action = $action;
    }

    /**
     * Dispatch request
     *
     * @param ServerRequestInterface $request
     *
     * @return array 
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $pathInfo = $request->getUri()->getPath();
        if (!empty(trim($pathInfo, "/")) && !$this->matchRoutes($pathInfo)) {
            $this->parsePathInfo($pathInfo);
        }

        $controller = $this->controllerNamespace . ucfirst(strtolower($this->controller));
        $this->methodExt = ucfirst(strtolower($request->getMethod()));
        $action = strtolower($this->action) . $this->methodExt;

        return ["controller" => $controller, "action" => $action, "params" => $this->params];
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
            if (false !== $this->processMatchedRoute($route, $matches)) {
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
        if (!is_array($callback) || count($callback) < 2) {
            return false;
        }

        $this->controller = $callback[0];
        $this->action = $callback[1];
        $this->params = array_slice($matches, 1);
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
        $this->params = empty($pathArray) ? [] : $pathArray;            
    }
}
