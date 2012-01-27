<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Loader_DB {
    static private $dialect_map;
    static public function dbDriver($dialect) {
        if ( !isset(self::$dialect_map) ) { self::$dialect_map = array(); }
        if ( ! isset(self::$dialect_map[$dialect]) ) {
            $capDialect = preg_replace( "/sql/", "SQL", $dialect );
            $classes_to_try = array(
                "Modyllic_Loader_DB_".ucfirst($capDialect),
                "Modyllic_Loader_DB_".ucfirst($dialect)."SQL",
                "Modyllic_Loader_DB_".ucfirst($dialect),
                "Modyllic_Loader_DB_".$capDialect,
                "Modyllic_Loader_DB_".$dialect."SQL",
                "Modyllic_Loader_DB_".$dialect,
                $dialect,
                );
            foreach ($classes_to_try as $class) {
                $file = preg_replace("/_/","/", $class) . ".php";
                @include_once $file;
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
                throw new Modyllic_Loader_Exception("Could not identify database in DSN: $source");
            }
            $dsn = $driver.':'.implode(';',$opts);
            return array( $driver, $dsn, $dbname, $username, $password );
        }
        else {
            throw new Modyllic_Loader_Exception("Invalid DSN: $source");
        }
    }

    static function load( $source ) {
        list( $driver, $dsn, $dbname, $username, $password ) = self::parse_dsn($source);
        Modyllic_Status::$sourceName = $dsn;

        $class = self::dbDriver( $driver );

        $dbh = new PDO( $dsn, $username, $password, array( PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>TRUE ) );

        $schema = call_user_func( array($class,'load'), $dbh, $dbname );

        return $schema;
    }
}
