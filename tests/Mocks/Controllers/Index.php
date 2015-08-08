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
        return $this->bindOutput($this->response, "Hello,world!");
    }

    public function helloPost()
    {
        return $this->bindOutput($this->response, "POST");
    }

    public function indexGet()
    {
        return $this->bindOutput($this->response, "Default Controller And Action");
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
        return $this->bindOutput($this->response, sprintf("Hi %s", $name));
    }

    public function fooGet()
    {
        return $this->bindOutput($this->response, $this->container["foo"]);
    }
}
