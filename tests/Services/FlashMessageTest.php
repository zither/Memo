<?php
namespace Memo\Tests\Services;

use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_Assert;
use Memo\Services\FlashMessage;

class ObjectMessage {
    public function __toString()
    {
        return "FOO";
    }
}

class FlashMessageTest extends PHPUnit_Framework_TestCase
{
    protected $flash;

    protected function setUp()
    {
        $this->flash = new FlashMessage();
        $this->storageKey = PHPUnit_Framework_Assert::readAttribute(
            $this->flash, 
            "storageKey"
        );
    }

    public function testSetMessage()
    {
        $this->assertEmpty($_SESSION[$this->storageKey]["forNext"]);
        $this->flash->set("foo", "FOO");
        $this->assertEquals(["foo" => "FOO"], $_SESSION[$this->storageKey]["forNext"]);
    }

    public function testSetInvalidMessage()
    {
        $this->flash->set("foo", []);
        $this->assertFalse(isset($_SESSION[$this->storageKey]["forNext"]["foo"]));
    }

    public function testSetObjectMessage()
    {
        $this->flash->set("foo", new ObjectMessage());
        $this->assertEquals("FOO", (string)$_SESSION[$this->storageKey]["forNext"]["foo"]);
    }

    public function testGetDefaultMessageValue()
    {
        $this->assertNull($this->flash->get("bar"));
    }

    public function testGetMessage()
    {
        $_SESSION[$this->storageKey]["fromPrevious"]["foo"] = "FOO";
        $this->assertEquals("FOO", $this->flash->get("foo"));
    }

    public function testHasMessage()
    {
        $this->assertFalse($this->flash->has("bar"));
        $_SESSION[$this->storageKey]["fromPrevious"]["bar"] = "BAR";
        $this->assertTrue($this->flash->has("bar"));
    }
} 
