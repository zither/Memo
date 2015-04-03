<?php

require ROOT . "/src/Memo/Router.php";
require ROOT . "/tests/data/controller/Index.php";
require ROOT . "/tests/data/controller/Resume.php";

use Memo\Router;

class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $router;

    protected function setUp()
    {
        $this->router = new Router(new \Slim\Http\Environment());
    }

    public function testConstruct()
    {
        $this->assertInstanceof("\Slim\Http\Environment", $this->router->environment);
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

    public function testMock()
    {
        $this->router->mock(array("PATH_INFO" => "/index/about/"));
        $env = PHPUnit_Framework_Assert::readAttribute(
            $this->router, 
            "environment"
        );
        $this->assertEquals("/index/about/", $env["PATH_INFO"]);
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithControllerAndAction()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/index/hello/", 
            "SCRIPT_NAME" => "/index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();

        $response = $this->router->dispatch($request, $response);
        $this->assertInstanceof("\Psr\Http\Message\ResponseInterface", $response);

        $this->assertEquals("Hello,world!", (string)$response->getBody());
    }

    protected function createRequest($env) 
    {
        $method = $env["REQUEST_METHOD"];
        $uri = \Slim\Http\Uri::createFromEnvironment($env);
        $headers = \Slim\Http\Headers::createFromEnvironment($env);
        $cookies = new \Slim\Http\Collection(\Slim\Http\Cookies::parseHeader($headers->get("Cookie")));
        $serverParams = new \Slim\Http\Collection($env->all());
        $body = new \Slim\Http\Body(fopen("php://input", "r"));
        return new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $body);    
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithDefaultControllerAndAction()
    {
        $this->router->mock(array("SCRIPT_NAME" => "index.php"));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("Default Controller And Action", (string)$response->getBody());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithControllerOnly()
    {
        $this->router->setDefaultAction("about");
        $this->router->mock(array(
            "PATH_INFO" => "/index", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("No Action", (string)$response->getBody());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithInvalidController()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/invalid/invalid", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithInvalidAction()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/index/invalid", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithControllerImplementsMemoController()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/resume/about/", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("Memo Controller", (string)$response->getBody());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithMetchedController()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/hi/Joe", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $this->router->addRoute("/hi/(\w+)", array("Index", "hi"));
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals("Hi Joe", (string)$response->getBody());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithInvalidRegularExpression()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/test", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $this->router->addRoute("/testss", array("Index", "index"));
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithMetchedInvalidController()
    {
        $this->router->mock(array(
            "PATH_INFO" => "/hi/Joe", 
            "SCRIPT_NAME" => "index.php"
        ));
        $request = $this->createRequest($this->router->environment);
        $response = new \Slim\Http\Response();
        $this->router->addRoute("/test/haha", array("Test", "haha"));
        $this->router->addRoute("/hi/(\w+)", array("Index"));
        $response = $this->router->dispatch($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
