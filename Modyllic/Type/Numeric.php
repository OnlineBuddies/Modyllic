<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */

class Modyllic_Numeric extends Modyllic_Type {
    public $default_length = 11;
    public $length;
    public $unsigned = false;
    public $zerofill = false;

    function __construct($type) {
        parent::__construct($type);
        $this->length = $this->default_length;
    }
    function equal_to(Modyllic_Type $other) {
        if ( ! parent::equal_to($other) ) { return false; }
        if ( $this->unsigned != $other->unsigned ) { return false; }
        if ( $this->zerofill != $other->zerofill ) { return false; }
        if ( $this->length != $other->length) { return false; }
        return true;
    }
    function clone_from(Modyllic_Type $old) {
        parent::clone_from($old);
        $this->unsigned = $old->unsigned;
        $this->zerofill = $old->zerofill;
        $this->length = $old->length;
    }
    function numify(Modyllic_Token $value) {
        if ( $value instanceOf Modyllic_Token_String ) {
            $plain = $value->unquote() + 0;
        }
        else if ( $value instanceOf Modyllic_Token_Num ) {
            $plain = $value->value() + 0;
        }
        else {
            $plain = 0;
        }
        return $plain;
    }
}
