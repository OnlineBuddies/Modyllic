<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A PSR-0 compatible AutoLoader used by Modyllic's commandline tools.
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 */
class Modyllic_AutoLoader {

    /**
     * This calculates a new include path that includes this file's install location.
     * If the include path already has that then it is unchanged.
     * @returns string An include path, suitable for setting with set_include_path
     */
    static function get_new_include_path() {
        $path = realpath( dirname(__FILE__) . "/.." );
        $include_path = explode(PATH_SEPARATOR,get_include_path());
        if ( ! in_array($path,array_map('realpath',$include_path)) ) {
            array_unshift( $include_path, $path );
        }
        return implode(PATH_SEPARATOR,$include_path);
    }

    /**
     * Configures the include path and installs self::autoload as an autoloader.
     */
    static function install() {
        set_include_path( self::get_new_include_path() );
        spl_autoload_register( array(__CLASS__,'autoload') );
    }

    /**
     * @param string $classname
     * @returns string Where, relative to the include paths, to find $classname on disk
     */
    static function class_to_filename($classname) {
        $namespace = explode('\\',ltrim($classname,'\\'));
        $filename = str_replace('_', '/', array_pop($namespace)) . ".php";
        return implode('/', array_merge($namespace,array($filename)) );
    }

    /**
     * @param string $filename
     * @returns null|string The full path to $filename, or null if none exists
     */
    static function find_in_path($filename) {
        foreach ( explode(PATH_SEPARATOR,get_include_path() ) as $path) {
            $fullpath = realpath("$path/$filename");
            if ( file_exists( $fullpath ) ) {
                return $fullpath;
            }
        }
    }

    /**
     * @param string $classname The name of the class to try to autoload
     */
    static function autoload($classname) {
        $filename = self::class_to_filename($classname);
        $fullpath = self::find_in_path($filename);

        if ( isset($fullpath) ) {
            require_once $fullpath;
        }

        return;
    }
}
