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

    static function install() {
        $path = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." );   
        $include_path = explode(PATH_SEPARATOR,get_include_path());
        $matched = false;
        if ( ! in_array($path,array_map('realpath',$include_path)) ) {
            array_unshift( $include_path, $path );
            set_include_path( implode(PATH_SEPARATOR,$include_path) );
        }
        spl_autoload_register( array(__CLASS__,'autoload') );
    }

    static function autoload($classname) {
        $namespace = explode('\\',ltrim($classname,'\\'));
        $filename = str_replace('_', DIRECTORY_SEPARATOR, array_pop($namespace)) . ".php";
        $filename = implode(DIRECTORY_SEPARATOR, array_merge($namespace,array($filename)) );
        foreach (explode(PATH_SEPARATOR,get_include_path()) as $path) {
            if ( file_exists( realpath("$path/$filename") ) ) {
                $fullpath = realpath("$path/$filename");
                break;
            }
        }
        if ( isset($fullpath) ) {
            require_once $fullpath;
        }
        return;
    }
}
