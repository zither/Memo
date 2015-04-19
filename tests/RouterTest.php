<?php

require ROOT . "/src/Memo/Router.php";
require ROOT . "/tests/data/controllers/Index.php";
require ROOT . "/tests/data/controllers/Resume.php";

use Memo\Router;
use Pimple\Container;
use Slim\Http\Environment;

class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp()
    {
        $this->router = new Router(new Container());
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

    public function testConstruct()
    {
        $this->assertInstanceof("\\Pimple\\Container", $this->router->container);
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
        $response = new \Slim\Http\Response();

        $response = $this->router->dispatch($request, $response);
        $this->assertInstanceof("\\Psr\\Http\\Message\\ResponseInterface", $response);

        $this->assertEquals("Hello,world!", (string)$response->getBody());
    }

    public function testDispatchWithArguments()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index/say/1/2/3", 
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();

        $response = $this->router->dispatch($request, $response);
        $this->assertInstanceOf("\\Psr\\Http\\Message\\ResponseInterface", $response);
        $this->assertEquals("123", (string)$response->getBody());
    }

    public function testDispatchPostRequestWithControllerAndAction()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index/hello/", 
            "REQUEST_METHOD" => "POST",
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();

        $response = $this->router->dispatch($request, $response);
        $this->assertInstanceof("\\Psr\\Http\\Message\\ResponseInterface", $response);

        $this->assertEquals("This is a POST request.", (string)$response->getBody());
    }

    public function testDispatchWithDefaultControllerAndAction()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "SCRIPT_NAME" => "/index.php"                        
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("Default Controller And Action", (string)$response->getBody());
    }

    public function testDispatchWithControllerOnly()
    {
        $this->router->setDefaultAction("about");
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("No Action", (string)$response->getBody());
    }

    public function testDispatchWithInvalidController()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/invalid/invalid", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDispatchWithInvalidAction()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/index/invalid", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDispatchWithControllerImplementsMemoController()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/resume/about/", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("Memo Controller", (string)$response->getBody());
    }

    public function testDispatchWithMetchedController()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/hi/Joe", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $this->router->addRoute("/hi/(\w+)", array("Index", "hi"));
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("Hi Joe", (string)$response->getBody());
    }

    public function testDispatchWithInvalidRegularExpression()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/test", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $this->router->addRoute("/testss", array("Index", "index"));
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDispatchWithMetchedInvalidController()
    {
        $mockEnv = (new \Slim\Http\Environment())->mock(array(
            "REQUEST_URI" => "/invalidController/Joe", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($mockEnv);
        $response = new \Slim\Http\Response();
        $this->router->addRoute("/test/haha", array("Test", "haha"));
        $this->router->addRoute("/hi/(\w+)", array("Index"));
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
