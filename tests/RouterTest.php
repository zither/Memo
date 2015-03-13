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
        $this->router = new Router();
    }

    public function testConstruct()
    {
        $this->assertAttributeEquals($_SERVER, "environment", $this->router);
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
        $this->assertEquals("about", $this->router->getAction());
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
        $this->router->mock(array("PATH_INFO" => "/index/hello/"));
        $result = $this->router->dispatch();
        $this->assertEquals("Hello,world!", $result);
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithDefaultControllerAndAction()
    {
        $result = $this->router->dispatch();
        $this->assertEquals("Default Controller And Action", $result);
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithControllerOnly()
    {
        $this->router->setDefaultAction("about");
        $this->router->mock(array("PATH_INFO" => "/index"));
        $result = $this->router->dispatch();
        $this->assertEquals("No Action", $result);
    }

    /**
     * @depends testMock
     * @expectedException RuntimeException
     */
    public function testDispatchWithInvalidController()
    {
        $this->router->mock(array("PATH_INFO" => "/invalid/invalid"));
        $result = $this->router->dispatch();
    }

    /**
     * @depends testMock
     * @expectedException BadMethodCallException
     */
    public function testDispatchWithInvalidAction()
    {
        $this->router->mock(array("PATH_INFO" => "/index/invalid"));
        $result = $this->router->dispatch();
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithControllerImplementsMemoController()
    {
        $this->router->mock(array("PATH_INFO" => "/resume/about/"));
        $result = $this->router->dispatch();
        $this->assertEquals("Memo Controller", $result);
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithMetchedController()
    {
        $this->router->mock(array("PATH_INFO" => "/hi/Joe"));
        $this->router->addRoute("/hi/(\w+)", array("Index", "hi"));
        $result = $this->router->dispatch();
        $this->assertEquals("Hi Joe", $result);
    }

    /**
     * @depends testMock
     */
    public function testDispatchWithMetchedClosure()
    {
        $this->router->mock(array("PATH_INFO" => "/test/closure"));
        $this->router->addRoute("/test/(\w+)", function ($word) {
            return "Test $word";            
        });
        $result = $this->router->dispatch();
        $this->assertEquals("Test closure", $result);
    }

    /**
     * @depends testMock
     * @expectedException RuntimeException
     */
    public function testDispatchWithMetchedInvalidController()
    {
        $this->router->mock(array("PATH_INFO" => "/hi/Joe"));
        $this->router->addRoute("/test/haha", array("Test", "haha"));
        $this->router->addRoute("/hi/(\w+)", array("Index"));
        $result = $this->router->dispatch();
    }
}
