<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Pimple\ServiceProviderInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class App extends \Pimple\Container
{
    use \Slim\MiddlewareAware;

    protected $defaultSettings = array(
        "cookieLifetime" => "20 minutes",
        "cookiePath" => "/",
        "cookieDomain" => null,
        "cookieSecure" => false,
        "cookieHttpOnly" => false,
        "httpVersion" => "1.1"
    );

    public function __construct(array $userSettings = [])
    {
        parent::__construct();

        $this["settings"] = function ($c) use ($userSettings) {
            return array_merge($c->defaultSettings, $userSettings);
        };

        $this["environment"] = function ($c) {
            return new \Slim\Http\Environment($_SERVER);
        };

        $this["request"] = $this->factory(function ($c) {
            $env = $c["environment"];
            $method = $env["REQUEST_METHOD"];
            $uri = \Slim\Http\Uri::createFromEnvironment($env);
            $headers = \Slim\Http\Headers::createFromEnvironment($env);
            $cookies = new \Slim\Http\Collection(\Slim\Http\Cookies::parseHeader($headers->get("Cookie")));
            $serverParams = new \Slim\Http\Collection($env->all());
            $body = new \Slim\Http\Body(fopen("php://input", "r"));

            return new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        });

        $this["response"] = $this->factory(function ($c) {
            $headers = new \Slim\Http\Headers(["Content-Type" => "text/html"]);
            $response = new \Slim\Http\Response(200, $headers);
            $response->setCookieDefaults(array(
                "expires" => $c["settings"]["cookieLifetime"],
                "path" => $c["settings"]["cookiePath"],
                "domain" => $c["settings"]["cookieDomain"],
                "secure" => $c["settings"]["cookieSecure"],
                "httponly" => $c["settings"]["cookieHttpOnly"]
            ));

            return $response->withProtocolVersion($c["settings"]["httpVersion"]);
        });

        $this["router"] = function ($c) {
            return new Router();
        };

        $this["errorHandler"] = function ($c) {
            return new \Slim\Handlers\Error();
        };

        $this["notFoundHandler"] = function ($c) {
            return new \Slim\Handlers\NotFound();
        };
    }

    public function stop(ResponseInterface $response)
    {
        throw new \Slim\Exception($response);
    }

    public function halt($status, $message = "")
    {
        $response = $this["response"]->withStatus($status);
        $response->write($message);
        $this->stop($response);
    }

    public function redirect($url, $status = 302)
    {
        return $this["response"]->withStatus($status)->withHeader("Location", $url);
    }

    public function run()
    {
        static $responded = false;
        $request = $this["request"];
        $response = $this["response"];

        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (\Slim\Exception $e) {
            $response = $e->getResponse();
        } catch (\Exception $e) {
            $response = $this["errorHandler"]($request, $response, $e);
        }

        if (in_array($response->getStatusCode(), array(204, 304))) {
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

            if (!in_array($response->getStatusCode(), array(204, 304))) {
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

    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        return $this["router"]->dispatch($request, $response);
    }

    public function subRequest($method, $path, array $headers = [], array $cookies = [], $bodyContent = "") 
    {
        $env = $this["environment"];
        $uri = \Slim\Http\Uri::createFromEnvironment($env)->withPath($path);
        $headers = new \Slim\Http\Headers($headers);
        $cookies = new \Slim\Collection($cookies);
        $serverParams = new \Slim\Collection($env->all());
        $body = new \Slim\Http\Body(fopen("php://temp", "r+"));
        $body->write($bodyContent);
        $body->rewind();
        $request = new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        $response = $this["response"];
        return $this($request, $response);
    }
}
