<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing an index on a table
 */
class Modyllic_Schema_Index extends Modyllic_Diffable {
    public $name  = "";
    public $docs = "";
    public $dynamic_name = false;
    public $spatial = false;
    public $primary = false;
    public $fulltext = false;
    public $unique   = false;
    public $using;
    public $columns  = array();

    /**
     * @param string $name
     */
    function __construct($name="") {
        $this->name = $name;
    }

    function get_name() {
        return $this->name;
    }

    /**
     * @param Modyllic_Schema_Index $other
     * @returns bool True if $other is equivalent to $this
     */
    function equal_to(Modyllic_Schema_Index $other, array $fromnames=null) {
        if ( get_class($other) != get_class($this) )   { return false; }
        if ( isset($fromnames) ) {
            if ( count($this->columns) != count($other->columns) ) { return false; }
            foreach ($other->columns as $name=>$column) {
                if ( ( ! isset($fromnames[$name]) or ! isset($this->columns[$fromnames[$name]]) ) and
                     ! isset($this->columns[$name]) ) {
                    return false;
                }
            }
        }
        else {
            if ( $this->columns != $other->columns ) { return false; }
        }
        if ( $this->primary != $other->primary ) { return false; }
        if ( $this->fulltext != $other->fulltext ) { return false; }
        if ( $this->unique != $other->unique ) { return false; }
        if ( $this->using != $other->using ) { return false; }
        if ( $this->spatial != $other->spatial ) { return false; }
        return true;
    }
}
