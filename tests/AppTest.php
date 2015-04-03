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

    /**
     * @expectedException \Slim\Exception
     */
    public function testStop()
    {
        $app = new App();
        $app->stop($app["response"]);
    }

    public function testHalt()
    {
        try {
            $app = new App();
            $app->halt(404, "App Halt");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Slim\\Exception", $e);
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $this->assertEquals("App Halt", (string)$e->getResponse()->getBody());
        }
    }

    public function testRedirect()
    {
        $app = new App();
        $response = $app->redirect("/index");
        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals("/index", $response->getHeader("Location"));
    }
}
