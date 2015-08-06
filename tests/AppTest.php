<?php
namespace Memo\Tests;

use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Memo\App;
use Memo\Tests\Mocks\Controllers\Index;

class AppTest extends PHPUnit_Framework_TestCase 
{
    protected $app;
    protected $controllerNamespace = "Memo\\Tests\\Mocks\\Controllers\\";

    public function setUp()
    {
        $this->app = new App();
        $this->app["router"]->setControllerNamespace($this->controllerNamespace);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf("\\Memo\\App", $this->app);
        $this->assertInstanceOf("\\Slim\\Http\\Environment", $this->app["environment"]);
        $this->assertInstanceOf("\\Slim\\Http\\Request", $this->app["request"]);
        $this->assertInstanceOf("\\Slim\\Http\\Response", $this->app["response"]);
        $this->assertInstanceOf("\\Memo\\Router", $this->app["router"]);
    }

    public function testAddRoute()
    {
        $this->app->addRoute("test", ["Test", "test"]);
        $this->assertEquals(
            [["test" => ["Test", "test"]]], 
            $this->app["router"]->routes
        );
    }

    public function testRunWithDefaultControllerAndAction()
    {
        $this->expectOutputString("Default Controller And Action");
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $this->app->run();
    }

    public function testRunWithParams()
    {
        $this->expectOutputString("Hi Joe");
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/index/hi/Joe", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $this->app->run();
    }

    public function testRunWithInvalidController()
    {
        $this->expectOutputString(sprintf(
            "Controller does not exist: %sInvalid",
            $this->controllerNamespace
        ));
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $this->app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithInvalidAction()
    {
        $this->expectOutputString(sprintf(
            "Call to undefined method %sIndex::invalidGet",
            $this->controllerNamespace
        ));
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/index/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $this->app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithBeforeActionHook()
    {
        $this->expectOutputString("action:fooGet");
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/Foo", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $this->app["foo"] = "FOO";
        $this->app->addRoute("/Foo", ["Index", "foo"]);
        $this->app->run();
    }

    public function testRunWithoutDebug()
    {
        $this->expectOutputString("Not Found");
        $app = new App(["debug" => false]);
        $app["router"]->setControllerNamespace($this->controllerNamespace);
        $app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/index/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithInvalidResponse()
    {
        $this->expectOutputString("Controller must return instance of \Psr\Http\Message\ResponseInterface");
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/index/about", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $this->app->run();
        $this->assertEquals(404, $response->getStatusCode());    
    }

    public function testRunWithPostRequest()
    {
        $this->expectOutputString("POST");
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/index/hello", 
                "REQUEST_METHOD" => "POST",
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $this->app->run();
    }

    public function testRunWithMemoException()
    {
        $this->app["environment"] = function () {
            return (new Environment())->mock([
                "REQUEST_URI" => "/index/redirect", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $this->app->run();  
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertArrayHasKey("Location", $response->getHeaders());
        $this->assertEquals("/index", $response->getHeaderLine("Location"));
    }
}
