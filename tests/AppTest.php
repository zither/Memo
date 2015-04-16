<?php
require ROOT . "/src/Memo/App.php";

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
}
