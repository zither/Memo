<?php
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Controller 
{
    /**
     * Request
     *
     * @var \Psr\Http\Message\RequestInterface;
     */
    protected $request;

    /**
     * Response
     *
     * @var \Psr\Http\Message\ResponseInterface;
     */
    protected $response;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Stop
     *
     * @param ResponseInterface $response
     *
     * @throws \Slim\Exception
     */
    public function stop(ResponseInterface $response)
    {
        throw new \Slim\Exception($response);
    }


    /**
     * Halt
     *
     * @param int    $status  The desired Http status
     * @param string $message The desired Http message
     *
     * @throws \Slim\Exception
     */
    public function halt($status, $message = "")
    {
        $response = $this->response->withStatus($status);
        $response->getBody()->write($message);
        $this->stop($response);
    }

    /**
     * Redirect
     *
     * @param string $url
     * @param int $status
     *
     * @throws \Slim\Exception
     */
    public function redirect($url, $status = 302)
    {
        $response = $this->response->withStatus($status)->withHeader("Location", $url);
        $this->stop($response);
    }
}
