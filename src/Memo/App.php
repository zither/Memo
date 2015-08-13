<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Exception;
use RuntimeException;
use BadMethodCallException;
use Memo\Exception as MemoException;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Pimple\Container;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;

class App extends Container
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
            return new Environment($_SERVER);
        };

        $this["request"] = $this->factory(function ($c) {
            return Request::createFromEnvironment($c["environment"]);
        });

        $this["response"] = $this->factory(function ($c) {
            $headers = new Headers(["Content-Type" => "text/html"]);
            $response = new Response(200, $headers);

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
     * @return ResponseInterface
     */
    public function run()
    {
        try {
            $routeInfo = $this["router"]->dispatch($this["request"]);
            $callable = $this->resolveCallable($routeInfo);
            $response = call_user_func_array($callable, $routeInfo["params"]);
            if (!$response instanceof ResponseInterface) {
                throw new RuntimeException(
                    "Controller must return instance of \Psr\Http\Message\ResponseInterface"
                );
            }
        } catch (MemoException $e) {
            $response = $e->getResponse();
        } catch (Exception $e) {
            if ($this["settings"]["debug"]) {
                $content = $e->getMessage();
            } else {
                $content = "Not Found";
            }

            $response = $this["response"]->withStatus(404);
            $response->getBody()->write($content);
        }

        return $this->sendResponse($response);
    }

    /**
     * Resolve callable
     *
     * @param array $routeInfo
     * @return array
     * @throws RuntimeException if controller does not exist
     * @throws BadMethodCallException if action is undefined
     */
    protected function resolveCallable(array $routeInfo)
    {
        $controllerName = $routeInfo["controller"];
        if (!class_exists($controllerName)) {
            throw new RuntimeException(
                sprintf("Controller does not exist: %s", $controllerName)
            );
        }

        $controller = new $controllerName($this["request"], $this["response"]);
        if ($controller instanceof Controller) {
            $controller->setContainer($this); 
        } 
        if (method_exists($controller, "beforeActionHook")) {
            $controller->beforeActionHook($routeInfo);
        }

        if (!method_exists($controller, $routeInfo["action"])) {
            throw new BadMethodCallException(
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
     * @return ResponseInterface
     */
    protected function sendResponse(ResponseInterface $response)
    {
        $response = $this->finalize($response);

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

        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            while (!$body->eof()) {
                echo $body->read(1024);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }    
        
        return $response;
    }

    /**
     * Finalize response
     *
     * @link https://github.com/slimphp/Slim/blob/3.x/Slim/App.php#L468
     */
    protected function finalize(ResponseInterface $response) 
    {
        if ($this->isEmptyResponse($response)) {
            return $response->withoutHeader("Content-Type")->withoutHeader("Content-Length");
        }

        $size = $response->getBody()->getSize();
        if (!is_null($size)) {
            $response = $response->withHeader("Content-Length", $size);
        }

        return $response;
    }

    /**
     * Helper method, which returns true if the provided response must not
     * output a body and false if the response could have a body 
     *
     * @link https://github.com/slimphp/Slim/blob/3.x/Slim/App.php#L491
     */
    protected function isEmptyResponse(ResponseInterface $response)
    {
        return in_array($response->getStatusCode(), [204, 205, 304]);
    }
}
