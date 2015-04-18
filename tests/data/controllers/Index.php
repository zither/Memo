<?php
namespace Memo\Controllers;

class Index 
{
    public function helloGet()
    {
        return "Hello,world!";
    }

    public function helloPost()
    {
        return "This is a POST request.";
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
