<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing an event
 */
class Modyllic_Schema_Trigger extends Modyllic_Schema_CodeBody {
    public $name;
    public $time;
    public $event;
    public $table;
    public $body;
    public $docs = "";

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }

    function equal_to(Modyllic_Schema_CodeBody $other) {
        if ( ! parent::equal_to($other)     ) { return false; }
        if ( $this->time != $other->time   ) { return false; }
        if ( $this->event != $other->event ) { return false; }
        if ( $this->body != $other->body   ) { return false; }
        return true;
    }

    function validate() {
        return array();
    }
}
