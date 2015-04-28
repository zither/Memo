<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class App extends \Pimple\Container
{
    protected $defaultSettings = [
        "cookieLifetime" => "20 minutes",
        "cookiePath" => "/",
        "cookieDomain" => null,
        "cookieSecure" => false,
        "cookieHttpOnly" => false,
        "httpVersion" => "1.1",
        "debug" => true
    ];

    public function __construct(array $userSettings = [])
    {
        parent::__construct();

        $this["settings"] = function ($c) use ($userSettings) {
            return array_merge($c->defaultSettings, $userSettings);
        };

        $this["environment"] = function () {
            return new \Slim\Http\Environment($_SERVER);
        };

        $this["request"] = $this->factory(function ($c) {
            $env = $c["environment"];
            $method = $env["REQUEST_METHOD"];
            $uri = \Slim\Http\Uri::createFromEnvironment($env);
            $headers = \Slim\Http\Headers::createFromEnvironment($env);
            $cookies = \Slim\Http\Cookies::parseHeader($headers->get("Cookie", []));
            $serverParams = $env->all();
            $body = new \Slim\Http\Body(fopen("php://input", "r"));

            return new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        });

        $this["response"] = $this->factory(function ($c) {
            $headers = new \Slim\Http\Headers(["Content-Type" => "text/html"]);
            $response = new \Slim\Http\Response(200, $headers);

            return $response->withProtocolVersion($c["settings"]["httpVersion"]);
        });

        $this["router"] = function ($c) {
            return (new Router())->setContainer($c);
        };
    }

    /**
     * Add route
     *
     * @param string $route
     * @param array $callback
     */
    public function addRoute($route, $callback)
    {
        $this["router"]->addRoute($route, $callback);
    }

    /**
     * Run app
     *
     * @throws \RuntimeException if controller dose not return an instance of ResponseInterface
     *
     * @return ResponseInterface
     */
    public function run()
    {
        try {
            $routeInfo = $this["router"]->dispatch($this["request"]);
            $callable = $this->resolveCallable($routeInfo);
            $response = call_user_func_array($callable, $routeInfo["params"]);
            if (!$response instanceof ResponseInterface) {
                throw new \RuntimeException(
                    "Controller must return instance of \Psr\Http\Message\ResponseInterface"
                );
            }
        } catch (\Memo\Exception $e) {
            $response = $e->getResponse();
        } catch (\Exception $e) {
            if (false === $this["settings"]["debug"]) {
                $content = "Not Found";
            } else {
                $content = $e->getMessage();
            }
            $response = $this["response"]->withStatus(404)
                                         ->withHeader('Content-Type', 'text/html')
                                         ->write($content);
        }

        return $this->sendResponse($response);
    }

    /**
     * Resolve callable
     *
     * @param array $routeInfo
     *
     * @throws \RuntimeException if controller does not exist
     * @throws \BadMethodCallException if action is undefined
     *
     * @return array
     */
    protected function resolveCallable(array $routeInfo)
    {
        $controllerName = sprintf(
            "%s\\%s",
            "\\Memo\\Controllers", 
            $routeInfo["controller"]
        );

        if (!class_exists($controllerName)) {
            throw new \RuntimeException(
                sprintf("Controller does not exist: %s", $controllerName)
            );
        }

        $controller = new $controllerName($this["request"], $this["response"]);
        if ($controller instanceof \Memo\Controller) {
            $controller->setContainer($this); 
        } 
        if (method_exists($controller, "beforeActionHook")) {
            $controller->beforeActionHook();
        }

        if (!method_exists($controller, $routeInfo["action"])) {
            throw new \BadMethodCallException(
                sprintf(
                    "Call to undefined method %s::%s",
                    $controllerName,
                    $routeInfo["action"]
                )
            );
        }

        return [$controller, $routeInfo["action"]];
    }

    /**
     * Send response
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function sendResponse(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        $hasBody = (204 !== $statusCode && 304 !== $statusCode);
        if (!$hasBody) {
            $response = $response->withoutHeader("Content-Type")
                                 ->withoutHeader("Content-Length");
        } else {
            $size = $response->getBody()->getSize();
            if (null !== $size) {
                $response = $response->withHeader("Content-Length", $size);
            }
        }

        if (!headers_sent()) {
            header(sprintf(
                "HTTP/%s %s %s",
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf("%s: %s", $name, $value), false);
                }
            }
        }

        if ($hasBody) {
            $body = $response->getBody();
            if ($body->isAttached()) {
                $body->rewind();
                while (!$body->eof()) {
                    echo $body->read(1024);
                }
            }
        }    
        
        return $response;
    }
}
