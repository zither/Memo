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
}
