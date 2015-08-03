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

class Exception extends \Exception
{
    /**
     * Response
     *
     * @var ResponseInterface;
     */
    protected $response;

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set response
     *
     * @param ResponseInterface $response
     *
     * @return self
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }
}
