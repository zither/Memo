<?php
require ROOT . "/src/Memo/View.php";

use \Memo\View;

class ViewTest extends PHPUnit_Framework_TestCase
{
    protected $view;

    protected function setUp()
    {
        $this->view = new View();
        $this->view->addFolder(__DIR__ . "/data");
    }

    public function testConstruct()
    {
        $helper = function () {
            return 1;
        };
        $settings = array(
            "template" => "index.template",
            "folders" => array(__DIR__ . "/data"),
            "helper" => $helper,
            "invalid" => "undefined"
        );
        $view = new View($settings);
        $this->assertInstanceOf("\\Pimple\\ServiceProviderInterface", $view);
        $this->assertEquals("index.template", $view->template);
        $this->assertEquals(array(__DIR__ . "/data"), $view->folders);
        $this->assertEquals($helper, $view->helper);
        $this->assertFalse($view->invalid);
    }

    public function testSetTemplate()
    {
        $this->view->setTemplate("test");
        $this->assertEquals("test", $this->view->template);
    }

    public function testSetFolders()
    {
        $this->view->setFolders(array("./test"));
        $this->assertEquals(array("./test"), $this->view->folders);
    }

    public function testAddFolder()
    {
        $newFolder = "./template/";
        $this->view->addFolder($newFolder);
        $this->assertEquals(array(__DIR__ . "/data", "./template"), $this->view->folders);
    }

    public function testLayout()
    {
        $this->view->layout("base");
        $this->view->layout("index");
        $this->assertEquals("base", $this->view->layoutQueue->dequeue());
        $this->assertEquals("index", $this->view->layoutQueue->dequeue());
        $this->assertTrue($this->view->layoutQueue->isEmpty());
    }

    public function testOpen()
    {
        $obLevel = ob_get_level();
        $this->view->open("content");
        $this->assertEquals(ob_get_level(), $obLevel + 1);
        $this->assertEquals("content", $this->view->sectionStack->pop());
        ob_end_clean();
    }

    /**
     * @expectedException \LogicException
     */
    public function testClose()
    {
        $this->view->open("content");
        ?>hello,world!<?php
        $this->view->close();
        $this->assertTrue(empty($this->view->sectionStack));
        $this->assertEquals("hello,world!", $this->view->sections['content']);

        $this->view->open("content");
        ?>Can't overwrite<?php
        $this->view->close();
        $this->assertEquals("hello,world!", $this->view->sections['content']);

        $this->view->close();
    }

    public function testSection()
    {
        $this->assertEquals(null, $this->view->section("content"));
        $this->assertEquals("hello", $this->view->section("invalidSection", "hello"));
        $this->view->open("content");
        ?>hello<?php
        $this->view->close();
        $this->assertEquals("hello", $this->view->section("content"));
    }

    public function testAssign()
    {
        $this->view->assign("content", "hello,world!");
        $this->assertEquals("hello,world!", $this->view->shareVars["content"]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetPath()
    {
        $this->assertEquals(__DIR__ . "/data/index.template.php", $this->view->getPath("index.template"));
        $this->view->getPath("undefine");
    }

    public function testRender()
    {
        $this->view->setTemplate("contact.template");
        $this->view->assign("name", "Joe");
        $this->assertEquals("<html><p>hello,Joe!</p><p>hello@example.com</p></html>", trim($this->view->render()));
    }

    /**
     * @expectedException \LogicException
     */
    public function testBadRender()
    {
        $this->view->setTemplate("bad.template");
        $this->view->render();
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testBadMethodCall()
    {
        $this->view->setTemplate("method.template");
        $this->view->render();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetHelper()
    {
        $helper = new stdclass();
        $this->view->setHelper($helper);
        $this->assertEquals($helper, $this->view->helper);
        $this->view->setHelper("badHelper");
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testCallHelperFunction()
    {
         $helper = function ($number) {
             return $number + 1;
         };
         $this->view->setHelper($helper);
         $this->assertEquals(2, $this->view->__invoke(1));
         $this->view->undefine();
    }

    public function testPropertyGetter()
    {
        $this->assertEquals(array(__DIR__ . "/data"), $this->view->folders);
        $this->assertFalse($this->view->undefine);
    }
} 
