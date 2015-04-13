<?php

require ROOT . "/src/Memo/Autoloader.php";

use \Memo\Autoloader;

class AutoloaderTest extends PHPUnit_Framework_TestCase 
{
    public function testAddNamespace()
    {
        Autoloader::addNamespace("Memo\\", ROOT . "/src/Memo"); 
        Autoloader::addNamespace(
            "Memo\\Tests", 
            ROOT . "/tests/data/" 
        ); 
        $this->assertEquals(
            array(
                "Memo" => array(ROOT . "/src/Memo/"),
                "Memo\\Tests" => array(ROOT . "/tests/data/")
            ),
            Autoloader::$prefixes
        );
    }
    
    /**
     * @depends testAddNamespace
     */    
    public function testValidAutoload()
    {
        Autoloader::register();
        $this->assertTrue(class_exists("\\Memo\\Tests\\HelloClass"));
    }

    /**
     * @depends testAddNamespace
     */    
    public function testInvalidAutoload()
    {
        Autoloader::addNamespace("Invalid", "/invalid/path/");
        $this->assertFalse(class_exists("\\Invalid\\Controller\\Index"));
    }
}
