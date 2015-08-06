<?php
namespace Memo\Tests;

use PHPUnit_Framework_TestCase;
use Memo\Router;
use Pimple\Container;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Cookies;
use Slim\Http\Body;
use Slim\Http\Request;

class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $router;
    protected $controllerNamespace = "Memo\\Tests\\Mocks\\Controllers\\";

    protected function setUp()
    {
        $container = new Container();
        $this->router = (new Router())->setContainer($container);
        $this->router->setControllerNamespace($this->controllerNamespace);
    }

    protected function createRequest($env) 
    {
        $method = $env["REQUEST_METHOD"];
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = Cookies::parseHeader($headers->get("Cookie", []));
        $serverParams = $env->all();
        $body = new Body(fopen("php://input", "r"));
        return new Request($method, $uri, $headers, $cookies, $serverParams, $body);    
    }

    public function testAddRoute()
    {
        $route = [
            ["/about" => function(){}],
            ["/test" => ["Index", "about"]]
        ];
        $this->router->addRoute("/about", function(){});
        $this->router->addRoute("/test", ["Index", "about"]);
        $this->assertAttributeEquals($route, "routes", $this->router);
    }

    public function testSetAndGetController()
    {
        $this->router->setDefaultController("Test");
        $this->assertEquals("Test", $this->router->getController());
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetInvalidController()
    {
        $this->router->setDefaultController(["Invalid"]);
    }

    public function testSetAndGetAction()
    {
        $this->router->setDefaultAction("about");
        $this->assertEquals("aboutGet", $this->router->getAction());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetInvalidAction()
    {
        $this->router->setDefaultAction(["invalid"]);
    }

    public function testDispatchWithControllerAndAction()
    {
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/index/hello/", 
            "SCRIPT_NAME" => "/index.php"                        
        ]);
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Index", 
            $routeInfo["controller"]
        );
        $this->assertEquals("helloGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithArguments()
    {
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/index/say/1/2/3", 
            "SCRIPT_NAME" => "/index.php"                        
        ]);
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Index", 
            $routeInfo["controller"]
        );
        $this->assertEquals("sayGet", $routeInfo["action"]);
        $this->assertEquals([1, 2, 3], $routeInfo["params"]);
    }

    public function testDispatchPostRequestWithControllerAndAction()
    {
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/index/hello/", 
            "REQUEST_METHOD" => "POST",
            "SCRIPT_NAME" => "/index.php"                        
        ]);
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Index", 
            $routeInfo["controller"]
        );
        $this->assertEquals("helloPost", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithDefaultControllerAndAction()
    {
        $mockEnv = (new Environment())->mock([
            "SCRIPT_NAME" => "/index.php"                        
        ]);
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Index", 
            $routeInfo["controller"]
        );
        $this->assertEquals("indexGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithControllerOnly()
    {
        $this->router->setDefaultAction("about");
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/index", 
            "SCRIPT_NAME" => "index.php"
        ]);
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Index", 
            $routeInfo["controller"]
        );
        $this->assertEquals("aboutGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithInvalidController()
    {
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/invalid/invalid", 
            "SCRIPT_NAME" => "index.php"
        ]);
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Invalid", 
            $routeInfo["controller"]
        );
        $this->assertEquals("invalidGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithRegularExpression()
    {
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/hi/Joe", 
            "SCRIPT_NAME" => "index.php"
        ]);
        $request = $this->createRequest($mockEnv);

        $this->router->addRoute("/hi/(\w+)", ["Index", "hi"]);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Index", 
            $routeInfo["controller"]
        );
        $this->assertEquals("hiGet", $routeInfo["action"]);
        $this->assertEquals(["Joe"], $routeInfo["params"]);
    }

    public function testDispatchWithInvalidRegularExpression()
    {
        $mockEnv = (new Environment())->mock([
            "REQUEST_URI" => "/test", 
            "SCRIPT_NAME" => "index.php"
        ]);
        $request = $this->createRequest($mockEnv);
        $this->router->addRoute("/testss", ["Index", "index"]);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals(
            $this->controllerNamespace . "Test", 
            $routeInfo["controller"]
        );
        $this->assertEquals("indexGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }
}
