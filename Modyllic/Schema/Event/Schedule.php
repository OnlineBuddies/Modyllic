<?php
/**
 * Copyright © 2013 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A collection of attributes describing an event
 */
class Modyllic_Schema_Event_Schedule extends Modyllic_Diffable {
    public $kind;
    public $schedule;
    public $starts;
    public $ends;

    function equal_to(Modyllic_Schema_Event_Schedule $other) {
        if ( $this->kind != $other->kind ) { return false; }
        if ( $this->schedule != $other->schedule ) { return false; }
        if ( $this->starts != $other->starts ) { return false; }
        if ( $this->ends != $other->ends ) { return false; }

        return true;
    }
}

