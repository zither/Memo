<?php
namespace Memo\Controllers;

class Foo extends \Memo\Controller
{
    public function beforeActionHook()
    {
        $this->container["foo"] = "BAR";
    }

    public function indexGet()
    {
        return $this->container["foo"];
    }
}
