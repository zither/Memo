<?php

use Pimple\Container;

class Bag
{
    use \Memo\ContainerAware;
} 

class ContainerAwareTest extends PHPUnit_Framework_TestCase
{
    public function testSetContainer()
    {
        $container = new Container();
        $bag = new Bag();
        $bag->setContainer($container);
        $this->assertAttributeSame($container, "container", $bag);
    }

    public function testMagicGet()
    {

        $bag = new Bag();
        $this->assertNull($bag->foo);

        $container = new Container();
        $container["foo"] = "FOO";
        $bag->setContainer($container);
        $this->assertEquals("FOO", $bag->foo);
    }
}
