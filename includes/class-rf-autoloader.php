<?php
/**
 * Autoloader for Recesso Facile classes
 *
 * @package RecessoFacile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Autoloader Class
 */
class RF_Autoloader {

    /**
     * Path to the includes directory
     *
     * @var string
     */
    private static $include_path = '';

    /**
     * Initialize autoloader
     */
    public static function init() {
        self::$include_path = RF_PLUGIN_DIR . 'includes/';

        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class Class name
     */
    public static function autoload($class) {
        // Only autoload classes with RF_ prefix
        if (strpos($class, 'RF_') !== 0) {
            return;
        }

        $file = self::get_file_name_from_class($class);
        $path = self::get_file_path($file);

        if ($path && is_readable($path)) {
            require_once $path;
        }
    }

    /**
     * Get file name from class name
     *
     * @param string $class Class name
     * @return string File name
     */
    private static function get_file_name_from_class($class) {
        return 'class-' . str_replace('_', '-', strtolower($class)) . '.php';
    }

    /**
     * Get file path
     *
     * @param string $file File name
     * @return string|false File path or false if not found
     */
    private static function get_file_path($file) {
        // Map of subdirectories
        $directories = array(
            'admin',
            'api',
            'services',
            'models',
            'validators',
            'emails',
            '',
        );

        foreach ($directories as $dir) {
            $path = self::$include_path . ($dir ? $dir . '/' : '') . $file;

            if (file_exists($path)) {
                return $path;
            }
        }

        return false;
    }
}
