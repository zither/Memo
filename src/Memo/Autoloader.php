<?php
/**
* Memo Framework (https://github.com/zither/Memo)
*
* @link https://github.com/zither/Memo
* @copyright Copyright (c) 2015 Jun Zhou
* @license https://github.com/zither/Memo/blob/master/LICENSE (MIT License)
*/
namespace Memo;

class Autoloader
{
    /**
     * Namespace prefixes
     *
     * @var array
     */
    public static $prefixes = [];

    /**
     * Add namespace prefix
     *
     * @param string $prefix
     * @param string $baseDir
     * @param boolean $prepend
     */
    public static function addNamespace($prefix, $baseDir)
    {
        $prefix = trim($prefix, "\\");
        $baseDir = rtrim($baseDir, "/\\") . DIRECTORY_SEPARATOR;
        if (!isset(static::$prefixes[$prefix])) {
            static::$prefixes[$prefix] = [];
        }
        array_push(static::$prefixes[$prefix], $baseDir);
     }

    /**
     * Autoloader PSR-4
     *
     * @param string $className
     *
     * @return boolean
     */
    public static function autoload($className)
    {
        $prefix = $className; 
        while (($pos = strrpos($prefix, '\\')) !== false) {
            $prefix = substr($className, 0, $pos);
            $relativeClass = substr($className, $pos + 1);

            if (!isset(static::$prefixes[$prefix])) {
                continue; 
            }

            foreach (static::$prefixes[$prefix] as $baseDir) {
                $file = sprintf(
                    "%s%s.php", 
                    $baseDir, 
                    str_replace("\\", DIRECTORY_SEPARATOR, $relativeClass)
                );
                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Register
     */
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
}
