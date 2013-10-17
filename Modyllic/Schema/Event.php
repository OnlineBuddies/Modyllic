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
class Modyllic_Schema_Event extends Modyllic_Schema_CodeBody {
    public $name;
    public $preserve = false;
    public $status = 'ENABLE';
    public $docs = "";

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
        $this->schedule = new Modyllic_Schema_Event_Schedule();
    }

    function equal_to(Modyllic_Schema_CodeBody $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( ! $this->schedule->equal_to($other->schedule) ) { return false; }
        if ( $this->preserve != $other->preserve ) { return false; }
        if ( $this->status == 'DISABLE ON SLAVE' and $other->status == 'ENABLE' ) { return true; } // !!!
        if ( $this->status != $other->status ) { return false; }
        return true;
    }

    function inflate($key,$value) {
        if ( in_array($key, array('kind','schedule','starts','ends')) ) {
            $this->schedule->$key = $value;
        }
        else {
            parent::inflate($key,$value);
        }
    }

    function validate() {
        return array();
    }
}
