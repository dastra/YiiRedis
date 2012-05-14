<?php

/**
 * Implements a lightweight PSR-0 compliant autoloader for use by Yii.
 */

class StaticPredisAutoloader
{
    /**
     * @param string $baseDirectory Base directory where the source files are located.
     */
    public function __construct($baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;
    }

    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param boolean $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false)
    {
        spl_autoload_register(array(new self, 'autoload'), true, $prepend);
    }

    /**
     * Loads a class from a file using its fully qualified name.
     *
     * @param string $className Fully qualified name of a class.
     */
    public static function autoload($className)
    {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'predis' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Predis';
        $prefix = __NAMESPACE__ . 'Predis\\';
        $prefixLength = strlen($prefix);

        if (0 === strpos($className, $prefix)) {
            $parts = explode('\\', substr($className, $prefixLength));
            require_once($directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php');
        }
    }
}
