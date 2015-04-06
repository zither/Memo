<?php
require ROOT . "/src/Memo/App.php";

use Memo\App;

class AppTest extends PHPUnit_Framework_TestCase 
{
    public function testConstructor()
    {
        $app = new App();
        $this->assertInstanceof("\\Memo\\App", $app);
    }

    public function testAddRoute()
    {
        $app = new App();
        $app->addRoute("test", array("Test", "test"));
        $this->assertEquals([["test" => ["Test", "test"]]], $app["router"]->routes);
    }
}
