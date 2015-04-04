<?php
require ROOT . "/src/Memo/Controller.php";

use Memo\Controller;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Cookies;
use Slim\Http\Body;
use Slim\Http\Collection;
use Slim\Http\Request;
use Slim\Http\Response;


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
        $env = (new Environment())->mock(array(
            "PATH_INFO" => "/index/hello/", 
            "SCRIPT_NAME" => "/index.php"            
        ));
        $method = $env["REQUEST_METHOD"];
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = new Collection(Cookies::parseHeader($headers->get("Cookie")));
        $serverParams = new Collection($env->all());
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

    /**
     * @expectedException \Slim\Exception
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
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Slim\\Exception", $e);
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $this->assertEquals("Controller Halt", (string)$e->getResponse()->getBody());
        }
    }

    public function testRedirect()
    {
        try {
            $controller = new Controller($this->request, $this->response);
            $response = $controller->redirect("/index");
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Slim\\Exception", $e);
            $response = $e->getResponse();
            $this->assertTrue($response->isRedirect());
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals("/index", $response->getHeader("Location"));
        }
    }
}
