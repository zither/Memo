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
use InvalidArgumentException;
use Memo\Exception as MemoException;

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
     * Bind output to response
     *
     * @param string|object $output
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     * @throws InvalidArgumentException for invalid output or response
     */
    public function bindOutput($output, $response = null)
    {
        if (!is_string($output) && !method_exists($output, "__toString")) {
            throw new InvalidArgumentException("Output must be a string");
        }

        if (null !== $response && !$response instanceof ResponseInterface) {
            throw new InvalidArgumentException("Expected a ResponseInterface");
        }

        $response = null === $response ? $this->response : $response;
        $response->getBody()->write($output);

        return $response;
    }

    /**
     * Stop
     *
     * @param ResponseInterface $response
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
     * @throws MemoException
     */
    public function redirect($url, $status = 302)
    {
        $response = $this->response->withStatus($status)->withHeader("Location", $url);
        $this->stop($response);
    }

    /**
     * Generate JSON response
     *
     * @param array $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    public function jsonResponse(array $data, $statusCode = 200)
    {
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $response = $this->response
            ->withStatus($statusCode)
            ->withHeader("Content-Type", "application/json");
        return $this->bindOutput($jsonData, $response);
    }
}
