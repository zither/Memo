<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Pimple\Container;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router
{
    /**
     * Container
     *
     * @var \Pimple\Container
     */
    public $container;

    /**
     * Routes
     *
     * @var array
     */
    public $routes = array();

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
     * Controller namespace
     *
     * @var string
     */
    public $namespace = "\\Memo\\Controller";

    /**
     * Params
     *
     * @var mixed
     */
    public $params = null;


    /**
     * Construct
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add route
     *
     * @param string $route 
     * @param array $callback an array contains controller and action name 
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
        $serverParams = $request->getServerParams();
        $pathInfo = isset($serverParams["PATH_INFO"]) ? $serverParams["PATH_INFO"] : "/";

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
        $this->params = $this->processParams(array_slice($matches, 1));
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
        return count($params) > 0 ? array_shift($params) : null;
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
     * Invoke action
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return mixed 
     * @throws \BadMethodCallException
     */
    protected function invokeAction(RequestInterface $request, ResponseInterface $response)
    {
        $controllerInstance = $this->instantiateController($request, $response);
        $this->methodExt = ucfirst(strtolower($request->getMethod()));
        $action = strtolower($this->action) . $this->methodExt;

        if (!method_exists($controllerInstance, $action)) {
            throw new \BadMethodCallException(
                sprintf(
                    "Call to undefined method %s::%s",
                    $this->controller,
                    $action
                )
            );
        }

        if (method_exists($controllerInstance, "beforeActionHook")) {
            call_user_func(array($controllerInstance, "beforeActionHook"));
        }

        return call_user_func(
            array($controllerInstance, $action), 
            $this->params
        );    
    }

    /**
     * Get a Controller instance
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return object
     * @throws \RuntimeException
     */
    protected function instantiateController(RequestInterface $request, ResponseInterface $response)
    {
        $controllerName = sprintf(
            "%s\\%s",
            $this->namespace, 
            ucfirst(strtolower($this->controller))
        );

        if (class_exists($controllerName)) {
            $controller = new $controllerName($request, $response);
            if ($controller instanceof $this->namespace) {
                $controller->setContainer($this->container); 
            }
            return $controller;
        }

        throw new \RuntimeException(
            sprintf("Controller dose not exist: %s", $controllerName)
        );
    }
}
