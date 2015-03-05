<?php
require dirname(__DIR__) . "/src/Memo/View.php";

use \Memo\View;

class ViewTest extends PHPUnit_Framework_TestCase
{
    protected $view;

    protected function setUp()
    {
        $this->view = new View();
        $this->view->addFolder("./data");
    }

    public function testConstruct()
    {
        $view = new View("index.template");
        $this->assertEquals("index.template", $view->template);
    }

    public function testSetTemplate()
    {
        $this->view->setTemplate("test");
        $this->assertEquals("test", $this->view->template);
    }

    public function testSetFolders()
    {
        $this->view->setFolders(array('./test'));
        $this->assertEquals(array("./test"), $this->view->folders);
    }

    public function testAddFolder()
    {
        $newFolder = "./template";
        $this->view->addFolder($newFolder);
        $this->assertEquals(array("./data", "./template"), $this->view->folders);
    }

    public function testLayout()
    {
        $this->view->layout("base");
        $this->view->layout("index");
        $this->assertEquals(array("base", "index"), $this->view->layouts);
    }

    public function testOpen()
    {
        $obLevel = ob_get_level();
        $this->view->open("content");
        $this->assertEquals(ob_get_level(), $obLevel + 1);
        $this->assertEquals(array("content"), $this->view->sectionStack);
        ob_end_clean();
    }

    /**
     * @expectedException \LogicException
     */
    public function testClose()
    {
        $this->view->open('content');
        ?>hello,world!<?php
        $this->view->close();
        $this->assertTrue(empty($this->view->sectionStack));
        $this->assertEquals("hello,world!", $this->view->sections['content']);

        $this->view->close();
    }

    public function testSection()
    {
        $this->assertEquals(null, $this->view->section("content"));
        $this->view->open('content');
        ?>hello<?php
        $this->view->close();
        $this->assertEquals("hello", $this->view->section("content"));
    }

    public function testAssign()
    {
        $this->view->assign("content", "hello,world!");
        $this->assertEquals("hello,world!", $this->view->shareVars['content']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetPath()
    {
        $this->assertEquals("./data/index.template.php", $this->view->getPath("index.template"));
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

    public function testDisplay()
    {
        $this->expectOutputString("<html><p>hello,Joe!</p><p>hello@example.com</p></html>\n");
        $this->view->setTemplate('contact.template');
        $this->view->assign("name", "Joe");
        $this->view->display();
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
     * @expectedException \BadFunctionCallException
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
        $this->assertEquals(array('./data'), $this->view->folders);
        $this->assertFalse($this->view->undefine);
    }
} 
