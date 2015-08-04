<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RuntimeException;

class FlashMessage implements ServiceProviderInterface
{
    /**
     * Storage key
     *
     * @var string
     */
    protected $storageKey = "MemoFlashMessages";

    /**
     * Storage
     *
     * @var array
     */
    protected $storage ;

    /**
     * Register Service provider interface
     *
     * @param Container $container
     *
     * @return self
     */
    public function register(Container $container)
    {
        return $container["flash"] = $this;
    }

    /**
     * Constructor
     *
     * @throws \RuntimeException
     */
    public function __construct()
    {
        if (!session_id()) {
            throw new RuntimeException("Session not found");
        }
        $this->initStorage();
    }

    /**
     * Init message storage
     */
    protected function initStorage()
    {
        if (!isset($_SESSION[$this->storageKey])) {
            $_SESSION[$this->storageKey] = [];
        }
        $this->storage =& $_SESSION[$this->storageKey];
        $this->storage["fromPrevious"] = [];

        if (isset($this->storage["forNext"]) && is_array($this->storage["forNext"])) {
            $this->storage["fromPrevious"] = $this->storage["forNext"];
        }
        $this->storage["forNext"] = [];    
    }

    /**
     * Get flash message
     *
     * @param string $key
     * @param null $default
     *
     * @return null|string
     */
    public function get($key, $default = null)
    {
        if (!isset($this->storage["fromPrevious"][$key])) {
            return $default;
        }
        return $this->storage["fromPrevious"][$key];
    }

    /**
     * Set flash message
     *
     * @param string $key
     * @param string $message
     */
    public function set($key, $message)
    {
        if (is_string($message) || method_exists($message, "__toString")) {
            $this->storage["forNext"][$key] = $message;
        }
    }

    /**
     * Does the storage have a given key
     *
     * @param mixed $key
     *
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->storage["fromPrevious"][$key]);
    }
}
