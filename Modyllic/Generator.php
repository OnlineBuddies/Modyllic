<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator {
    // Static class only
    private function __construct() {}
    static private $dialect_map;
    static public function dialect_to_class($dialect) {
        if ( !isset(self::$dialect_map) ) { self::$dialect_map = array(); }
        if ( ! isset(self::$dialect_map[$dialect]) ) {
            $cap_dialect = preg_replace( "/sql/u", "SQL", $dialect );
            $classes_to_try = array_unique( array(
                "Modyllic_Generator_".ucfirst($cap_dialect),
                "Modyllic_Generator_".ucfirst($dialect)."SQL",
                "Modyllic_Generator_".ucfirst($dialect),
                "Modyllic_Generator_".$cap_dialect,
                "Modyllic_Generator_".$dialect."SQL",
                "Modyllic_Generator_".$dialect,
                $dialect,
                ) );
            foreach ($classes_to_try as $class) {
                $file = preg_replace("/_/u","/", $class) . ".php";
                if ( class_exists($class) ) {
                    self::$dialect_map[$dialect] = $class;
                    self::$dialect_map[$class] = $class;
                    break;
                }
            }
            if ( ! isset(self::$dialect_map[$dialect]) ) {
                throw new Exception("Could not find SQL dialect $dialect");
            }
        }
        return self::$dialect_map[$dialect];
    }

    static public function create($dialect, array $args) {
        $class = self::dialect_to_class($dialect);
        $reflectionObj = new ReflectionClass($class);
        return $reflectionObj->newInstanceArgs($reflectionObj);
    }
}
