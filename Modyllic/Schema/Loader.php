<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Parser.php";
require_once "Modyllic/Schema.php";
require_once "Modyllic/Schema/FromDB.php";

require_once "Modyllic/Status.php";

class Modyllic_Schema_Loader_Exception extends Exception {}

/**
 * Factory class for creating Schema objects from various sources
 */
class Modyllic_Schema_Loader {

    static function is_dsn($source) {
        return preg_match("/^(\w+):(.*)/",$source);
    }
    static function parse_dsn($source) {
        if ( preg_match("/^(\w+):(.*)/",$source,$matches) ) {
            $driver = $matches[1];
            $username = null;
            $password = null;
            $dbname = null;
            $opts = array();
            foreach ( explode(';',$matches[2]) as $opt_pair ) {
                list($name,$value) = explode('=',$opt_pair);
                if ( $name == 'username' ) {
                    $username = $value;
                }
                else if ( $name == 'password' ) {
                    $password = $value;
                }
                else if ( $name == 'dbname' ) {
                    $dbname = $value;
                }
                else {
                    $opts[] = $opt_pair;
                }
            }
            if ( ! isset($dbname) ) {
                throw new Modyllic_Schema_Loader_Exception("Could not identify database in DSN: $source");
            }
            $dsn = $driver.':'.implode(';',$opts);
            return array( $dsn, $dbname, $username, $password );
        }
        else {
            throw new Modyllic_Schema_Loader_Exception("Invalid DSN: $source");
        }
    }
    
    static function load(array $sources) {
        $schema = new Modyllic_Schema();
        Modyllic_Status::$sourceCount = count($sources);
        Modyllic_Status::$sourceIndex = 0;
        foreach ($sources as $source) {
            Modyllic_Status::$sourceName = $source;
            if ( is_dir($source) ) {
                $filelist = glob("$source/*.sql",GLOB_NOSORT);
                natsort($filelist);
                Modyllic_Status::$sourceIndex += count($filelist);
                $subschema = self::from_files($filelist);
            }
            else if ( file_exists($source) ) {
                $subschema = self::from_files(array($source));
            }
            else if ( self::is_dsn($source) ) {
                $subschema = call_user_func_array( array(__CLASS__,'from_db'), self::parse_dsn($source) );
            }
            else {
                throw new Modyllic_Schema_Loader_Exception("Could not load $source, file or directory not found");
            }
            $schema->merge($subschema);
        }
        $schema->load_sqlmeta();
        return $schema;
    }
    
    static function from_files(array $files) {
        $parser = new Modyllic_Parser();
        $schema = new Modyllic_Schema();
        foreach ($files as $file) {
            Modyllic_Status::$sourceName = $file;
            Modyllic_Status::$sourceIndex ++;
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
                $schema = $parser->partial($schema, $data, $file, ";" );
            }
            else {
                if ( ($data = @file_get_contents($sqlc_file)) === FALSE ) {
                    throw new Modyllic_Schema_Loader_Exception("Error opening $sqlc_file");
                }
                $subschema = unserialize($data);
                $schema->merge($subschema);
            }
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
