<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Parser.php";
require_once "Modyllic/Schema.php";
require_once "Modyllic/Status.php";

require_once "Modyllic/Loader/File.php";
require_once "Modyllic/Loader/Dir.php";
require_once "Modyllic/Loader/DB.php";

class Modyllic_Loader_Exception extends Exception {}

/**
 * Factory class for creating Schema objects from various sources
 */
class Modyllic_Loader {

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
        Modyllic_Status::$sourceCount += count($sources);
        foreach ($sources as $source) {
            Modyllic_Status::$sourceName = $source;
            Modyllic_Status::$sourceIndex ++;
            list($source,$loader) = self::determine_loader($source);
            if ( isset($loader) ) {
                $loader::load($source,$schema);
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
