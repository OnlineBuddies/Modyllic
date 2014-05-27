<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Serial extends Modyllic_Type_BigInt {
    public $unsigned = true;
    function to_sql() {
        return $this->name;
    }
    function isa_equivalent(Modyllic_Type $other) {
        if ( parent::isa_equivalent($other) ) { return true; }
        if ( get_class($other) != "Modyllic_Type_BigInt" ) { return false; }
        if ( $this->unsigned != $other->unsigned ) { return false; }
        if ( $other->length() != $this->length() ) { return false; }
        return true;
    }
}

