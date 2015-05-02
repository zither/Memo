<?php
require ROOT . "/tests/data/controllers/Index.php";

use Memo\App;

class AppTest extends PHPUnit_Framework_TestCase 
{
    public function testConstructor()
    {
        $app = new App();
        $this->assertInstanceOf("\\Memo\\App", $app);
        $this->assertInstanceOf("\\Slim\\Http\\Environment", $app["environment"]);
        $this->assertInstanceOf("\\Slim\\Http\\Request", $app["request"]);
        $this->assertInstanceOf("\\Slim\\Http\\Response", $app["response"]);
        $this->assertInstanceOf("\\Memo\\Router", $app["router"]);
    }

    public function testAddRoute()
    {
        $app = new App();
        $app->addRoute("test", ["Test", "test"]);
        $this->assertEquals([["test" => ["Test", "test"]]], $app["router"]->routes);
    }

    public function testRunWithDefaultControllerAndAction()
    {
        $this->expectOutputString("Default Controller And Action");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $app->run();
    }

    public function testRunWithParams()
    {
        $this->expectOutputString("Hi Joe");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/index/hi/Joe", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $app->run();
    }

    public function testRunWithInvalidController()
    {
        $this->expectOutputString("Controller does not exist: \App\Controllers\Invalid");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithInvalidAction()
    {
        $this->expectOutputString("Call to undefined method \App\Controllers\Index::invalidGet");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/index/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithBeforeActionHook()
    {
        $this->expectOutputString("BAR");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/Foo", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $app["foo"] = "FOO";
        $app->addRoute("/Foo", ["Index", "foo"]);
        $app->run();
    }

    public function testRunWithoutDebug()
    {
        $this->expectOutputString("Not Found");
        $app = new App(["debug" => false]);
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
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
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/index/about", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $app->run();
        $this->assertEquals(404, $response->getStatusCode());    
    }

    public function testRunWithPostRequest()
    {
        $this->expectOutputString("POST");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/index/hello", 
                "REQUEST_METHOD" => "POST",
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $app->run();
    }

    public function testRunWithMemoException()
    {
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock([
                "REQUEST_URI" => "/index/redirect", 
                "SCRIPT_NAME" => "/index.php"                        
            ]); 
        }; 
        $response = $app->run();  
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertArrayHasKey("Location", $response->getHeaders());
        $this->assertEquals("/index", $response->getHeaderLine("Location"));
    }
}
