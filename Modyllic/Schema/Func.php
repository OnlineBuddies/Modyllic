<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

require_once "Modyllic/Schema/Routine.php";

/**
 * A collection of attributes describing a stored function
 */
class Modyllic_Schema_Func extends Modyllic_Schema_Routine {
    function equal_to($other) {
        if ( ! parent::equal_to( $other ) ) { return false; }
        if ( ! $this->returns->equal_to( $other->returns ) ) { return false; }
        return true;
    }
}

