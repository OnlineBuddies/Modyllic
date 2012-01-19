<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once dirname(__FILE__)."/../Parser.php";
require_once dirname(__FILE__)."/FromDB.php";

class Modyllic_Schema_Loader_Exception extends Exception {}

/**
 * Factory class for creating Schema objects from various sources
 */
class Modyllic_Schema_Loader {
    static $source;
    /**
     * Load a schema from a file of SQL DDL
     *
     * @param string $filename
     * @returns Modyllic_Schema
     */
    static function from_file($file) {
        if ( is_dir($file) ) {
            return self::from_dir($file);
        }
        else {
            return self::from_files(array($file));
        }
    }
    
    static function from_files(array $files) {
        $parser = new Modyllic_Parser();
        $schema = new Modyllic_Schema();
        foreach ($files as $file) {
            $file_bits = explode(".",$file);
            array_pop($file_bits);
            $sqlc_file = implode(".",$file_bits).".sqlc";
            $sqlc = @stat($sqlc_file);
            $sql  = @stat($file);
            if ( ! $sql ) {
                throw new Modyllic_Schema_Loader_Exception("$file: File not found.");
            }
            else if ( !$sqlc or $sqlc[9] < $sql[9] ) {
                if ( ($data = @file_get_contents($file)) === FALSE ) {
                    throw new Modyllic_Schema_Loader_Exception("Error opening $file");
                }
                $parser->partial($schema, $data, $file, ";" );
            }
            else {
                if ( ($data = @file_get_contents($sqlc_file)) === FALSE ) {
                    throw new Modyllic_Schema_Loader_Exception("Error opening $sqlc_file");
                }
                $subschema = unserialize($data);
                $schema->merge($subschema);
            }
        }
        $schema->finalize();
        return $schema;
    }
    
    /**
     * Load a schema from a directory of SQL files
     * @param string $filename
     * @returns Modyllic_Schema
     */
    static function from_dir($dir) {
        $raw = glob("$dir/*.sql",GLOB_NOSORT);
        natsort($raw);
        return self::from_files($raw);
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
    static function from_db($host=null,$dbname,$user=null,$pass=null) {
        $dsn = "mysql:";
        if ( isset($host) ) {
            $dsn .= "host=$host;";
        }
        $dsn .= "dbname=information_schema";
        self::$source = $dsn;
        $dbh = new PDO( $dsn, $user, $pass, array( PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>TRUE ) );
        $loader = new Modyllic_Schema_FromDB( $dbh );
        $schema = $loader->get_schema( $dbname );
        return $schema;
    }
}
