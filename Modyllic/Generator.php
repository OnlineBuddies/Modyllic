<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Generator {
    static private $dialect_map;
    static public function dialectToClass($dialect) {
        if ( !isset(self::$dialect_map) ) { self::$dialect_map = array(); }
        if ( ! isset(self::$dialect_map[$dialect]) ) {
            $capDialect = preg_replace( "/sql/", "SQL", $dialect );
            $classes_to_try = array_unique( array(
                "Modyllic_Generator_".ucfirst($capDialect),
                "Modyllic_Generator_".ucfirst($dialect)."SQL",
                "Modyllic_Generator_".ucfirst($dialect),
                "Modyllic_Generator_".$capDialect,
                "Modyllic_Generator_".$dialect."SQL",
                "Modyllic_Generator_".$dialect,
                $dialect,
                ) );
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
                throw new Exception("Could not find SQL dialect $dialect");
            }
        }
        return self::$dialect_map[$dialect];
    }
}
