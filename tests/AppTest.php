<?php
require ROOT . "/tests/data/controllers/Index.php";
require ROOT . "/tests/data/controllers/Foo.php";

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
        $app->addRoute("test", array("Test", "test"));
        $this->assertEquals([["test" => ["Test", "test"]]], $app["router"]->routes);
    }

    public function testRunWithDefaultControllerAndAction()
    {
        $this->expectOutputString("Default Controller And Action");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock(array(
                "SCRIPT_NAME" => "/index.php"                        
            )); 
        }; 
        $app->run();
    }

    public function testRunWithParams()
    {
        $this->expectOutputString("Hi Joe");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock(array(
                "REQUEST_URI" => "/index/hi/Joe", 
                "SCRIPT_NAME" => "/index.php"                        
            )); 
        }; 
        $app->run();
    }

    public function testRunWithInvalidController()
    {
        $this->expectOutputString("Controller does not exist: \Memo\Controllers\Invalid");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock(array(
                "REQUEST_URI" => "/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            )); 
        }; 
        $response = $app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithInvalidAction()
    {
        $this->expectOutputString("Call to undefined method \Memo\Controllers\Index::invalidGet");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock(array(
                "REQUEST_URI" => "/index/invalid", 
                "SCRIPT_NAME" => "/index.php"                        
            )); 
        }; 
        $response = $app->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRunWithSubclassOfMemoController()
    {
        $this->expectOutputString("BAR");
        $app = new App();
        $app["environment"] = function () {
            return (new \Slim\Http\Environment())->mock(array(
                "REQUEST_URI" => "/Foo", 
                "SCRIPT_NAME" => "/index.php"                        
            )); 
        }; 
        $app["foo"] = "FOO";
        $app->run();
    }
}
