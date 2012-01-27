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

    static function load(array $sources) {
        $schema = new Modyllic_Schema();
        Modyllic_Status::$sourceCount += count($sources);
        foreach ($sources as $source) {
            Modyllic_Status::$sourceName = $source;
            Modyllic_Status::$sourceIndex ++;

            if ( is_dir($source) ) {
                $subschema = Modyllic_Loader_Dir::load($source);
            }
            else if ( file_exists($source) ) {
                $subschema = Modyllic_Loader_File::load($source);
            }
            else if ( Modyllic_Loader_DB::is_dsn($source) ) {
                $subschema = Modyllic_Loader_DB::load($source);
            }
            else {
                throw new Modyllic_Loader_Exception("Could not load $source, file or directory not found");
            }

            $schema->merge($subschema);
            Modyllic_Status::status( 1, 1 );

        }
        $schema->load_sqlmeta();
        return $schema;
    }

    /**
     * Load a schema from a MySQL database's INFORMATION_SCHEMA database
     *
     * @param string $host
     * @param string $dbname
     * @param string $user
     * @param string $pass
     * @returns Modyllic_Schema
     */
    static function from_db($dsn,$dbname,$user=null,$pass=null) {
        Modyllic_Status::$sourceName = $dsn;
        $dbh = new PDO( $dsn, $user, $pass, array( PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>TRUE ) );
        $loader = new Modyllic_Schema_FromDB( $dbh );
        $schema = $loader->get_schema( $dbname );
        Modyllic_Status::$sourceIndex ++;
        return $schema;
    }
}
