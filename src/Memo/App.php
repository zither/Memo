<?php 
namespace Memo;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Pimple\ServiceProviderInterface;

class App extends \Pimple\Container
{
    use \Slim\MiddlewareAware;

    public function __construct(array $userSettings = [])
    {
        parent::__construct();

        $this["settings"] = function ($c) use ($userSettings) {
            $config = new \Slim\Configuration(new \Slim\ConfigurationHandler);
            $config->setArray($userSettings);
            return $config;
        };

        $this["environment"] = function ($c) {
            return new \Slim\Http\Environment($_SERVER);
        };

        $this["request"] = $this->factory(function ($c) {
            $env = $c["environment"];
            $method = $env["REQUEST_METHOD"];
            $uri = \Slim\Http\Uri::createFromEnvironment($env);
            $headers = \Slim\Http\Headers::createFromEnvironment($env);
            $cookies = new \Slim\Collection(\Slim\Http\Cookies::parseHeader($headers->get("Cookie")));
            $serverParams = new \Slim\Collection($env->all());
            $body = new \Slim\Http\Body(fopen("php://input", "r"));
            return new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);
        });

        $this["response"] = $this->factory(function ($c) {
            $headers = new \Slim\Http\Headers(["Content-Type" => "text/html"]);
            $cookies = new \Slim\Http\Cookies([], [
                "expires" => $c["settings"]["cookies.lifetime"],
                "path" => $c["settings"]["cookies.path"],
                "domain" => $c["settings"]["cookies.domain"],
                "secure" => $c["settings"]["cookies.secure"],
                "httponly" => $c["settings"]["cookies.httponly"],
            ]);
            $response = new \Slim\Http\Response(200, $headers, $cookies);
            return $response->withProtocolVersion($c["settings"]["http.version"]);
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

        $this["notAllowedHandler"] = function ($c) {
            return new \Slim\Handlers\NotAllowed();
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

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!($errno & error_reporting())) {
                return ;
            }
            throw new \ErrorException($errstr, $errno, 1, $errfile, $errline);
        });

        $request = $this["request"];
        $response = $this["response"];

        try {
            $response = $this->callMiddlewareStack($request, $response);
        } catch (\Slim\Exception $e) {
            $response = $e->getResponse();
        } catch (\Exception $e) {
            $response = $this["errorHandler"]($request, $response, $e);
        }

        if (!$responded) {
            $responded = true;
            $response = $response->finalize();
            $response->sendHeaders();
            if (!$request->isHead()) {
                $response->sendBody();
            }
        }

        restore_error_handler();

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
