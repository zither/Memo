<?php
namespace Memo\Controller;

class Index 
{
    public function helloGet()
    {
        return "Hello,world!";
    }

    public function indexGet()
    {
        return "Default Controller And Action";
    }

    public function aboutGet()
    {
        return "No Action"; 
    }

    public function hiGet($name)
    {
        return "Hi $name";
    }
}
