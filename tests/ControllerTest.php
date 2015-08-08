<?php
namespace Memo\Tests;

use PHPUnit_Framework_TestCase;
use Exception;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Cookies;
use Slim\Http\Body;
use Slim\Http\Collection;
use Slim\Http\Request;
use Slim\Http\Response;
use Pimple\Container;
use Memo\Controller;
use Memo\Tests\Mocks\ObjectOutput;

class ControllerTest extends PHPUnit_Framework_TestCase 
{
    protected $request;
    protected $response;

    protected function setUp()
    {
        $this->request = $this->createRequest();
        $this->response = new Response();
    }

    protected function createRequest()
    {
        $env = (new Environment())->mock([
            "PATH_INFO" => "/index/hello/", 
            "SCRIPT_NAME" => "/index.php"            
        ]);
        $method = $env["REQUEST_METHOD"];
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = Cookies::parseHeader($headers->get("Cookie", []));
        $serverParams = $env->all();
        $body = new Body(fopen("php://input", "r"));
        return new Request($method, $uri, $headers, $cookies, $serverParams, $body);    
    }

    public function testConstruct()
    {
        $controller = new Controller($this->request, $this->response);
        $this->assertInstanceOf("\\Memo\\Controller", $controller);
        $this->assertAttributeEquals($this->request, "request", $controller);
        $this->assertAttributeEquals($this->response, "response", $controller);
    }

    public function testBindOutput()
    {
        $controller = new Controller($this->request, $this->response);
        $response = $controller->bindOutput("String Output");
        $this->assertEquals("String Output", (string)$response->getBody());
    }

    public function testBindOutputWithObjectOutput()
    {
        $objectOutput = new ObjectOutput();
        $controller = new Controller($this->request, $this->response);
        $response = $controller->bindOutput($objectOutput);
        $this->assertEquals("Object Output", (string)$response->getBody());    
    }

    public function testBindOutputWithNewResponse()
    {
        $controller = new Controller($this->request, $this->response);
        $newResponse = $this->response->withStatus(404);
        $response = $controller->bindOutput("New Response", $newResponse);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals("New Response", (string)$response->getBody());
    }

    public function testBindOutputWithInvalidOutput()
    {
        try {
            $controller = new Controller($this->request, $this->response);
            $controller->bindOutput([]);
        } catch (Exception $e) {
            $this->assertInstanceOf("InvalidArgumentException", $e);
            $this->assertEquals("Output must be a string", $e->getMessage());
        }
    }

    public function testBindOutputWithInvalidResponse()
    {
        try {
            $controller = new Controller($this->request, $this->response);
            $controller->bindOutput("Invalid Response", []);
        } catch (Exception $e) {
            $this->assertInstanceOf("InvalidArgumentException", $e);
            $this->assertEquals("Expected a ResponseInterface", $e->getMessage());
        }
    }

    /**
     * @expectedException \Memo\Exception
     */
    public function testStop()
    {
        $controller = new Controller($this->request, $this->response);
        $controller->stop($this->response);
    }

    public function testHalt()
    {
        try {
            $controller = new Controller($this->request, $this->response);
            $controller->halt(404, "Controller Halt");
        } catch (Exception $e) {
            $this->assertInstanceOf("\\Memo\\Exception", $e);
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $this->assertEquals("Controller Halt", (string)$e->getResponse()->getBody());
        }
    }

    public function testRedirect()
    {
        try {
            $controller = new Controller($this->request, $this->response);
            $response = $controller->redirect("/index");
        } catch (Exception $e) {
            $this->assertInstanceOf("\\Memo\\Exception", $e);
            $response = $e->getResponse();
            $this->assertTrue($response->isRedirect());
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals("/index", $response->getHeaderLine("Location"));
        }
    }

    public function testSetContainer()
    {
        $container = new Container();
        $controller = new Controller($this->request, $this->response);
        $controller->setContainer($container);
        $this->assertAttributeEquals($container, "container", $controller);
    }

    public function testMagicGetWithValidValue()
    {
        $container = new Container();
        $container["request"] = $this->request;
        $controller = new Controller($this->request, $this->response);
        $controller->setContainer($container);
        $this->assertSame($this->request, $controller->request);    
    }

    public function testMagicGetWithInvalidValue()
    {
        $container = new Container();
        $controller = new Controller($this->request, $this->response);
        $controller->setContainer($container);
        $this->assertNull($controller->environment);
    }
}
