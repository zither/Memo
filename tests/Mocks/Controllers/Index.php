<?php
namespace Memo\Tests\Mocks\Controllers;

use Memo\Controller;

class Index extends Controller 
{
    public function beforeActionHook(array $routeInfo)
    {
        $this->container["foo"] = sprintf("action:%s", $routeInfo["action"]);
    }

    public function helloGet()
    {
        return $this->response->write("Hello,world!");
    }

    public function helloPost()
    {
        return $this->response->write("POST");
    }

    public function indexGet()
    {
        return $this->response->write("Default Controller And Action");
    }

    public function aboutGet()
    {
        return "string";
    }

    public function redirectGet()
    {
        $this->redirect("/index");
    }

    public function hiGet($name)
    {
        return $this->response->write("Hi $name");
    }

    public function fooGet()
    {
        return $this->response->write($this->container["foo"]);
    }
}
