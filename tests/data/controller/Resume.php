<?php
namespace Memo {
    interface Controller 
    {
        public function beforeActionHook();
    }
}

namespace Memo\Controller {
    class Resume implements \Memo\Controller 
    {
        public function beforeActionHook(){}

        public function aboutGet()
        {
            return "Memo Controller";
        }
    }
}
