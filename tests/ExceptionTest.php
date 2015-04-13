<?php

require ROOT . "/src/Memo/Exception.php";


class ExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testSetResponse()
    {
        $response = new \Slim\Http\Response();
        $exception = (new \Memo\Exception())->setResponse($response);
        $this->assertAttributeInstanceOf("\\Slim\\Http\\Response", "response", $exception);
    }

    public function testGetResponse()
    {
        $response = new \Slim\Http\Response();
        $exception = (new \Memo\Exception())->setResponse($response);
        $this->assertSame($response, $exception->getResponse());
    }
}
