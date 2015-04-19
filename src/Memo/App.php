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
        "httpVersion" => "1.1"
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
            return new Router($c);
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

    public function run()
    {
        static $responded = false;
        $response = $this["router"]->dispatch($this["request"], $this["response"]);

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

        if (!$responded) {
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

            $responded = true;
        }

        return $response;
    }
}
