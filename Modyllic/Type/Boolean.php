<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Type_Boolean extends Modyllic_Type_TinyInt {
    public $default_length = 1;
    function isa_equivalent(Modyllic_Type $other) {
        if ( parent::isa_equivalent($other) ) { return true; }
        if ( get_class($other) == "Modyllic_Type_TinyInt" ) { return true; }
        return false;
    }
}
