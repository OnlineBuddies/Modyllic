<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Loader_DB {
    // Static class only
    private function __construct() {}

    static private $dialect_map;
    static public function db_driver($dialect) {
        if ( !isset(self::$dialect_map) ) { self::$dialect_map = array(); }
        if ( ! isset(self::$dialect_map[$dialect]) ) {
            $cap_dialect = preg_replace( "/sql/u", "SQL", $dialect );
            $classes_to_try = array(
                "Modyllic_Loader_DB_".ucfirst($cap_dialect),
                "Modyllic_Loader_DB_".ucfirst($dialect)."SQL",
                "Modyllic_Loader_DB_".ucfirst($dialect),
                "Modyllic_Loader_DB_".$cap_dialect,
                "Modyllic_Loader_DB_".$dialect."SQL",
                "Modyllic_Loader_DB_".$dialect,
                $dialect,
                );
            foreach ($classes_to_try as $class) {
                $file = preg_replace("/_/u","/", $class) . ".php";
                if ( class_exists($class) ) {
                    self::$dialect_map[$dialect] = $class;
                    self::$dialect_map[$class] = $class;
                    break;
                }
            }
            if ( ! isset(self::$dialect_map[$dialect]) ) {
                throw new Exception("Could not find Loader for SQL dialect $dialect");
            }
        }
        return self::$dialect_map[$dialect];
    }

    static function is_dsn($source) {
        return preg_match("/^(\w+):(.*)/u",$source);
    }

    static function parse_dsn($source) {
        if ( preg_match("/^(\w+):(.*)/u",$source,$matches) ) {
            $driver = $matches[1];
            $username = null;
            $password = null;
            $dbname = null;
            $opts = array();
            foreach ( preg_split('/[:;]/u',$matches[2]) as $opt_pair ) {
                list($name,$value) = explode('=',$opt_pair,2);
                $value = rawurldecode($value);
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
                throw new Modyllic_Loader_Exception("Could not identify database in DSN: $source");
            }
            $dsn = $driver.':'.implode(';',$opts);
            return array( $driver, $dsn, $dbname, $username, $password );
        }
        else {
            throw new Modyllic_Loader_Exception("Invalid DSN: $source");
        }
    }

    static function load( $source, $schema ) {
        list( $driver, $dsn, $dbname, $username, $password ) = self::parse_dsn($source);
        Modyllic_Status::$source_name = $dsn;

        $class = self::db_driver( $driver );

        $dbh = new PDO( $dsn, $username, $password, array( PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>true ) );

        call_user_func( array($class,'load'), $dbh, $dbname, $schema );
    }
}
