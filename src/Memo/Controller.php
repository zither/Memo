<?php
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Memo\Exception as MemoException;
use Slim\Http\Body;
use InvalidArgumentException;

class Controller 
{
    use ContainerAware;

    /**
     * Request
     *
     * @var ServerRequestInterface;
     */
    protected $request;

    /**
     * Response
     *
     * @var ResponseInterface;
     */
    protected $response;

    /**
     * Constructor
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Stop
     *
     * @param ResponseInterface $response
     *
     * @throws MemoException
     */
    public function stop(ResponseInterface $response)
    {
        throw (new MemoException())->setResponse($response);
    }

    /**
     * Halt
     *
     * @param int    $status  The desired Http status
     * @param string $message The desired Http message
     *
     * @throws MemoException
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
     * @throws MemoException
     */
    public function redirect($url, $status = 302)
    {
        $response = $this->response->withStatus($status)->withHeader("Location", $url);
        $this->stop($response);
    }

    /**
     * Bind output to response
     *
     * @param ResponseInterface $response
     * @param mixed $output
     *
     * @return ResponseInterface
     * @throws InvalidArgumentException
     */
    public function bindOutput(ResponseInterface $response, $output)
    {
        if (!is_string($output) && !method_exists($output, "__toString")) {
            throw new InvalidArgumentException("Output must be a string.");
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withBody($body);
    }
}
