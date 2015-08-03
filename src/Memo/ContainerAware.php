<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use ArrayAccess;

trait ContainerAware
{
    /**
     * Container
     *
     * @var ArrayAccess|null
     */
    protected $container = null;

    /**
     * Set container
     *
     * @param ArrayAccess $container
     *
     * @return self
     */
    public function setContainer(ArrayAccess $container)
    {
        $this->container = $container;    
        return $this;
    }

    /**
     * Container getter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (is_null($this->container)) {
            return null;
        }
        return isset($this->container[$key]) ? $this->container[$key] : null;
    }
}
