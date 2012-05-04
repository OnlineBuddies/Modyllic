<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * Factory class for creating Schema objects from various sources
 */
class Modyllic_Loader {
    // Static class only
    private function __construct() {}

    static function determine_loader($source) {
        if ( is_dir($source) ) {
            $loader = "Modyllic_Loader_Dir";
        }
        else if ( file_exists($source) ) {
            $loader = "Modyllic_Loader_File";
        }
        else if ( Modyllic_Loader_DB::is_dsn($source) ) {
            $loader = "Modyllic_Loader_DB";
        }
        else {
            $loader = null;
        }
        return array($source,$loader);
    }

    static function load(array $sources,$schema=null) {
        if ( !isset($schema) ) {
            $schema = new Modyllic_Schema();
        }
        Modyllic_Status::$source_count += count($sources);
        foreach ($sources as $source) {
            // Strip trailing slashes from directory names
            if ( substr($source,-1) == "/" ) {
                $source = substr($source,0,-1);
            }
            Modyllic_Status::$source_name = $source;
            Modyllic_Status::$source_index ++;
            list($source,$loader) = self::determine_loader($source);
            if ( isset($loader) ) {
                call_user_func(array($loader,'load'),$source,$schema);
            }
            else {
                throw new Modyllic_Loader_Exception("Could not load $source, file or directory not found");
            }

            Modyllic_Status::status( 1, 1 );

        }
        $schema->load_sqlmeta();
        return $schema;
    }
}
