<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing a column in a table
 */
class Modyllic_Schema_Column extends Modyllic_Diffable {
    public $name;
    public $aliases = array();
    public $previously;
    public $type;
    public $null = true;
    public $default = "NULL";
    public $auto_increment = false;
    public $on_update;
    public $docs = "";
    public $after;
    public $is_primary;

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }


    /**
     * @param Modyllic_Schema_Column $other
     * @returns bool True if $other is equivalent to $this
     */
    function equal_to($other) {
        if ( $this->name != $other->name ) { return false; }
        if ( ! $this->type->equal_to( $other->type ) ) { return false; }
        if ( $this->null != $other->null ) { return false; }
        if ( $this->default != $other->default ) { return false; }
        if ( $this->auto_increment != $other->auto_increment ) { return false; }
        if ( $this->on_update != $other->on_update ) { return false; }
        if ( $this->aliases != $other->aliases ) { return false; }
        return true;
    }
}

