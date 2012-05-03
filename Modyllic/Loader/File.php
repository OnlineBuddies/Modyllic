<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Loader_File {
    // Static class only
    private function __construct() {}

    static function load( $file, $schema ) {
        # A preparsed schemafile will have a .sqlc extension
        $file_bits = explode(".",$file);
        array_pop($file_bits);
        $sqlc_file = implode(".",$file_bits).".sqlc";

        $sqlc = @stat($sqlc_file);
        $sql  = @stat($file);
        if ( ! $sql ) {
            throw new Modyllic_Loader_Exception("$file: File not found.");
        }
        else if ( !$sqlc or $sqlc[7]==0 or ($sqlc[9] < $sql[9]) ) {
            if ( ($data = @file_get_contents($file)) === false ) {
                throw new Modyllic_Loader_Exception("Error opening $file");
            }
            $parser = new Modyllic_Parser();
            $parser->partial($schema, $data, $file, ";" );
        }
        else {
            if ( ($data = @file_get_contents($sqlc_file)) === false ) {
                throw new Modyllic_Loader_Exception("Error opening $sqlc_file");
            }
            $subschema = unserialize($data);
            if ( $subschema === FALSE ) {
                throw new Modyllic_Loader_Exception("Error unserializing $sqlc_file");
            }
            $schema->merge( $subschema );
        }
    }

}
