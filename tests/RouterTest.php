<?php

use Memo\Router;
use Pimple\Container;
use Slim\Http\Environment;

class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp()
    {
        $container = new Container();
        $this->router = (new Router())->setContainer($container);
    }

    protected function createRequest($env) 
    {
        $method = $env["REQUEST_METHOD"];
        $uri = \Slim\Http\Uri::createFromEnvironment($env);
        $headers = \Slim\Http\Headers::createFromEnvironment($env);
        $cookies = \Slim\Http\Cookies::parseHeader($headers->get("Cookie", array()));
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen("php://input", "r"));
        return new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);    
    }

    public function testAddRoute()
    {
        $route = array(
            array("/about" => function(){}),
            array("/test" => array("Index", "about"))
        );
        $this->router->addRoute("/about", function(){});
        $this->router->addRoute("/test", array("Index", "about"));
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
        $this->router->setDefaultController(array("Invalid"));
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
        $this->router->setDefaultAction(array("invalid"));
    }

    public function testDispatchWithControllerAndAction()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index/hello/", 
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Index", $routeInfo["controller"]);
        $this->assertEquals("helloGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithArguments()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index/say/1/2/3", 
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Index", $routeInfo["controller"]);
        $this->assertEquals("sayGet", $routeInfo["action"]);
        $this->assertEquals([1, 2, 3], $routeInfo["params"]);
    }

    public function testDispatchPostRequestWithControllerAndAction()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index/hello/", 
            "REQUEST_METHOD" => "POST",
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Index", $routeInfo["controller"]);
        $this->assertEquals("helloPost", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithDefaultControllerAndAction()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Index", $routeInfo["controller"]);
        $this->assertEquals("indexGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithControllerOnly()
    {
        $this->router->setDefaultAction("about");
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Index", $routeInfo["controller"]);
        $this->assertEquals("aboutGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithInvalidController()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/invalid/invalid", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Invalid", $routeInfo["controller"]);
        $this->assertEquals("invalidGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }

    public function testDispatchWithRegularExpression()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/hi/Joe", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);

        $this->router->addRoute("/hi/(\w+)", array("Index", "hi"));

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Index", $routeInfo["controller"]);
        $this->assertEquals("hiGet", $routeInfo["action"]);
        $this->assertEquals(["Joe"], $routeInfo["params"]);
    }

    public function testDispatchWithInvalidRegularExpression()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/test", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $this->router->addRoute("/testss", array("Index", "index"));

        $routeInfo = $this->router->dispatch($request);
        $this->assertEquals("Test", $routeInfo["controller"]);
        $this->assertEquals("indexGet", $routeInfo["action"]);
        $this->assertEquals([], $routeInfo["params"]);
    }
}
