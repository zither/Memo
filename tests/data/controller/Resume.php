<?php
namespace Memo\Controller;

class Resume extends \Memo\Controller 
{
    public function beforeActionHook(){}

    public function aboutGet()
    {
        return "Memo Controller";
    }
}
