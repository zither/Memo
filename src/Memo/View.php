<?php 
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class View implements ServiceProviderInterface
{
    /**
     * The current template name
     *
     * @var string
     */
    protected $template;

    /**
     * folders
     *
     * @var array
     */
    protected $folders = [];

    /**
     * Helper instance
     *
     * @var mixed
     */
    protected $helper = null;

    /**
     * shareVars
     *
     * @var array
     */
    protected $shareVars = [];

    /**
     * section container
     *
     * @var array
     */
    protected $sections = [];

    /**
     * section status stack
     *
     * @var array
     * @link http://php.net/manual/en/class.splstack.php
     */
    protected $sectionStack;

    /**
     * Layout queue
     *
     * @var \SplQueue
     * @link http://php.net/manual/en/class.splqueue.php
     */
    protected $layoutQueue;

    /**
     * Constructor
     *
     * @param array $userSettings
     */
    public function __construct(Array $userSettings = [])
    {
        $this->layoutQueue = new \SplQueue();
        $this->sectionStack = new \SplStack();

        $validProperties = ["template", "folders", "helper"];
        $userSettings = array_intersect_key(
            $userSettings, 
            array_flip($validProperties)
        );
       
        foreach ($userSettings as $property => $value) {
            $method = sprintf("set%s", ucfirst(strtolower($property)));
            call_user_func([$this, $method], $value);
        }
    }

    /**
     * Register this cache provider with a Pimple container
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container["view"] = $this;
    }

    /**
     * Set template name
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Set template folder paths
     *
     * @param Array $folders
     */
    public function setFolders(Array $folders)
    {
        $this->folders = $folders;
    }

    /**
     * Add a template folder
     *
     * @param string $folder
     */
    public function addFolder($folder)
    {
        array_push($this->folders, rtrim($folder, "/"));
    }

    /**
     * Insert a layout into the layout queue
     *
     * @param string $layout
     */
    public function layout($layout)
    {
        $this->layoutQueue->enqueue($layout);
    }

    /**
     * Open a section
     *
     * Insert a section name into the section stack
     *
     * @param string $name
     */
    public function open($name)
    {
        $this->sectionStack->push($name);
        ob_start();
    }

    /**
     * Close a section
     *
     * Remove last section from the section stack
     *
     * @throws \LogicException
     */
    public function close()
    {
        if ($this->sectionStack->isEmpty()) {
            throw new \LogicException(
                "Must open a section before calling the close method."
            );
        }
        $content = ob_get_clean();
        $section = $this->sectionStack->pop();
        if (!isset($this->sections[$section])) {
            $this->sections[$section] = $content;
        }
    }


    /**
     * Get section content
     *
     * @param string $key
     * @param string|null $default
     *
     * @return string|null
     */
    public function section($key, $default = null)
    {
        return isset($this->sections[$key]) ? $this->sections[$key] : $default;
    }

    /**
     * Assgin template variable
     *
     * @param string $key
     * @param mixed $value
     */
    public function assign($key, $value)
    {
        $this->shareVars[$key] = $value;
    }

    /**
     * Render a template
     *
     * The template's layout could be nested
     *
     * @return string
     * @throws \LogicExcetpion
     */
    public function render()
    {
        try {
            // get the current level of the output buffering mechanism
            $level = ob_get_level();
            ob_start();
            extract($this->shareVars, EXTR_OVERWRITE);
            include $this->getPath($this->template);

            // Include all layouts from the layout queue
            while (!$this->layoutQueue->isEmpty()) {
                include $this->getPath($this->layoutQueue->dequeue());
            }
        } catch (\Exception $e) {
            $this->recursiveObEndClean($level);
            throw $e;
        }    

        if (!$this->sectionStack->isEmpty()) {
            $this->recursiveObEndClean($level);
            throw new \LogicException("Not all of sections was closed.");
        }
        return trim(ob_get_clean());
    }

    /**
     * Get template's path from folders, the first found is used.
     *
     * @param string $template
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getPath($template)
    {
        foreach ($this->folders as $folder) {
            $templatePath = sprintf(
                "%s/%s.php", 
                rtrim($folder, "/\\"), 
                $template 
            );
            if (file_exists($templatePath)) {
                return $templatePath;
            }
        }
        throw new \InvalidArgumentException(
            sprintf("Invalid template: %s.php", $template)
        );
    }

    /**
     * Recursive ob_end_clean
     *
     * @param int $level
     */
    protected function recursiveObEndClean($level = 0) 
    {
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
    }

    /**
     * Set helper instance
     *
     * @param object $helper
     *
     * @throws \InvalidArgumentException
     */
    public function setHelper($helper)
    {
        if (!is_object($helper)) {
            $message = sprintf(
                "Helper must be of the type object, %s given.",
                getType($helper)
            );
            throw new \InvalidArgumentException($message);
        }
        $this->helper = $helper;
    }

    /**
     * Property getter
     *
     * @param mixed $property
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        return false;
    }

    /**
     * Call helper function
     *
     * @param mixed $method
     * @param mixed $arguments
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments) 
    {
        if (is_null($this->helper) || !method_exists($this->helper, $method)) {
            throw new \BadMethodCallException(
                sprintf("Call to undefined method %s::%s!", __CLASS__, $method)
            );        
        }
        return call_user_func_array([$this->helper, $method], $arguments);
    }
}
