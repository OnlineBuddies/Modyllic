<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Schema/CodeBody.php";

/**
 * A collection of attributes describing an event
 */
class Modyllic_Schema_Event extends Modyllic_Schema_CodeBody {
    public $name;
    public $schedule;
    public $preserve = false;
    public $status;
    public $docs = "";

    /**
     * @param string $name
     */
    function __construct($name) {
        $this->name = $name;
    }

    function equal_to(Modyllic_Schema_CodeBody $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->schedule != $other->schedule ) { return false; }
        if ( $this->preserve != $other->preserve ) { return false; }
        if ( $this->status != $other->status ) { return false; }
        return true;
    }
}

