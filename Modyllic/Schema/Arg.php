<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing an argument to a stored procedure
 * or function.
 */
class Modyllic_Schema_Arg extends Modyllic_Diffable {
    public $name;
    public $type;
    public $dir = "IN";
    public $docs = "";
    function to_sql() {
        $sql = "";
        if ( $dir != "IN" ) {
            $sql .= "$dir ";
        }
        $sql .= Modyllic_SQL::quote_ident($name)." ";
        $sql .= $type->to_sql();
        return $sql;
    }
    function equal_to($other) {
        if ( $this->name != $other->name ) { return false; }
        if ( $this->dir != $other->dir ) { return false; }
        if ( ! $this->type->equal_to($other->type) ) { return false; }
        return true;
    }
}


