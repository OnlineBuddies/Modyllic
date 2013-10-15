<?php
/**
 * Copyright © 2011 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

/**
 * A base class for various schema objects.  Handles generic things like
 * providing previous values for the diff engine.  In a perfect world this
 * would be a runtime trait applied by the diff engine.
 */
class Modyllic_Diffable {
    public $from;

    public function inflate($attr,$value) {
        # Types are always a type object, which we'll
        # recreate via cloning if a different type spec is
        # requested.  This allows us to round trip things
        # like BOOLEAN and SERIAL.
        if ( $attr == "type" ) {
            $new_type = Modyllic_Type::create($value);
            $new_type->copy_from( $this->type );
            $this->type = $new_type;
        }
        else {
            $this->$attr = $value;
        }
    }
}
