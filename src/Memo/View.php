<?php 
/**
* Memo view template engine
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

class View 
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
    protected $folders = array();

    /**
     * Layout queue
     *
     * @var array
     */
    protected $layouts = array();

    /**
     * shareVars
     *
     * @var array
     */
    protected $shareVars = array();

    /**
     * Template extension
     *
     * @var string
     */
    protected $extension = "php";

    /**
     * Helper instance
     *
     * @var mixed
     */
    protected $helper = null;

    /**
     * section container
     *
     * @var array
     */
    protected $sections = array();

    /**
     * section status stack
     *
     * @var array
     */
    protected $sectionStack = array();

    /**
     * Constructor
     *
     * @param array $userSettings
     */
    public function __construct(Array $userSettings = array())
    {
        $validProperties = array("template", "folders", "extension", "helper");
        $userSettings = array_intersect_key(
            $userSettings, 
            array_flip($validProperties)
        );
       
        foreach ($userSettings as $property => $value) {
            $method = sprintf("set%s", ucfirst(strtolower($property)));
            call_user_func(array($this, $method), $value);
        }
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
        array_push($this->folders, rtrim($folder, '/'));
    }

    /**
     * Set template extension
     *
     * @param string $extension
     */
    public function setExtension($extension)
    {
        $this->extension = ltrim($extension, ".");
    }

    /**
     * Insert a layout into the layout queue
     *
     * @param string $layout
     */
    public function layout($layout)
    {
        array_push($this->layouts, $layout);
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
        array_push($this->sectionStack, $name);
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
        if (empty($this->sectionStack)) {
            throw new \LogicException(
                'Must open a section before calling the close method.'
            );
        }
        $content = ob_get_clean();
        $section = array_pop($this->sectionStack);
        if (!isset($this->sections[$section])) {
            $this->sections[$section] = $content;
        }
    }

    /**
     * Get section's value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function section($name)
    {
        if (isset($this->sections[$name])) {
            return $this->sections[$name];
        }
    }

    /**
     * Assgin template variable
     *
     * @param mixed $var
     * @param mixed $value
     */
    public function assign($var, $value)
    {
        $this->shareVars[$var] = $value;
    }

    /**
     * Render a template
     *
     * The template's layout could be nested
     *
     * @throws \LogicExcetpion
     *
     * @return string
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
            while (count($this->layouts) > 0) {
                include $this->getPath(array_shift($this->layouts));
            }
        } catch (\Exception $e) {
            $this->recursiveObEndClean($level);
            throw $e;
        }    

        // Check the section stack, every section should be closed.
        if (!empty($this->sectionStack)) {
            $this->recursiveObEndClean($level);
            $message = sprintf(
                'Unclosed section%s: %s.', 
                count($this->sectionStack) > 1 ? "s" : "", 
                implode($this->sectionStack, ",")
            );
            throw new \LogicException($message);
        }
        return trim(ob_get_clean());
    }

    /**
     * Get template's path from folders, the first found is used.
     *
     * @param string $template
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getPath($template)
    {
        foreach ($this->folders as $folder) {
            $path = sprintf("%s/%s.%s", rtrim($folder, "/\\"), $template, $this->extension);
            if (file_exists($path)) {
                return $path;
            }
        }
        throw new \InvalidArgumentException(
            sprintf("Invalid template: %s.%s!", $template, $this->extension)
        );
    }

    /**
     *  Recursive ob_end_clean
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
     * Print template
     */
    public function display()
    {
        echo $this->render();
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
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call($method, $arguments) 
    {
        if (is_null($this->helper) || !method_exists($this->helper, $method)) {
            throw new \BadMethodCallException(
                sprintf("Call to undefined method %s::%s!", __CLASS__, $method)
            );        
        }
        return call_user_func_array(array($this->helper, $method), $arguments);
    }
}
