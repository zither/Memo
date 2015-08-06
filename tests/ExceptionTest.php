<?php
namespace Memo\Tests;

use PHPUnit_Framework_TestCase;
use Slim\Http\Response;
use Memo\Exception as MemoException;

class ExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testSetResponse()
    {
        $response = new Response();
        $exception = (new MemoException())->setResponse($response);
        $this->assertAttributeInstanceOf("\\Slim\\Http\\Response", "response", $exception);
    }

    public function testGetResponse()
    {
        $response = new Response();
        $exception = (new MemoException())->setResponse($response);
        $this->assertSame($response, $exception->getResponse());
    }
}
